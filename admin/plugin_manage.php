<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Plugins');
define('ADMIN_SELECTED_PAGE', 'plugins');
define('ADMIN_SELECTED_SUB_PAGE', 'plugin_manage');

// includes and security
include_once('_local_auth.inc.php');

// redirect ucloud users
//if(themeHelper::getCurrentProductType() == 'cloudable') {
//    adminFunctions::redirect(WEB_ROOT . '/themes/cloudable/admin/settings.php');
//}

// error/success messages
if(isset($_REQUEST['sa']))
{
    adminFunctions::setSuccess('Plugin successfully added. To enable the plugin, install it below and configure any plugin specific settings.');
}
elseif(isset($_REQUEST['se']))
{
    adminFunctions::setSuccess('Plugin settings updated.');
}
elseif(isset($_REQUEST['sm']))
{
    // redirect to plugin settings
    if(strlen(trim($_REQUEST['plugin'])))
    {
        adminFunctions::redirect(PLUGIN_WEB_ROOT . '/' . urlencode(trim($_REQUEST['plugin'])) . '/admin/settings.php?id=' . (int) $_REQUEST['id'] . '&sm=' . urlencode($_REQUEST['sm']));
    }
    else
    {
        adminFunctions::setSuccess(urldecode($_REQUEST['sm']));
    }
}
elseif(isset($_REQUEST['d']))
{
    adminFunctions::setSuccess(urldecode($_REQUEST['d']));
}
elseif(isset($_REQUEST['error']))
{
    adminFunctions::setError(urldecode($_REQUEST['error']));
}

// update plugin config cache
pluginHelper::loadPluginConfigurationFiles(true);

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    gPluginId = null;
    $(document).ready(function () {
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/plugin_manage.ajax.php',
            "iDisplayLength": 100,
            "aoColumns": [
                {bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide"},
                {bSortable: false, sName: 'plugin_title'},
                {bSortable: false, sName: 'directory_name', sWidth: '14%', sClass: "adminResponsiveHide"},
                {bSortable: false, sName: 'installed', sWidth: '10%', sClass: "center adminResponsiveHide"},
                {bSortable: false, sName: 'version', sWidth: '10%', sClass: "center adminResponsiveHide"},
                {bSortable: false, sName: 'up_to_date', sWidth: '10%', sClass: "center adminResponsiveHide"},
                {bSortable: false, sWidth: '20%', sClass: "center", sClass: "center"}
            ],
            "fnServerData": function (sSource, aoData, fnCallback, oSettings) {
                aoData.push({"name": "filterText", "value": $('#filterText').val()});
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": sSource,
                    "data": aoData,
                    "success": fnCallback
                });
            },
            "oLanguage": {
                "sEmptyTable": "You have no plugins configured within your site. Go to <a href='<?php echo themeHelper::getCurrentProductUrl(); ?>' target='_blank'><?php echo themeHelper::getCurrentProductName(); ?></a> to see a list of available plugins."
            },
            "fnDrawCallback": function (oSettings) {
                postDatatableRender();
                checkForUpdates();
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
    });

    function reloadTable()
    {
        oTable.fnDraw(false);
    }

    function confirmInstallPlugin(plugin_id)
    {
        gPluginId = plugin_id;
        showBasicModal('<p>Are you sure you want to install this plugin?</p>', 'Confirm Install', '<button type="button" class="btn btn-primary" onClick="installPlugin(); return false;">Install</button>');
    }

    function confirmUninstallPlugin(plugin_id)
    {
        gPluginId = plugin_id;
        showBasicModal('<p>Are you sure you want to uninstall this plugin? All data associated with the plugin will be deleted and unrecoverable.</p>', 'Confirm Uninstall', '<button type="button" class="btn btn-primary" onClick="uninstallPlugin(); return false;">Uninstall</button>');
    }

    function confirmDeletePlugin(plugin_id)
    {
        gPluginId = plugin_id;
        showBasicModal('<p>Are you sure you want to delete this plugin? All data associated with the plugin will be deleted and unrecoverable.</p>', 'Confirm Uninstall', '<button type="button" class="btn btn-primary" onClick="deletePlugin(); return false;">Delete</button>');
    }

    function installPlugin()
    {
        $.ajax({
            type: "POST",
            url: "ajax/plugin_manage_install.ajax.php",
            data: {plugin_id: gPluginId},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    showError(json.msg, 'messageContainer');
                } else
                {
                    //showSuccess(json.msg, 'messageContainer');
                    //reloadTable();
                    window.location = 'plugin_manage.php?id=' + encodeURIComponent(json.id) + '&plugin=' + encodeURIComponent(json.plugin) + '&sm=' + encodeURIComponent(json.msg);
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'messageContainer');
            }
        });
    }

    function uninstallPlugin()
    {
        $.ajax({
            type: "POST",
            url: "ajax/plugin_manage_uninstall.ajax.php",
            data: {plugin_id: gPluginId},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    showError(json.msg, 'messageContainer');
                } else
                {
                    //showSuccess(json.msg, 'messageContainer');
                    //reloadTable();
                    window.location = 'plugin_manage.php?sm=' + encodeURIComponent(json.msg);
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'messageContainer');
            }
        });
    }

    function deletePlugin()
    {
        $.ajax({
            type: "POST",
            url: "ajax/plugin_manage_delete.ajax.php",
            data: {plugin_id: gPluginId},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    showError(json.msg, 'messageContainer');
                } else
                {
                    //showSuccess(json.msg, 'messageContainer');
                    //reloadTable();
                    window.location = 'plugin_manage.php?d=' + encodeURIComponent(json.msg);
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'messageContainer');
            }
        });
    }
    
    function checkForUpdates()
    {
        $.ajax({
            url: "ajax/check_for_upgrade.ajax.php",
            dataType: "json"
        }).done(function(response) {
            totalOutOfDate = 0;
            for(i in response)
            {
                // found some plugins which aren't up to date
                var itemIdentifier = i;
                if($('.identifier_'+itemIdentifier).length > 0)
                {
                    $('.identifier_'+itemIdentifier).replaceWith('<a href="#" onClick="showUpdateNotice(\''+response[i]['latest_version']+'\'); return false;"><i class="fa fa-warning text-danger" data-toggle="tooltip" data-placement="top" data-original-title="Update Available"></i></a>');  
                    totalOutOfDate++;
                }
            }
            
            // assume the rest are up to date
            $('.update_checker').replaceWith('<i class="fa fa-check text-success" data-toggle="tooltip" data-placement="top" data-original-title="Plugin is the Latest Version"></i>');  
            setupTooltips();
            
            // show the onscreen notice
            if(totalOutOfDate > 0)
            {
                showInfo('You have '+totalOutOfDate+' plugin(s) which have updates available. Please see below for more information.');
            }
        }).error(function(response) {
            // assume the rest are up to date
            $('.update_checker').replaceWith('<i class="fa fa-check text-success" data-toggle="tooltip" data-placement="top" data-original-title="Plugin is the Latest Version"></i>');  
            setupTooltips();
        });
    }
    
    function showUpdateNotice(newVersion)
    {
        showBasicModal('<p>This plugin has been updated to v'+newVersion+'. Click the button below to login to your account and download the latest release.</p>', 'Plugin Update Available - v'+newVersion, '<a href="https://yetishare.com" target="_blank" class="btn btn-primary">Download Update</a>');
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
                        <h2>Plugins</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <table id="fileTable" class="table table-striped table-only-border dtLoading bulk_action">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("plugin", "plugin")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("directory_name", "directory name")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("installed", "installed?")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("version", "version")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("up_to_date", "up to date")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("action", "action")); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="20"><?php echo adminFunctions::t('admin_loading_data', 'Loading data...'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="x_panel">
                    <a href="plugin_manage_add.php" type="button" class="btn btn-primary">Add Plugin</a>
                    <a href="<?php echo themeHelper::getCurrentProductUrl(); ?>/plugins.html" target="_blank" type="button" class="btn btn-default">Get Plugins</a>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="customFilter" id="customFilter" style="display: none;">
    <label>
        Filter Results:
        <input name="filterText" id="filterText" type="text" onKeyUp="reloadTable();
                return false;" class="form-control"/>
    </label>
</div>

<?php
include_once('_footer.inc.php');
?>