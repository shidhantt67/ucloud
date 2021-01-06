<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Background Task Logs');
define('ADMIN_SELECTED_PAGE', 'configuration');
define('ADMIN_SELECTED_SUB_PAGE', 'background_task');

// includes and security
define('MIN_ACCESS_LEVEL', 20);
include_once('_local_auth.inc.php');

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    $(document).ready(function () {
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/background_task_manage.ajax.php',
            "iDisplayLength": 25,
            "bFilter": false,
            "aaSorting": [[1, "asc"]],
            "aoColumns": [
                {bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide"},
                {sName: 'task_name', bSortable: false},
                {sName: 'last_update', sWidth: '15%', sClass: "center", bSortable: false},
                {sName: 'status', sClass: "center", sWidth: '15%', bSortable: false},
                {bSortable: false, sClass: "center adminResponsiveHide", sWidth: '15%'}
            ],
            "fnDrawCallback": function (oSettings) {
                postDatatableRender();
            },
            "oLanguage": {
                "sEmptyTable": "There are no crons in the current filters."
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
                        <h2>List Of Background/Cron Tasks</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <p>Below is the background (cron) tasks set to run on the system. Use this page to ensure they're running and see the last run time. For more information on setting up the crons, <a href="https://support.mfscripts.com/public/kb_view/26/" target="_blank">see here</a>.</p>
                        <table id="fileTable" class="table table-striped table-only-border dtLoading bulk_action">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="align-left"><?php echo adminFunctions::t('task_name', 'Task Name'); ?></th>
                                    <th class="align-left"><?php echo adminFunctions::t('last_run', 'Last Run'); ?></th>
                                    <th><?php echo adminFunctions::t('status', 'Status'); ?></th>
                                    <th class="align-left"><?php echo adminFunctions::t('actions', 'Actions'); ?></th>
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
                    <a href="https://support.mfscripts.com/public/kb_view/26/" class="btn btn-default" target="_blank">More Information On Background Tasks/Crons</a>
                </div>

            </div>
        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>