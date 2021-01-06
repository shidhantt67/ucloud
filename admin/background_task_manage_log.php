<?php
// initial constants
define('ADMIN_SELECTED_PAGE', 'configuration');

// includes and security
define('MIN_ACCESS_LEVEL', 20);
include_once('_local_auth.inc.php');

// pickup params
$taskId = null;
if(isset($_REQUEST['task_id']))
{
    $taskId = (int) $_REQUEST['task_id'];
}
if(!$taskId)
{
    coreFunctions::redirect('background_task_manage.php');
}

// load task
$task = $db->getRow('SELECT * FROM background_task WHERE id = ' . (int) $taskId . ' LIMIT 1');
if(!$task)
{
    coreFunctions::redirect('background_task_manage.php');
}

define('ADMIN_PAGE_TITLE', 'Background Task Logs: "' . $task['task'] . '"');

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
            "sAjaxSource": 'ajax/background_task_manage_log.ajax.php?task_id=<?php echo (int) $task['id']; ?>',
            "iDisplayLength": 25,
            "bFilter": false,
            "aaSorting": [[1, "desc"]],
            "aoColumns": [
                {bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center"},
                {sName: 'server', bSortable: false},
                {sName: 'start_time', sWidth: '15%', sClass: "center", bSortable: false},
                {sName: 'end_time', sWidth: '15%', sClass: "center", bSortable: false},
                {sName: 'status', sClass: "center", sWidth: '15%', bSortable: false}
            ],
            "fnDrawCallback": function (oSettings) {
                postDatatableRender();
            },
            "oLanguage": {
                "sEmptyTable": "There are no logs in the current filters."
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
                        <h2>Recent Logs</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <p>All recent runs of this task are listed below, these include any external servers which may also be running this task.</p>
                        <table id="fileTable" class="table table-striped table-only-border dtLoading bulk_action">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th><?php echo adminFunctions::t('server', 'Server'); ?></th>
                                    <th><?php echo adminFunctions::t('start_time', 'Start Time'); ?></th>
                                    <th><?php echo adminFunctions::t('end_time', 'End Time'); ?></th>
                                    <th><?php echo adminFunctions::t('status', 'Status'); ?></th>
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
                    <a href="background_task_manage.php" class="btn btn-primary">< Back to Background Task Logs</a>
                    <a href="https://support.mfscripts.com/public/kb_view/26/" class="btn btn-default" target="_blank">More Information On Background Tasks/Crons</a>
                </div>

            </div>
        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>