<?php
// initial constants
define('ADMIN_SELECTED_PAGE', 'plugins');
define('ADMIN_SELECTED_SUB_PAGE', 'plugin_manage');

// includes and security
include_once('../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// load plugin class, we need to do it like this as the plugin may not be enabled
$classPath = PLUGIN_DIRECTORY_ROOT . 'fileimport/pluginFileimport.class.php';
$pluginClassName = 'PluginFileimport';
include_once($classPath);
$pluginObj = new $pluginClassName();
$basePath = $pluginObj->getLowestWritableBasePath();
if (_CONFIG_DEMO_MODE == true)
{
    $basePath = DOC_ROOT;
}

// handle submissions
$startImport = false;
if(isset($_REQUEST['submitted']))
{
    $import_path = trim($_REQUEST['import_path']);
    $import_account = trim($_REQUEST['import_account']);
    $import_folder = (int)$_REQUEST['import_folder'];
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    elseif(strlen($import_path) == 0)
    {
        adminFunctions::setError('Please set the import path.');
    }
    elseif($import_path == DIRECTORY_SEPARATOR)
    {
        adminFunctions::setError('The import path can not be root.');
    }
    elseif(!is_readable($import_path))
    {
        adminFunctions::setError('The import path is not readable, please move the files to a readable directory and try again.');
    }
    elseif(strlen($import_account) == 0)
    {
        adminFunctions::setError('Please set the account to import the files into.');
    }
    else
    {
        // lookup the account
        $user = UserPeer::loadUserByUsername($import_account);
        if(!$user)
        {
            adminFunctions::setError('User account not found, please check and try again');
        }
    }
    
    // ok to run the import
    if(!adminFunctions::isErrors())
    {
        $startImport = true;
    }
}

define('ADMIN_PAGE_TITLE', 'Import Files');

// page header
include_once(ADMIN_ROOT . '/_header.inc.php');
?>

<link rel="stylesheet" href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/jstree/themes/default/style.css" />

<div class="right_col" role="main">
    <div class="">
        <div class="page-title">
            <div class="title_left">
                <h3>Import Files</h3>
            </div>
        </div>
        <div class="clearfix"></div>

        <?php echo adminFunctions::compileNotifications(); ?>

        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>Bulk Import</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <?php if($startImport == false): ?>
                        <form method="POST" action="<?php echo PLUGIN_WEB_ROOT; ?>/fileimport/admin/settings.php" class="form-horizontal form-label-left" onSubmit="return confirmSubmission();">
                            <div class="" role="tabpanel" data-example-id="togglable-tabs">
                                <ul id="myTab" class="nav nav-tabs bar_tabs" role="tablist">
                                    <li role="presentation" class="active"><a href="#tab_content1" id="home-tab" role="tab" data-toggle="tab" aria-expanded="true">Import Tool</a>
                                    </li>
                                    <li role="presentation" class=""><a href="#tab_content2" role="tab" id="profile-tab" data-toggle="tab" aria-expanded="false">Manual Import</a>
                                    </li>
                                </ul>
                                <div id="myTabContent" class="tab-content">
                                    <div role="tabpanel" class="tab-pane fade active in" id="tab_content1" aria-labelledby="home-tab">
                                        <p>Use the form below to bulk import files into the current server. If you require files to be imported onto an external file server, please load the admin area directly on that server and open this page.</p>
                                        <p>Upload the files into a sub-folder on your server and select it below.</p>
                                        <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12" for="import_path">Select Path to Import <span class="required">*</span>
                                            </label>
                                            <div class="col-md-6 col-sm-6 col-xs-12">
                                                <input type="text" id="import_path" name="import_path" required="required" class="form-control col-md-7 col-xs-12" placeholder="select below..." value="<?php echo isset($_REQUEST['import_path'])?adminFunctions::makeSafe($_REQUEST['import_path']):''; ?>" style="margin-bottom: 6px;"/>
                                                <div id="import_folder_listing"></div>
                                                <p class="text-muted" style="margin-top: 6px;">Path to script installation: <?php echo DOC_ROOT; ?></p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12" for="import_account">Import into Account <span class="required">*</span>
                                            </label>
                                            <div class="col-md-6 col-sm-6 col-xs-12">
                                                <input type="text" id="import_account" name="import_account" required="required" class="form-control col-md-7 col-xs-12 txt-auto" placeholder="account username..." value="<?php echo isset($_REQUEST['import_account'])?adminFunctions::makeSafe($_REQUEST['import_account']):''; ?>" autocomplete="off"/>
                                                <p class="text-muted">The account username to import the files into. Start typing to find the account.</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12" for="import_folder">Import into Account Folder <span class="required">*</span>
                                            </label>
                                            <div class="col-md-6 col-sm-6 col-xs-12">
                                                <span id="import_folder_wrapper">
                                                    <select id="import_folder" name="import_folder" class="form-control col-md-7 col-xs-12" disabled="disabled">
                                                        <option value="">- select account above first -</option>
                                                    </select>
                                                </span>
                                                <p class="text-muted">Updated with the folder list of the above users account. Files will be placed directly in this folder. <a href="#" onClick="reloadUserFolderListing(); return false;">(reload)</a></p>
                                            </div>
                                        </div>

                                        <div class="ln_solid"></div>
                                        <div class="form-group">
                                            <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                                <button type="submit" class="btn btn-primary">Import Files</button>
                                            </div>
                                        </div>

                                    </div>
                                    <div role="tabpanel" class="tab-pane fade" id="tab_content2" aria-labelledby="profile-tab">
                                        <p>The import script enables you to migrate your existing 'offline' files into the script. It can be run on your main server aswell as file servers.</p>
                                        <p>First download the <a href="download_import.php">import.php script</a>. This can also be found in:</p>
                                        <code>
                                            <?php echo DOC_ROOT; ?>/plugins/fileimport/admin/import.php.txt (rename to import.php)
                                        </code>
                                        <br/><br/>
                                        <p>Populate the constants in [[[SQUARE_BRACKET]]] at the top of import.php. i.e. FILE_IMPORT_ACCOUNT_NAME, FILE_IMPORT_PATH, FILE_IMPORT_ACCOUNT_START_FOLDER</p>
                                        <p>Save and upload the file, either to the YetiShare root of your main site or the YetiShare root of a file server. The YetiShare root path is the same location as this file: _config.inc.php</p>
                                        <p>Using FTP or WinSCP, upload or move all the files you want to import to a folder on that server. This should be outside of the YetiShare installation (FILE_IMPORT_PATH in import.php).</p>
                                        <p>Execute the script on the command line (via SSH) using PHP. Like this:</p>
                                        <code>
                                            php <?php echo DOC_ROOT; ?>/import.php
                                        </code>
                                        <br/><br/>
                                        <p>The import will complete with progress onscreen. Files will not be moved, they'll be copied into YetiShare so you will need to delete them from the temporary folder after the import.</p>
                                        <p>Once the import is complete, ensure you remove the import.php script from your YetiShare root.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <input type="hidden" name="submitted" value="1"/>
                        </form>
                        <?php else: ?>
                            <p>Importing files from <?php echo adminFunctions::makeSafe($import_path); ?>, please check below for progress. Note that this process can take some time.</p>
                            <iframe src="<?php echo PLUGIN_WEB_ROOT; ?>/fileimport/admin/_process_file_import.iframe.php?<?php echo http_build_query(array('import_path' => $import_path, 'import_account' => $import_account, 'import_folder' => $import_folder)); ?>" style="border: 0px; width: 100%; height: 360px;"></iframe>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/jstree/jstree.min.js"></script>
<script>
    var firstLoad = true;
    $(function () {
        $('#import_folder_listing').jstree({
            'core': {
                'data': {
                    'url': '<?php echo PLUGIN_WEB_ROOT; ?>/fileimport/admin/ajax/folder_listing.ajax.php?operation=get_node',
                    'data': function (node) {
                        return {'id': node.id};
                    }
                },
                'check_callback': function (o, n, p, i, m) {
                    if (m && m.dnd && m.pos !== 'i') {
                        return false;
                    }
                    return true;
                },
                'force_text': true,
                'themes': {
                    'responsive': false,
                    'variant': 'small',
                    'stripes': true
                }
            },
            'sort': function (a, b) {
                return this.get_type(a) === this.get_type(b) ? (this.get_text(a) > this.get_text(b) ? 1 : -1) : (this.get_type(a) >= this.get_type(b) ? 1 : -1);
            },
            'types': {
                'default': {'icon': 'folder'}
            },
            'plugins': ['state', 'sort', 'types']
        }).on("select_node.jstree", function (e, data) {
            $('#import_path').val('<?php echo $basePath; ?>'+data.node.id);
        });
        
        $('#import_account').typeahead({
            source: function( request, response ) {
                $.ajax({
                    url : '<?php echo ADMIN_WEB_ROOT; ?>/ajax/file_manage_auto_complete.ajax.php',
                    dataType: "json",
                    data: {
                       filterByUser: $("#import_account").val()
                    },
                     success: function( data ) {
                        response( data );
                    }
                });
            },
            minLength: 3,
            delay: 1,
            afterSelect: function() { 
                reloadUserFolderListing();
            }
        });
        
        <?php if(isset($_REQUEST['submitted'])): ?>
            reloadUserFolderListing();
        <?php endif; ?>
    });
    
    function reloadUserFolderListing()
    {
        setElementLoading('#import_folder_wrapper');
        $('#import_folder_wrapper').load('<?php echo ADMIN_WEB_ROOT; ?>/ajax/get_user_folder_select.ajax.php', {'import_account': $("#import_account").val()}, function() {
            // reload selected item
            if(firstLoad == true)
            {
                <?php if((isset($_REQUEST['submitted'])) && (int)$_REQUEST['import_folder']): ?>
                    $('#import_folder').val(<?php echo (int)$_REQUEST['import_folder']; ?>);
                <?php endif; ?>
                firstLoad = false;
            }
        });
    }
    
    function confirmSubmission()
    {
        return confirm("Are you sure you want to import these files? Please confirm the details below. Once submitted, this may take some time to complete.\n\nImport Files: "+$('#import_path').val()+"\nInto Account: "+$('#import_account').val()+"\nInto User Folder: "+$('#import_folder option:selected').text());
    }
</script>

<?php
include_once(ADMIN_ROOT . '/_footer.inc.php');
?>