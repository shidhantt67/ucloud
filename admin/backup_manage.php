<?php
define('ADMIN_PAGE_TITLE', 'Manage Backups');
define('ADMIN_SELECTED_PAGE', 'configuration');
include_once('_local_auth.inc.php');

// setup the backup object for later
$backup = new backup();
$backupPath = $backup->getBackupPath();

// handle submissions
if(isset($_REQUEST['cd']))
{
    // validate submission
    if(_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    else
    {
        $rs = $backup->backupDatabase();
        if(!$rs)
        {
            adminFunctions::setError("Failed to create database backup, please try again later.");
        }
        else
        {
            coreFunctions::redirect(ADMIN_WEB_ROOT . '/backup_manage.php?cds=1');
        }
    }
}
elseif(isset($_REQUEST['cds']))
{
    adminFunctions::setSuccess("Database backup created.");
}
elseif(isset($_REQUEST['cc']))
{
    // validate submission
    if(_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    else
    {
        $rs = $backup->backupCode();
        if(!$rs)
        {
            adminFunctions::setError("Failed to create code backup, please try again later.");
        }
        else
        {
            coreFunctions::redirect(ADMIN_WEB_ROOT . '/backup_manage.php?ccs=1');
        }
    }
}
elseif(isset($_REQUEST['ccs']))
{
    adminFunctions::setSuccess("Code backup created.");
}
elseif(isset($_REQUEST['delete_path']))
{
    // validate submission
    if(_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    else
    {
        // get params
        $path = $_REQUEST['delete_path'];
        $path = str_replace(array('..'), '', $path);
        $fullBackupPath = $backupPath . '/' . $path;

        // some security
        $fullBackupPath = realpath($fullBackupPath);
        if($backupPath != substr($fullBackupPath, 0, strlen($backupPath)))
        {
            exit;
        }

        $rs = @unlink($fullBackupPath);
        if(!$rs)
        {
            adminFunctions::setError("Failed to delete backup file, please try again later.");
        }
        else
        {
            coreFunctions::redirect(ADMIN_WEB_ROOT . '/backup_manage.php?cdelete_path=1');
        }
    }
}
elseif(isset($_REQUEST['cdelete_path']))
{
    adminFunctions::setSuccess("Backup file removed.");
}

// get list of backups
$backupFiles = array();
if($handle = opendir($backupPath))
{
    // loop contents
    while(false !== ($entry = readdir($handle)))
    {
        $filePath = $backupPath . '/' . $entry;
        if((substr($entry, 0, 1) != '.') && (is_file($filePath)))
        {
            $created = coreFunctions::formatDate(filemtime($filePath));
            $filesize = coreFunctions::formatSize(filesize($filePath));
            $backupFiles[] = array('filename' => $entry, 'created' => $created, 'filesize' => $filesize);
        }
    }
    closedir($handle);
    asort($backupFiles);
}

include_once('_header.inc.php');
?>
<script>
    function createDatabaseBackup()
    {
        if (confirm("Are you sure you want to create a database backup?\n\nThis may take some time to complete, depending on the size of your database. Note that it will not backup 'disposible' data such as the session table data."))
        {
            $('#createDatabaseBackupButton').removeAttr('onClick');
            $('#createDatabaseBackupButton').removeAttr('href');
            $('#createCodeBackupButton').hide();
            $('#createDatabaseBackupButton').removeClass("blue");
            $('#createDatabaseBackupButton').addClass("grey");
            $('#createDatabaseBackupButton').text('Creating database backup, please wait...');
            window.location = 'backup_manage.php?cd=1';

            return true;
        }

        return false;
    }

    function createCodeBackup()
    {
        if (confirm("Are you sure you want to create a code backup?\n\nThis process zips up all your site code, any custom changes, config files and other script files you may have added. It will NOT backup any user uploaded files (within files/), you will need cover this via another process.\n\nThis backup may take some time to complete, depending on the size of your site."))
        {
            $('#createDatabaseBackupButton').removeAttr('onClick');
            $('#createDatabaseBackupButton').removeAttr('href');
            $('#createCodeBackupButton').hide();
            $('#createDatabaseBackupButton').removeClass("blue");
            $('#createDatabaseBackupButton').addClass("grey");
            $('#createDatabaseBackupButton').text('Creating code backup, please wait...');
            window.location = 'backup_manage.php?cc=1';

            return true;
        }

        return false;
    }

    function confirmDeleteBackup()
    {
        if (confirm("Are you sure you want to delete this backup from storage?"))
        {
            return true;
        }

        return false;
    }

    $(document).ready(function () {
        // datatable
        oTable = $('#backupTable').dataTable({
            "sPaginationType": "full_numbers",
            "bProcessing": true,
            "iDisplayLength": 25,
            "aaSorting": [[4, "desc"]],
            "aoColumns": [
                {bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide"},
                {sName: 'backup_name'},
                {sName: 'backup_type', sWidth: '10%', sClass: "center adminResponsiveHide"},
                {sName: 'backup_size', sWidth: '10%', sClass: "center adminResponsiveHide"},
                {sName: 'backup_created', sWidth: '16%', sClass: "center"},
                {bSortable: false, sWidth: '14%', sClass: "center adminResponsiveHide"}
            ],
            "fnDrawCallback": function (oSettings) {
                postDatatableRender();
            },
            "oLanguage": {
                "sEmptyTable": "There are no files in the current filters."
            },
            dom: "lBfrtip",
            buttons: [
              {
                extend: "copy",
                className: "btn-sm"
              },
              {
                extend: "csv",
                className: "btn-sm"
              },
              {
                extend: "excel",
                className: "btn-sm"
              },
              {
                extend: "pdfHtml5",
                className: "btn-sm"
              },
              {
                extend: "print",
                className: "btn-sm"
              }
            ]
        });
    });
</script>

<!-- page content -->
<div class="right_col" role="main">
    <div class="">
        <div class="page-title">
            <div class="title_left">
                <h3><?php echo ADMIN_PAGE_TITLE; ?></h3>
            </div>
        </div>
        <div class="clearfix"></div>

        <?php echo adminFunctions::compileNotifications(); ?>
        
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>Backups</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <p>Use this page to generate and download both database and site code backups. It is recommended you do this before any changes or upgrades to your site. It is not possible to backup any user uploaded files (within /files/), this would be better managed directly on the server using rsync or similar.</p>
                        <p>Note: All code backups exclude the /files/, /core/cache/ &amp; /core/logs/ folders. Database backups will exclude the contents of any 'disposable' tables such as 'sessions'.</p>
                        <br/>
                        <?php
                        if(COUNT($backupFiles) == 0)
                        {
                            echo '<strong>- Could not find any backups in the backups folder - ' . $backupPath. '</strong>';
                        }
                        else
                        {
                        ?>
                        <table id="backupTable" class="table table-striped table-only-border bulk_action">
                            <thead><tr><th></th><th>Backup Name:</th><th>Type:</th><th>Size:</th><th>Created:</th><th>Options</th></tr></thead>
                            <tbody>
                                <?php
                                foreach($backupFiles AS $backupFile)
                                {
                                    $type = 'Code';
                                    if(substr($backupFile['filename'], 0, 8) == 'database')
                                    {
                                        $type = 'Database';
                                    }
                                    $icon = 'assets/images/icons/backup/16px/' . strtolower($type) . '.png';

                                    echo '<tr>';
                                    echo '<td><img src="' . $icon . '" alt="' . adminFunctions::makeSafe($type) . '" style="width: 16px; height: 16px;"/></td>';
                                    echo '<td><a href="backup_download.php?path=' . adminFunctions::makeSafe($backupFile['filename']) . '">' . adminFunctions::makeSafe($backupFile['filename']) . '</a></td>';
                                    echo '<td>' . adminFunctions::makeSafe($type) . '</td>';
                                    echo '<td>' . adminFunctions::makeSafe($backupFile['filesize']) . '</td>';
                                    echo '<td>' . adminFunctions::makeSafe($backupFile['created']) . '</td>';
                                    echo '<td><div class="btn-group">';
                                    echo '<a class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="remove" href="backup_manage.php?delete_path=' . adminFunctions::makeSafe($backupFile['filename']) . '" onClick="return confirmDeleteBackup();"><span class="fa fa-trash text-danger" aria-hidden="true"></span></a>';
                                    echo '<a class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="download" href="backup_download.php?path=' . adminFunctions::makeSafe($backupFile['filename']) . '"><span class="fa fa-download" aria-hidden="true"></span></a>';
                                    echo '</div></td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                        <?php
                            echo '<br/><br/><br/>';
                            echo '<p>Backup Storage Path:&nbsp;&nbsp;' . $backupPath.'</p>';
                        }
                        ?>
                    </div>
                </div>

                <div class="x_panel">
                    <a href="#" onClick="return createDatabaseBackup();" id="createDatabaseBackupButton" class="btn btn-primary"><?php echo adminFunctions::t('backup_manage_create_database', 'Create Database Backup'); ?></a>&nbsp;
                    <a href="#" onClick="return createCodeBackup();" id="createCodeBackupButton" class="btn btn-primary"><?php echo adminFunctions::t('backup_manage_create_code_backup', 'Create Code Backup'); ?></a>
                </div>

            </div>
        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>