<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Manage Payment Subscriptions');
define('ADMIN_SELECTED_PAGE', 'users');
define('ADMIN_SELECTED_SUB_PAGE', 'payment_subscription_manage');

// includes and security
include_once('_local_auth.inc.php');

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    gPaymentId = null;
    $(document).ready(function(){
        // datatable
        oTable = $('#paymentsTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/payment_subscription_manage.ajax.php',
            "iDisplayLength": 25,
            "aaSorting": [[ 1, "desc" ]],
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'payment_date', sWidth: '15%' , sClass: "center" },
                { sName: 'user_name', sWidth: '18%' , sClass: "center adminResponsiveHide" },
                { sName: 'period', bSortable: false, sClass: "center adminResponsiveHide" },
                { sName: 'amount', bSortable: false, sWidth: '12%', sClass: "center" },
                { sName: 'gateway', sWidth: '12%', sClass: "center" },
                { bSortable: false, sWidth: '10%', sClass: "center adminResponsiveHide" }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback ) {
                aoData.push( { "name": "filterText", "value": $('#filterText').val() } );
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
                "sEmptyTable": "There are no subscriptions in the current filters."
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
                        <h2>List Of Subscribers</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <p>
                            Note: Not all payment gateways will support subscriptions. Please check the payment gateway plugin settings page for the option to enable or disable subscriptions. If it is not available, the gateway plugin does not yet have built in support for it.
                        </p>
                        <table id="paymentsTable" class="table table-striped table-only-border dtLoading bulk_action">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("date_added", "date added")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("user_name", "user name")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("period", "period")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("amount", "amount")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("gateway", "gateway")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("status", "status")); ?></th>
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

<div class="customFilter" id="customFilter" style="display: none;">
    <label>
        Filter Results:
        <input name="filterText" id="filterText" type="text" onKeyUp="reloadTable(); return false;" style="width: 160px;"/>
    </label>
</div>

<?php
include_once('_footer.inc.php');
?>