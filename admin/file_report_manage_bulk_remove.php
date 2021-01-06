<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Bulk Remove');
define('ADMIN_SELECTED_PAGE', 'files');
define('ADMIN_SELECTED_SUB_PAGE', 'file_report_manage');
define('MAX_ABUSE_REMOVAL_URLS', 500);

// allow some time to run
set_time_limit(60 * 60);

// includes and security
include_once('_local_auth.inc.php');

// handle page submissions
$file_urls = '';
$confirm_password = '';
$removal_type = 4;
$admin_notes = '';
$deleteRs = array();
if (isset($_REQUEST['submitted'])) {
    // pickup variables
    $file_urls = trim($_REQUEST['file_urls']);
    $confirm_password = trim($_REQUEST['confirm_password']);
    $removal_type = (int) $_REQUEST['removal_type'];
    $admin_notes = trim($_REQUEST['admin_notes']);

    // validate submission
    if (_CONFIG_DEMO_MODE == true) {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    elseif (strlen($file_urls) == 0) {
        adminFunctions::setError(adminFunctions::t("file_report_manage_bulk_remove_enter_file_urls", "Please enter the file urls."));
    }
    elseif (strlen($confirm_password) == 0) {
        adminFunctions::setError(adminFunctions::t("file_report_manage_bulk_remove_enter_password", "Please enter your account password."));
    }

    // check password
    if (adminFunctions::isErrors() == false) {
        $storedUserPassword = $db->getValue('SELECT password FROM users WHERE id = ' . (int) $Auth->id . ' LIMIT 1');
        if (Password::validatePassword($confirm_password, $storedUserPassword) == false) {
            adminFunctions::setError(adminFunctions::t("file_report_manage_bulk_remove_entered_password_is_invalid", "The account password you entered is incorrect."));
        }
    }

    if (adminFunctions::isErrors() == false) {
        // loop urls
        $file_urls = str_replace(array("\n\r", "\r\n", "\r\r", "\r"), "\n", $file_urls);
        $file_urls = str_replace(array("\n\n"), "\n", $file_urls);
        $fileUrlItems = explode("\n", $file_urls);
        if (COUNT($fileUrlItems) > MAX_ABUSE_REMOVAL_URLS) {
            adminFunctions::setError(adminFunctions::t("file_report_manage_bulk_remove_entered_too_many", "Too many urls, max [[[URLS]]].", array('URLS' => MAX_ABUSE_REMOVAL_URLS)));
        }
    }

    if (adminFunctions::isErrors() == false) {
        // loop urls and process
        foreach ($fileUrlItems AS $fileUrlItem) {
            $file = file::loadByFullUrl($fileUrlItem);
            if (!$file) {
                // make sure we've found the file
                $deleteRs[] = '<i class="fa fa-close text-danger"></i> <span class="text-danger">' . adminFunctions::t("file_report_manage_bulk_remove_file_not_found", "Error: File not found") . '</span> - ' . adminFunctions::makeSafe($fileUrlItem);
            }
            elseif ($file->status != 'active') {
                // make sure the file is active
                $deleteRs[] = '<i class="fa fa-close text-danger"></i> <span class="text-danger">' . adminFunctions::t("file_report_manage_bulk_remove_file_not_active", "Error: File not active") . '</span> - ' . adminFunctions::makeSafe($fileUrlItem);
            }
            else {
                // delete it
                $file->removeBySystem();
                $db->query('UPDATE file SET adminNotes = :adminNotes WHERE id = :id LIMIT 1', array('adminNotes' => $admin_notes, 'id' => $file->id));
                if ($db->affectedRows() == 1) {
                    $deleteRs[] = '<i class="fa fa-check text-success"></i> <span class="text-success">' . adminFunctions::t("file_report_manage_bulk_remove_file_deleted", "Success: File deleted") . '</span> - ' . adminFunctions::makeSafe($fileUrlItem);
                }
                else {
                    $deleteRs[] = '<i class="fa fa-close text-danger"></i> <span class="text-danger">' . adminFunctions::t("file_report_manage_bulk_remove_file_failed", "Error: Failed removing file") . '</span> - ' . adminFunctions::makeSafe($fileUrlItem);
                }
            }
        }
    }
}

// page header
include_once('_header.inc.php');
?>

<div class="right_col" role="main">
    <div class="">
        <div class="page-title">
            <div class="title_left">
                <h3>Bulk Remove</h3>
            </div>
        </div>
        <div class="clearfix"></div>

<?php
if (COUNT($deleteRs)) {
    echo "<div class='row'><div class='col-md-12 col-sm-12 col-xs-12'><div class='x_panel'>";
    echo "<div class='x_title'><h2>Result</h2><div class='clearfix'></div></div>";
    echo "<div class='x_content'>" . implode("<br/>", $deleteRs) . '</div>';
    echo "</div></div></div>";
}
?>
<?php echo adminFunctions::compileNotifications(); ?>

        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <form action="file_report_manage_bulk_remove.php" method="POST" id="demo-form2" class="form-horizontal form-label-left">
                    <div class="x_panel">
                        <div class="x_title">
                            <h2>File Details</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <p>To bulk remove files, enter the urls in the textarea below. On submission these urls will be deactivated and files removed. Urls should include the site domain name and start with <?php echo _CONFIG_SITE_PROTOCOL; ?>. Only <strong>1 url per line</strong> within the textarea.</p>
                            <br/>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="file_urls">Download Urls:</span>
                                </label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <textarea name="file_urls" id="file_urls" class="form-control" placeholder="<?php echo WEB_ROOT; ?>/xyz&#10;..." required="required" style="height: 200px;"><?php echo adminFunctions::makeSafe($file_urls); ?></textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="confirm_password">Your Account Password:</span>
                                </label>
                                <div class="col-md-3 col-sm-3 col-xs-12">
                                    <input id="confirm_password" name="confirm_password" class="form-control" required="required" type="password"/>
                                    <p class="text-muted">For security reasons please confirm your account password.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="x_panel">
                        <div class="x_title">
                            <h2>Other Options</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <br/>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="removal_type">Removal Reason:</span>
                                </label>
                                <div class="col-md-3 col-sm-3 col-xs-12">
                                    <select name="removal_type" id="removal_type" class="form-control">
                                        <option value="3" <?php echo (int) $removal_type == 3 ? 'SELECTED' : ''; ?>>General</option>
                                        <option value="4" <?php echo (int) $removal_type == 4 ? 'SELECTED' : ''; ?>>Copyright Breach (DMCA)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="admin_notes">Notes:</span>
                                </label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <textarea name="admin_notes" id="admin_notes" class="form-control"><?php echo adminFunctions::makeSafe($admin_notes); ?></textarea>
                                </div>
                            </div>

                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                    <button type="submit" class="btn btn-default" onClick="window.location = 'file_report_manage.php';">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Confirm Removal</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input name="submitted" type="hidden" value="1"/>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>