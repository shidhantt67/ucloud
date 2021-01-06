<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Downloads');
define('ADMIN_SELECTED_PAGE', 'downloads');
define('AUTO_REFRESH_PERIOD_SECONDS', 15);
define('ADMIN_SELECTED_SUB_PAGE', 'download_current');

// includes and security
include_once('_local_auth.inc.php');

// clear any expired download trackers
downloadTracker::clearTimedOutDownloads();
downloadTracker::purgeDownloadData();

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    oTableRefreshTimer = null;
    $(document).ready(function(){
        // datatable
        oTable = $('#currentDownloadsTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/download_current.ajax.php',
            "iDisplayLength": 100,
            "aaSorting": [[ 1, "desc" ]],
            "bFilter": false,
            "bLengthChange": false,
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'date_started', sWidth: '15%', sClass: "activeDownloadsColumn" },
                { sName: 'ip_address', sWidth: '12%', sClass: "center adminResponsiveHide" },
                { sName: 'file_name' },
                { sName: 'file_size', sWidth: '12%', sClass: "center adminResponsiveHide" },
                { sName: 'total_threads', sWidth: '10%', sClass: "center adminResponsiveHide" },
                { sName: 'status', sWidth: '14%', sClass: "center adminResponsiveHide" }
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
                "sEmptyTable": "There are no active downloads."
            }
        });
        
        oTableRefreshTimer = setInterval('reloadTable()', <?php echo (int)AUTO_REFRESH_PERIOD_SECONDS; ?> * 1000)
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
                        <h2>Active Downloads</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <p>The table below shows all active downloads on the site. This screen will automatically refresh every <?php echo AUTO_REFRESH_PERIOD_SECONDS; ?> seconds.</p>
                        <p>Note: This page will not show any data if you are using xSendFile (Apache) or xAccelRedirect (Nginx) to handle your file downloads.</p>
                        <table id="currentDownloadsTable" class="table table-striped table-only-border dtLoading bulk_action">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th><?php echo UCWords(adminFunctions::t("date_started", "date started")); ?></th>
                                    <th><?php echo UCWords(adminFunctions::t("downloader", "downloader")); ?></th>
                                    <th><?php echo UCWords(adminFunctions::t("file_name", "file name")); ?></th>
                                    <th><?php echo UCWords(adminFunctions::t("file_size", "file size")); ?></th>
                                    <th><?php echo UCWords(adminFunctions::t("threads", "threads")); ?></th>
                                    <th><?php echo UCWords(adminFunctions::t("status", "status")); ?></th>
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