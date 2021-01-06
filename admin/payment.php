<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Received Payments');
define('ADMIN_SELECTED_PAGE', 'users');
define('ADMIN_SELECTED_SUB_PAGE', 'payment');

// includes and security
include_once('_local_auth.inc.php');

// page header
include_once('_header.inc.php');

$paymentStatusDetails = array('success' => 1, 'failed' => 2, 'processing' => 3);
$filterByAccountStatus = '';
if(isset($_REQUEST['filterByAccountStatus']))
{
    $filterByAccountStatus = trim($_REQUEST['filterByAccountStatus']);
}
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
            "sAjaxSource": 'ajax/payment.ajax.php',
            "iDisplayLength": 25,
            "aaSorting": [[ 1, "desc" ]],
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'payment_date', sWidth: '15%' , sClass: "center" },
                { sName: 'user_name', sWidth: '18%' , sClass: "center adminResponsiveHide" },
                { sName: 'ammount', sWidth: '18%' , sClass: "center adminResponsiveHide" },
                { sName: 'order_id' , sClass: "center adminResponsiveHide" },
                { sName: 'payment_id', sWidth: '12%', sClass: "center" },
                { sName: 'status', sWidth: '12%', sClass: "center" },
                { bSortable: false, sWidth: '10%', sClass: "center adminResponsiveHide" }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback ) {
                aoData.push( { "name": "filterText", "value": $('#filterText').val() } );
                aoData.push({"name": "filterByPaymentStatus", "value": $('#filterByPaymentStatus').val()});
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
                "sEmptyTable": "There are no payments in the current filters."
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

        // dialog box
        $( "#addPaymentForm" ).modal({
            show: false
        });
        
        // dialog box
        $( "#paymentDetailForm" ).modal({
            show: false
        });
        
        <?php if(isset($_REQUEST['log'])): ?>
            addPaymentForm();
        <?php endif; ?>
    });
    
    function viewPaymentDetail(paymentId)
    {
        gPaymentId = paymentId;
        loadPaymentDetail();
        $('#paymentDetailForm').modal('show');
    }

    function reloadTable()
    {
        oTable.fnDraw(false);
    }
    
    function loadPaymentDetail()
    {
        $('#paymentDetailInnerWrapper').html('Loading, please wait...');
        $.ajax({
            type: "POST",
            url: "ajax/payment_manage_detail.ajax.php",
            data: { paymentId: gPaymentId },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    $('#paymentDetailInnerWrapper').html(json.msg);
                }
                else
                {
                    $('#paymentDetailInnerWrapper').html(json.html);
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                $('#paymentDetailInnerWrapper').html(XMLHttpRequest.responseText);
            }
        });
    }
    
    function addPaymentForm()
    {
        loadAddPaymentForm();
        $('#addPaymentForm').modal('show');
    }
    
    function loadAddPaymentForm()
    {
        $('#paymentForm').html('Loading, please wait...');
        $.ajax({
            type: "POST",
            url: "ajax/payment_manage_add_form.ajax.php",
            data: { },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    $('#paymentForm').html(json.msg);
                }
                else
                {
                    $('#paymentForm').html(json.html);
                    setupPopupFormElements();
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                $('#paymentForm').html(XMLHttpRequest.responseText);
            }
        });
    }
    
    function setupPopupFormElements()
    {
        $('#editFileFormInner #payment_date').daterangepicker({
            singleDatePicker: true,
            calender_style: "picker_1",
            timePicker: true,
            autoUpdateInput: false,
            locale: {
                format: 'DD/MM/YYYY HH:mm:00'
            }
        }, function(chosen_date) {
            $('#editFileFormInner #payment_date').val(chosen_date.format('DD/MM/YYYY HH:mm:00'));
        });
    }
    
    function processAddPayment()
    {
        // get data
        user_id = $('#user_id').val();
        payment_date = $('#payment_date').val();
        payment_amount = $('#payment_amount').val();
        description = $('#description').val();
        payment_method = $('#payment_method').val();
        notes = $('#notes').val();
        
        $.ajax({
            type: "POST",
            url: "ajax/payment_manage_add_process.ajax.php",
            data: { user_id: user_id, payment_date: payment_date, payment_amount: payment_amount, description: description, payment_method: payment_method, notes: notes },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    showError(json.msg, 'popupMessageContainer');
                }
                else
                {
                    showSuccess(json.msg);
                    reloadTable();
                    $("#addPaymentForm").modal('hide');
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });
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
                        <h2>List Of Payments from RazorPay</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <p>
                            Note: Payments will only show above after the charge is successful and the payment gateway calls back to the site. Any users which have been manually upgraded, without logging a payment, will not be shown below.
                        </p>
                        <table id="paymentsTable" class="table table-striped table-only-border dtLoading bulk_action">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("user_name", "user name")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("amount", "Amount")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("order_id", "order id")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("payment_id", "payment id")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("payment_date", "payment date")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("status", "status")); ?></th>
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

            </div>
        </div>
    </div>
</div>

<div class="customFilter" id="customFilter" style="display: none;">
    <label>
        Filter Results:
        <input name="filterText" id="filterText" type="text" onKeyUp="reloadTable(); return false;" style="width: 160px;"/>
    </label>
    <label class="adminResponsiveHide" style="padding-left: 6px;">
        By Status:
        <select name="filterByPaymentStatus" id="filterByPaymentStatus" onChange="reloadTable();
                return false;" style="width: 120px;" class="form-control">
            <option value="">- all -</option>
<?php
if(COUNT($paymentStatusDetails))
{
    foreach($paymentStatusDetails as $paymentStatusDetail=>$value)
    {
        echo '<option value="' . $value . '"';
        if(($filterByAccountStatus) && ($filterByAccountStatus == $paymentStatusDetail))
        {
            echo ' SELECTED';
        }
        echo '>' . UCWords($paymentStatusDetail) . '</option>';
    }
}
?>
        </select>
    </label>
</div>

<div id="addPaymentForm" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button></div>
            <div class="modal-body" id="paymentForm"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onClick="processAddPayment();">Add Payment Entry</button>
            </div>
        </div>
    </div>
</div>

<div id="paymentDetailForm" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button></div>
            <div class="modal-body" id="paymentDetailInnerWrapper"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>