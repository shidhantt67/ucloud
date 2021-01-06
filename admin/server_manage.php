<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'File Servers');
define('ADMIN_SELECTED_PAGE', 'file_servers');
define('ADMIN_SELECTED_SUB_PAGE', 'server_manage');

// includes and security
include_once('_local_auth.inc.php');

// update storage stats
if (isset($_REQUEST['r'])) {
    file::updateFileServerStorageStats(null, true);
}

// handle upload/download toggles
if (isset($_REQUEST['toggle_uploads'])) {
    if (_CONFIG_DEMO_MODE == true) {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    else {
        $configValue = 'yes';
        if (SITE_CONFIG_UPLOADS_BLOCK_ALL == 'yes') {
            $configValue = 'no';
        }
        $db->query('UPDATE site_config SET config_value = :configValue WHERE config_key = \'uploads_block_all\' LIMIT 1', array('configValue' => $configValue));

        // redirect to self
        coreFunctions::redirect(ADMIN_WEB_ROOT . '/server_manage.php?toggle_uploadss=' . $configValue);
    }
}
elseif (isset($_REQUEST['toggle_uploadss'])) {
    adminFunctions::setSuccess("All uploading has been " . ($_REQUEST['toggle_uploadss'] == 'yes' ? 'disabled' : 'enabled') . ".");
}
elseif (isset($_REQUEST['toggle_downloads'])) {
    if (_CONFIG_DEMO_MODE == true) {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    else {
        $configValue = 'yes';
        if (SITE_CONFIG_DOWNLOADS_BLOCK_ALL == 'yes') {
            $configValue = 'no';
        }
        $db->query('UPDATE site_config SET config_value = :configValue WHERE config_key = \'downloads_block_all\' LIMIT 1', array('configValue' => $configValue));

        // redirect to self
        coreFunctions::redirect(ADMIN_WEB_ROOT . '/server_manage.php?toggle_downloadss=' . $configValue);
    }
}
elseif (isset($_REQUEST['toggle_downloadss'])) {
    adminFunctions::setSuccess("All downloading has been " . ($_REQUEST['toggle_downloadss'] == 'yes' ? 'disabled' : 'enabled') . ".");
}

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    gFileServerId = null;
    gEditFileServerId = null;
    gTestFileServerId = null;
    gDeleteFileServerId = null;
    $(document).ready(function () {
        // datatable
        oTable = $('#fileServerTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/server_manage.ajax.php',
            "iDisplayLength": 25,
            "aaSorting": [[1, "asc"]],
            "aoColumns": [
                {bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide"},
                {sName: 'server_label', sWidth: '25%'},
                {sName: 'server_type', sWidth: '10%', sClass: "center adminResponsiveHide"},
                {sName: 'total_space_used', sWidth: '10%', sClass: "center"},
                {sName: 'total_files', sWidth: '10%', sClass: "center"},
                {sName: 'status', sWidth: '10%', sClass: "center adminResponsiveHide"},
                {bSortable: false, sWidth: '20%', sClass: "center adminResponsiveHide"}
            ],
            "fnServerData": function (sSource, aoData, fnCallback) {
                aoData.push({"name": "filterText", "value": $('#filterText').val()});
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": sSource,
                    "data": aoData,
                    "success": fnCallback
                });
            },
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

        // update custom filter
        $('.dataTables_filter').html($('#customFilter').html());

<?php if (isset($_REQUEST['add'])): ?>
            addServerForm();
<?php endif; ?>
    });

    function confirmRemoveFileServer(serverId, serverName, activeFiles)
    {
        $('#pleaseWait').hide();
        $('#confirmText').show();
        $('#serverNameLabel').html(serverName);
        $('#serverActiveFilesLabel').html(activeFiles);
        $('#confirmDelete').modal("show");
        gDeleteFileServerId = serverId;
    }

    function removeFileServer()
    {
        $('#confirmText').hide();
        $('#pleaseWait').show();
        $.ajax({
            type: "POST",
            url: "ajax/server_manage_remove.ajax.php",
            data: {serverId: gDeleteFileServerId},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    $('#pleaseWait').hide();
                    $('#confirmText').show();
                    showError(json.msg);
                } else
                {
                    showSuccess(json.msg);
                    reloadTable();
                    $("#confirmDelete").modal("hide");
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                $('#pleaseWait').hide();
                $('#confirmText').show();
                showError(XMLHttpRequest.responseText);
            }
        });
    }

    function loadAddServerForm()
    {
        $('#addFileServerForm').html('Loading...');
        $('#editFileServerForm').html('');
        $.ajax({
            type: "POST",
            url: "ajax/server_manage_add_form.ajax.php",
            data: {},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    $('#addFileServerForm').html(json.msg);
                } else
                {
                    $('#addFileServerForm').html(json.html);
                    showHideFTPElements();
                    updateUrlParams();
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                $('#addFileServerForm').html(XMLHttpRequest.responseText);
            }
        });
    }

    function loadEditServerForm()
    {
        $('#addFileServerForm').html('Loading...');
        $('#editFileServerForm').html('Loading...');
        $.ajax({
            type: "POST",
            url: "ajax/server_manage_add_form.ajax.php",
            data: {gEditFileServerId: gEditFileServerId},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    $('#editFileServerForm').html(json.msg);
                } else
                {
                    $('#editFileServerForm').html(json.html);
                    showHideFTPElements();
                    updateUrlParams();
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                $('#editFileServerForm').html(XMLHttpRequest.responseText);
            }
        });
    }

    function loadFtpTestServerForm()
    {
        showBasicModal('<iframe src="server_manage_test_ftp.php?serverId=' + gTestFileServerId + '" style="background: url(\'assets/images/spinner.gif\') no-repeat center center;" height="100%" width="100%" frameborder="0" scrolling="auto">Loading...</iframe>', 'Test FTP Server');
    }

    function loadDirectTestServerForm()
    {
        showBasicModal('<iframe src="server_manage_test_direct.php?serverId=' + gTestFileServerId + '" style="background: url(\'assets/images/spinner.gif\') no-repeat center center;" height="100%" width="100%" frameborder="0" scrolling="auto">Loading...</iframe>', 'Test Server');
    }

    function loadFlysystemTestServerForm()
    {
        showBasicModal('<iframe src="server_manage_test_flysystem.php?serverId=' + gTestFileServerId + '" style="background: url(\'assets/images/spinner.gif\') no-repeat center center;" height="100%" width="100%" frameborder="0" scrolling="auto">Loading...</iframe>', 'Test Storage Server');
    }

    function processAddFileServer()
    {

        // get data
        ftp_host = '';
        ftp_port = '';
        ftp_username = '';
        ftp_password = '';
        ftp_server_type = '';
        ftp_passive_mode = '';
        file_server_domain_name = '';
        script_root_path = '';
        script_path = '';
        server_label = $('#server_label').val();
        status_id = $('#status_id').val();
        server_type = $('#server_type').val();
        max_storage_space = $('#max_storage_space').val();
        server_priority = $('#server_priority').val();
        storage_path = '';
        route_via_main_site = 0;
        dlAccelerator = 0;
        cdn_url = $('#cdn_url').val();

        file_server_direct_ip_address = '';
        file_server_direct_ssh_port = 22;
        file_server_direct_ssh_username = '';
        file_server_direct_ssh_password = '';
        file_server_direct_server_path_to_storage = '';
        file_server_download_proto = '';

        if (server_type == 'ftp')
        {
            ftp_host = $('#ftp_host').val();
            ftp_port = $('#ftp_port').val();
            ftp_username = $('#ftp_username').val();
            ftp_password = $('#ftp_password').val();
            storage_path = $('#ftp_storage_path').val();
            ftp_server_type = $('#ftp_server_type').val();
            ftp_passive_mode = $('#ftp_passive_mode').val();
        } else if (server_type == 'direct')
        {
            file_server_domain_name = $('#file_server_domain_name').val();
            script_root_path = $('#direct_script_root_path').val();
            script_path = $('#script_path').val();
            storage_path = $('#direct_storage_path').val();
            route_via_main_site = $('#route_via_main_site').val();
            dlAccelerator = $('#dlAccelerator2').val();

            file_server_direct_ip_address = $('#file_server_direct_ip_address').val();
            file_server_direct_ssh_port = $('#file_server_direct_ssh_port').val();
            file_server_direct_ssh_username = $('#file_server_direct_ssh_username').val();
            file_server_direct_ssh_password = $('#file_server_direct_ssh_password').val();
            file_server_direct_server_path_to_storage = $('#file_server_direct_server_path_to_storage').val();
            file_server_download_proto = $('#file_server_download_proto').val();
        } else if (server_type == 'local')
        {
            script_root_path = $('#local_script_root_path').val();
            storage_path = $('#local_storage_path').val();
            dlAccelerator = $('#dlAccelerator1').val();

            file_server_direct_ip_address = $('#file_server_direct_ip_address_2').val();
            file_server_direct_ssh_port = $('#file_server_direct_ssh_port_2').val();
            file_server_direct_ssh_username = $('#file_server_direct_ssh_username_2').val();
            file_server_direct_ssh_password = $('#file_server_direct_ssh_password_2').val();
            file_server_direct_server_path_to_storage = $('#file_server_direct_server_path_to_storage_2').val();
        }
        existing_file_server_id = gEditFileServerId;

        // get any flysystem fields
        flysystem_config = {};
        $('.flysystem-field').each(function () {
            flysystem_config[$(this).attr('id')] = $(this).val();
        });

        $.ajax({
            type: "POST",
            url: "ajax/server_manage_add_process.ajax.php",
            data: {existing_file_server_id: existing_file_server_id, 
                flysystem_config: flysystem_config, 
                route_via_main_site: route_via_main_site, 
                file_server_domain_name: file_server_domain_name, 
                script_path: script_path, 
                server_label: server_label, 
                status_id: status_id, 
                server_type: server_type, 
                storage_path: storage_path, 
                ftp_host: ftp_host, 
                ftp_port: ftp_port, 
                ftp_username: ftp_username, 
                ftp_password: ftp_password, 
                ftp_server_type: ftp_server_type, 
                ftp_passive_mode: ftp_passive_mode, 
                max_storage_space: max_storage_space, 
                server_priority: server_priority, 
                dlAccelerator: dlAccelerator, 
                file_server_direct_ip_address: file_server_direct_ip_address, 
                file_server_direct_ssh_port: file_server_direct_ssh_port, 
                file_server_direct_ssh_username: file_server_direct_ssh_username, 
                file_server_direct_ssh_password: file_server_direct_ssh_password, 
                file_server_direct_server_path_to_storage: file_server_direct_server_path_to_storage, 
                file_server_download_proto: file_server_download_proto, 
                cdn_url: cdn_url,
                script_root_path: script_root_path
            },
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    showError(json.msg, 'popupMessageContainer');
                } else
                {
                    showSuccess(json.msg);
                    reloadTable();
                    $("#addServerForm").modal("hide");
                    $("#editServerForm").modal("hide");
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });

    }

    function addServerForm()
    {
        gEditFileServerId = null;
        loadAddServerForm();
        $('#addServerForm').modal("show");
    }

    function editServerForm(fileServerId)
    {
        gEditFileServerId = fileServerId;
        loadEditServerForm();
        $('#editServerForm').modal("show");
    }

    function reloadTable()
    {
        oTable.fnDraw(false);
    }

    function showHideFTPElements()
    {
        hideAllElements();
        serverType = $('#server_type').val();
        if (serverType == 'ftp')
        {
            $('.ftpElements').show();
        } else if (serverType == 'direct')
        {
            $('.directElements').show();
        } else if (serverType == 'local')
        {
            $('.localElements').show();
        } else
        {
            // flysystem adapters
            extraFields = $('#server_type option:selected').attr('data-fields');
            renderFlysystemOptions($('#server_type').val(), extraFields);
        }
    }

    function renderFlysystemOptions(flysystemAdapter, extraFields)
    {
        hideAllElements();

        // create form
        html = '';
        extraFieldsObj = jQuery.parseJSON(extraFields);
        for (i in extraFieldsObj)
        {
            html += '<div class="form-group">';
            html += '    <label class="control-label col-md-3 col-sm-3 col-xs-12">' + extraFieldsObj[i]['label'] + ':</label>';
            html += '    <div class="col-md-5 col-sm-5 col-xs-12">';
            if (extraFieldsObj[i]['type'] == 'select')
            {
                html += '        <select name="' + flysystemAdapter + '_' + i + '" id="' + flysystemAdapter + '_' + i + '" class="form-control flysystem-field">';
                for (o in extraFieldsObj[i]['option_values'])
                {
                    html += '        <option value="' + o + '"';
                    if (extraFieldsObj[i]['default'] == o)
                    {
                        html += ' SELECTED';
                    }
                    html += '>' + extraFieldsObj[i]['option_values'][o] + '</option>';
                }
                html += '        </select>';
            } else
            {
                html += '        <input name="' + flysystemAdapter + '_' + i + '" id="' + flysystemAdapter + '_' + i + '" type="text" value="' + extraFieldsObj[i]['default'] + '" class="form-control flysystem-field"/>';
            }
            html += '    </div>';
            html += '</div>';
        }

        $('.flysystemWrapper').html(html);
        $('.flysystemWrapper').show();
    }

    function hideAllElements()
    {
        $('.directElements').hide();
        $('.ftpElements').hide();
        $('.localElements').hide();
        $('.flysystemWrapper').hide();
    }

    function testFtpFileServer(serverId)
    {
        gTestFileServerId = serverId;
        $('#testServerForm').modal("show");
        loadFtpTestServerForm();
    }

    function testDirectFileServer(serverId)
    {
        gTestFileServerId = serverId;
        $('#testServerForm').modal("show");
        loadDirectTestServerForm();
    }

    function testFlysystemFileServer(serverId)
    {
        gTestFileServerId = serverId;
        $('#testServerForm').modal("show");
        loadFlysystemTestServerForm();
    }

    function updateUrlParams()
    {
        // file server domain name
        SITE_HOST = $('#file_server_domain_name').val();
        SITE_HOST = SITE_HOST.replace(/\s/g, "");
        $('#file_server_domain_name').val(SITE_HOST);
        if (SITE_HOST.length == 0)
        {
            SITE_HOST = '';
        }

        // rewrite base
        REWRITE_BASE = $('#script_path').val();
        REWRITE_BASE = REWRITE_BASE.replace(/\s/g, "");
        $('#script_path').val(REWRITE_BASE);
        if (REWRITE_BASE.length == 0)
        {
            REWRITE_BASE = '/';
        }

        $('#configLink').attr('href', 'server_manage_direct_get_config_file.php?fileName=_config.inc.php&REWRITE_BASE=' + REWRITE_BASE + '&SITE_HOST=' + SITE_HOST);
        $('#htaccessLink').attr('href', 'server_manage_direct_get_config_file.php?fileName=.htaccess&REWRITE_BASE=' + REWRITE_BASE);
    }

    function toggleAllUploads()
    {
        if (confirm("Are you sure you want to disable all uploads on your site? This will block any new uploads from starting for non-admin users."))
        {
            window.location = 'server_manage.php?toggle_uploads=1';
        }

        return false;
    }

    function toggleAllDownloads()
    {
        if (confirm("Are you sure you want to disable all downloads on your site? This will block any new downloads from starting for non-admin users."))
        {
            window.location = 'server_manage.php?toggle_downloads=1';
        }

        return false;
    }
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
                        <h2>Manage Servers</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <table id="fileServerTable" class="table table-striped table-only-border dtLoading bulk_action">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th><?php echo UCWords(adminFunctions::t("server_label", "server label")); ?></th>
                                    <th><?php echo UCWords(adminFunctions::t("server_type", "server type")); ?></th>
                                    <th><?php echo UCWords(adminFunctions::t("space_used", "space used")); ?> *</th>
                                    <th><?php echo UCWords(adminFunctions::t("total_files", "total files")); ?> *</th>
                                    <th><?php echo UCWords(adminFunctions::t("status", "status")); ?></th>
                                    <th><?php echo UCWords(adminFunctions::t("action", "action")); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="20"><?php echo adminFunctions::t('admin_loading_data', 'Loading data...'); ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="clear"><br/></div>
                        <p>Note: Active servers above might not necessarily be used for new uploads. You can set which specific server is used within the <a href="setting_manage.php?filterByGroup=File+Uploads">site configuration</a> section.</p>
                        <p>* The above storage and total file data is refreshed every 5 minutes, so the above values may be slightly out of date. Stats are for active or trash items, pending deletes in the <a href="file_manage_action_queue.php">file action queue</a> are not included. You can force a refresh by <a href="server_manage.php?r=1">click here</a>. (this can take some time!)</p>
                    </div>
                </div>

                <div class="x_panel">
                    <div class="btn-group pull-right">
                        <a href="#" type="button" class="btn btn-<?php echo (SITE_CONFIG_UPLOADS_BLOCK_ALL == 'yes') ? 'danger' : 'default'; ?> buttonmobileAdminResponsiveHide" onclick="toggleAllUploads(); return false;"><?php echo (SITE_CONFIG_UPLOADS_BLOCK_ALL == 'yes') ? 'Enable' : 'Disable'; ?> All Site Uploads</a>&nbsp;
                        <a href="#" type="button" class="btn btn-<?php echo (SITE_CONFIG_DOWNLOADS_BLOCK_ALL == 'yes') ? 'danger' : 'default'; ?> buttonmobileAdminResponsiveHide" onclick="toggleAllDownloads(); return false;"><?php echo (SITE_CONFIG_DOWNLOADS_BLOCK_ALL == 'yes') ? 'Enable' : 'Disable'; ?> All Site Downloads</a>
                    </div>
<?php if ($Auth->hasAccessLevel(20)): ?>
                        <div class="btn-group pull-left">
                            <a href="#" type="button" class="btn btn-primary" onClick="addServerForm(); return false;">Add File Server</a>
                        </div>
<?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="customFilter" id="customFilter" style="display: none;">
    <label>
        Filter Results:
        <input name="filterText" id="filterText" type="text" class="form-control" onKeyUp="reloadTable(); return false;" style="width: 160px;"/>
    </label>
</div>

<div id="addServerForm" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button></div>
            <div class="modal-body" id="addFileServerForm"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onClick="processAddFileServer(); return false;">Add File Server</button>
            </div>
        </div>
    </div>
</div>

<div id="editServerForm" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button></div>
            <div class="modal-body" id="editFileServerForm"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onClick="processAddFileServer(); return false;">Update Server</button>
            </div>
        </div>
    </div>
</div>

<div id="confirmDelete" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button></div>
            <div class="modal-body" id="removeFileServerForm">
                <div class="x_panel">
                    <span id="confirmText">
                        <div class="x_title">
                            <h2>Confirm File Server Removal</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <p>Are you sure you want to remove the file server called '<span id="serverNameLabel" style="font-weight: bold;"></span>'?</p>
                            <p>There are <span id="serverActiveFilesLabel"></span> file(s) on this server. Any active files will be removed and any historic data will be lost. This includes the statistics on these and previously expired files.</p>
                            <p>Once confirmed, this action can not be reversed.</p>
                            <p>Note: If there are a lot of files on this file server, this process may take a long time to complete.</p>
                        </div>
                    </span>
                    <span id="pleaseWait">
                        Removing, please wait...
                    </span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onClick="removeFileServer(); return false;">Confirm File Removal</button>
            </div>
        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>