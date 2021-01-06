<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Manage Banned IP Addresses');
define('ADMIN_SELECTED_PAGE', 'configuration');

// includes and security
include_once('_local_auth.inc.php');

// clear any expired IPs
bannedIP::clearExpiredBannedIps();

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    gBannedIpId = null;
    $(document).ready(function(){
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/banned_ip_manage.ajax.php',
            "iDisplayLength": 25,
            "aaSorting": [[ 1, "asc" ]],
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'ip_address', sWidth: '12%' },
                { sName: 'date_banned', sWidth: '12%', sClass: "adminResponsiveHide" },
                { sName: 'ban_type', sWidth: '10%', sClass: "adminResponsiveHide" },
                { sName: 'ban_expiry', sWidth: '15%', sClass: "adminResponsiveHide" },
                { sName: 'ban_notes' , sClass: "adminResponsiveHide"},
                { bSortable: false, sWidth: '10%', sClass: "center" }
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
                "sEmptyTable": "There are no banned IP addresses in the current filters."
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
    
    function addIPForm()
    {
        showBasicModal('Loading...', 'Add IP Address', '<button type="button" class="btn btn-primary" onClick="processBanIPAddress(); return false;">Ban IP Address</button>');
        loadAddIPForm();
    }

    function loadAddIPForm()
    {
        $.ajax({
            type: "POST",
            url: "ajax/banned_ip_manage_add_form.ajax.php",
            data: { },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    setBasicModalContent(json.msg);
                }
                else
                {
                    setBasicModalContent(json.html);
                    
                    // date picker
                    $('#ban_expiry_date').daterangepicker({
                        singleDatePicker: true,
                        calender_style: "picker_1",
                        autoUpdateInput: false,
                        locale: {
                            format: 'DD/MM/YYYY'
                        }
                    }, function(chosen_date) {
                        $('#ban_expiry_date').val(chosen_date.format('DD/MM/YYYY'));
                    });
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                setBasicModalContent(XMLHttpRequest.responseText);
            }
        });
    }
    
    function processBanIPAddress()
    {
        // get data
        ip_address = $('#ip_address').val();
        ban_type = $('#ban_type').val();
        ban_expiry_date = $('#ban_expiry_date').val();
        ban_expiry_hour = $('#ban_expiry_hour').val();
        ban_expiry_minute = $('#ban_expiry_minute').val();
        ban_notes = $('#ban_notes').val();
        
        $.ajax({
            type: "POST",
            url: "ajax/banned_ip_manage_add_process.ajax.php",
            data: { ip_address: ip_address, ban_type: ban_type, ban_expiry_date: ban_expiry_date, ban_expiry_hour: ban_expiry_hour, ban_expiry_minute: ban_expiry_minute, ban_notes: ban_notes },
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
                    hideModal();
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });

    }
    
    function reloadTable()
    {
        oTable.fnDraw(false);
    }
    
    function deleteBannedIp(bannedIpId)
    {
        showBasicModal('<p>Are you sure you want to remove the banned IP address?</p>', 'Confirm Removal', '<button type="button" class="btn btn-primary" onClick="removeBannedIp(); return false;">Remove</button>');
        gBannedIpId = bannedIpId;
    }
    
    function removeBannedIp()
    {
        $.ajax({
            type: "POST",
            url: "ajax/banned_ip_manage_remove.ajax.php",
            data: { bannedIpId: gBannedIpId },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    showError(json.msg);
                }
                else
                {
                    showSuccess(json.msg);
                    reloadTable();
                    hideModal();
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText);
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
                        <h2>Banned IP Addresses</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <table id='fileTable' class='table table-striped table-only-border dtLoading bulk_action'>
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="align-left"><?php echo adminFunctions::t('ip_address', 'IP Address'); ?></th>
                                    <th class="align-left"><?php echo adminFunctions::t('date_banned', 'Date Banned'); ?></th>
                                    <th class="align-left"><?php echo adminFunctions::t('ban_type', 'Ban Type'); ?></th>
                                    <th class="align-left"><?php echo adminFunctions::t('ban_expiry', 'Ban Expiry'); ?></th>
                                    <th class="align-left"><?php echo adminFunctions::t('ban_notes', 'Ban Notes'); ?></th>
                                    <th class="align-left"><?php echo adminFunctions::t('actions', 'Actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="x_panel">
                    <div class="pull-left">
                        <a href="#" type="button" class="btn btn-primary" onClick="addIPForm(); return false;">Ban IP Address</a>
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