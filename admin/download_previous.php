<?php

// includes and security
include_once('_local_auth.inc.php');

// load file
$file = file::loadById((int)$_REQUEST['fileId']);

// initial constants
define('ADMIN_PAGE_TITLE', 'Downloads for '.substr($file->originalFilename, 0, 50));
define('ADMIN_SELECTED_PAGE', 'files');
define('ADMIN_SELECTED_SUB_PAGE', 'file_manage');

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    oTableRefreshTimer = null;
    $(document).ready(function(){
        // datatable
        oTable = $('#previousDownloadsTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/download_previous.ajax.php?fileId=<?php echo (int)$file->id; ?>',
            "iDisplayLength": 100,
            "aaSorting": [[ 1, "desc" ]],
            "bFilter": false,
            "bLengthChange": false,
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'date_started', sWidth: '15%', sClass: "activeDownloadsColumn" },
                { sName: 'ip_address', sWidth: '12%', sClass: "center adminResponsiveHide" },
                { sName: 'username' }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback, oSettings ) {
                setTableLoading();
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
                "sEmptyTable": "There are no previous downloads."
            }
        });
    });
    
    function reloadTable()
    {
        oTable.fnDraw();
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
                        <h2>File Downloads</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <table id="previousDownloadsTable" class="table table-striped table-only-border dtLoading bulk_action">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th><?php echo UCWords(adminFunctions::t("download_date", "download date")); ?></th>
                                    <th><?php echo UCWords(adminFunctions::t("ip_address", "ip address")); ?></th>
                                    <th><?php echo UCWords(adminFunctions::t("username", "username")); ?></th>
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
            </div>
        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>