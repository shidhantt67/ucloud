<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Abuse Reports');
define('ADMIN_SELECTED_PAGE', 'files');
define('MIN_ACCESS_LEVEL', 10); // allow moderators
define('ADMIN_SELECTED_SUB_PAGE', 'file_report_manage');

// includes and security
include_once('_local_auth.inc.php');

// page header
include_once('_header.inc.php');

// status list
$statusDetails = array('pending', 'cancelled', 'accepted');

$filterByReportStatus = 'pending';
if (isset($_REQUEST['filterByReportStatus']))
{
    $filterByReportStatus = trim($_REQUEST['filterByReportStatus']);
}
?>
        
<script>
    oTable = null;
    gFileId = null;
    gAbuseId = null;
    gNotesText = '';
    $(document).ready(function(){
        // datatable
        oTable = $('#abuseReportsTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/file_report_manage.ajax.php',
            "iDisplayLength": 25,
            "aaSorting": [[ 2, "desc" ]],
            "aoColumns" : [
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'report_date', sWidth: '12%', sClass: "center" },
                { sName: 'file_name', sClass: "adminResponsiveHide" },
                { sName: 'reported_by_name', sWidth: '15%', sClass: "center adminResponsiveHide" },
                { sName: 'reported_by_ip', sWidth: '15%', sClass: "center adminResponsiveHide" },
                { sName: 'status', sWidth: '12%', sClass: "center adminResponsiveHide" },
                { bSortable: false, sWidth: '20%', sClass: "center" }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback ) {
                setTableLoading();
                aoData.push( { "name": "filterText", "value": $('#filterText').val() } );
                aoData.push( { "name": "filterByReportStatus", "value": $('#filterByReportStatus').val() } );
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
                "sEmptyTable": "No abuse reports found in the current filters."
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
        $( "#confirmDelete" ).modal({
            show: false
        });
        
        // dialog box
        $( "#viewReport" ).modal({
            show: false
        });
    });

    function reloadTable()
    {
        oTable.fnDraw(false);
    }
    
    function confirmRemoveFile(abuseId, notesText, fileId)
    {
        showModal($('#confirmDelete .modal-body'), $('#confirmDelete .modal-footer'));
        gFileId = fileId;
        $('#removeFileForm #admin_notes').val(notesText);
        gFileId = fileId;
        gAbuseId = abuseId;
    }
    
    function processRemoveFile()
    {
        removeFile(function() {
            hideModal();
        });
    }
    
    function viewReport(abuseId, notesText, fileId, reportStatus)
    {
        gFileId = fileId;
        gAbuseId = abuseId;
        gNotesText = notesText;
        
        $('#viewReport .modal-body').html('');
        $('#viewReport').modal('show');
        $.ajax({
            type: "POST",
            url: "ajax/file_report_detail.ajax.php",
            data: { abuseId: abuseId },
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    $('#viewReport .modal-body').html(json.msg);
                }
                else
                {
                    $('#viewReport .modal-body').html(json.html);
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('#viewReport .modal-body').html(XMLHttpRequest.responseText);
            }
        });


        // show or hide action buttons
        if(reportStatus == 'pending')
        {
            $(":button:contains('Remove File')").prop("disabled", false).removeClass("ui-state-disabled");
            $(":button:contains('Decline Request')").prop("disabled", false).removeClass("ui-state-disabled");
        }
        else
        {
            $(":button:contains('Remove File')").prop("disabled", true).addClass("ui-state-disabled");
            $(":button:contains('Decline Request')").prop("disabled", true).addClass("ui-state-disabled");
            $('.ui-dialog :button').blur();
        }
    }
    
    function removeFile(callback)
    {
        // find out file server first
        $.ajax({
            type: "POST",
            url: "ajax/update_file_state.ajax.php",
            data: { fileId: gFileId, statusId: $('#removal_type').val(), adminNotes: $('#admin_notes').val(), blockUploads: 1 },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    showError(json.msg);
                }
                else
                {
                    acceptReport(gAbuseId);
                    return true;
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText);
            }
        });
    }
    
    function acceptReport(abuseId)
    {
        gAbuseId = abuseId;
        //  accept report
        $.ajax({
            type: "POST",
            url: "ajax/file_report_accept.ajax.php",
            data: { abuseId: gAbuseId },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    hideModal();
                    showError(json.msg);
                }
                else
                {
                    hideModal();
                    showSuccess(json.msg);
                    $('#removal_type').val(3);
                    $('#admin_notes').val('');
                    reloadTable();
                    callback();
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText);
            }
        });
    }
    
    function declineReport(reportId)
    {
        if(confirm('Are you sure you want to decline this abuse report?'))
        {
            //  decline report
            $.ajax({
                type: "POST",
                url: "ajax/file_report_decline.ajax.php",
                data: { reportId: reportId },
                dataType: 'json',
                success: function(json) {
                    if(json.error == true)
                    {
                        showError(json.msg);
                    }
                    else
                    {
                        reloadTable();
                    }

                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    showError(XMLHttpRequest.responseText);
                }
            });
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
                        <h2>Abuse Reports</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <table id="abuseReportsTable" class="table table-striped table-only-border dtLoading bulk_action">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="align-left"><?php echo adminFunctions::t('report_date', 'Report Date'); ?></th>
                                    <th><?php echo adminFunctions::t('file', 'File'); ?></th>
                                    <th class="align-left"><?php echo adminFunctions::t('reported_by_name', 'Reported Name'); ?></th>
                                    <th style="width: 10%;"><?php echo adminFunctions::t('reported_by_ip', 'Reported By IP'); ?></th>
                                    <th style="width: 10%;"><?php echo adminFunctions::t('status', 'Status'); ?></th>
                                    <th class="align-left" style="width: 15%;"><?php echo adminFunctions::t('actions', 'Actions'); ?></th>
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
                    <a type="button" class="btn btn-default mobileAdminResponsiveHide" href="file_report_manage_bulk_remove.php">Bulk Remove</a>
                </div>
                
            </div>
        </div>
    </div>
</div>

<div class="customFilter" id="customFilter" style="display: none;">
    <label>
        Filter Results:
        <input name="filterText" id="filterText" type="text" value="<?php echo adminFunctions::makeSafe($filterText); ?>" onKeyUp="reloadTable(); return false;" style="width: 160px;" class="form-control"/>
    </label>
    <label class="adminResponsiveHide" style="padding-left: 6px;">
        By Status:
        <select name="filterByReportStatus" id="filterByReportStatus" onChange="reloadTable(); return false;" style="width: 120px;" class="form-control">
            <option value="">- all -</option>
            <?php
            foreach ($statusDetails AS $statusDetail)
            {
                echo '<option value="' . $statusDetail . '"';
                if (($filterByReportStatus) && ($filterByReportStatus == $statusDetail))
                {
                    echo ' SELECTED';
                }
                echo '>' . UCWords($statusDetail) . '</option>';
            }
            ?>
        </select>
    </label>
</div>

<div id="confirmDelete" style="display: none;">
    <div class="modal-body">
        <form id="removeFileForm" class="form-horizontal form-label-left input_mask">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Remove File:</h2>
                    <div class="clearfix"></div>
                </div>

                <div class="x_content">
                    <p>Select the type of removal below. You can also add removal notes such as a copy of the original removal request. The notes are only visible by an admin user.</p>
                    <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">
                            Removal Type:
                        </label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                              <div class="input-group">
                                  <select name="removal_type" id="removal_type" class="form-control" onChange="updateSelectedRemoveFileSelect(); return false;">
                                      <option value="3">General</option>
                                      <option value="4" SELECTED>Copyright Breach (DMCA)</option>
                                  </select>
                              </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">
                            Notes:
                        </label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <textarea name="admin_notes" id="admin_notes" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">
                            Block Future Uploads:
                        </label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                              <div class="input-group">
                                  <select name="block_uploads" id="block_uploads" class="form-control">
                                        <option value="0">No (allow the same file to be uploaded again)</option>
                                        <option value="1" SELECTED>Yes (this file will be blocked from uploading again)</option>
                                  </select>
                              </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onClick="processRemoveFile();">Remove File</button>
    </div>
</div>

<div id="viewReport" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button></div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onClick="$('#viewReport').modal('hide'); confirmRemoveFile(gAbuseId, gNotesText, gFileId);">Remove File</button>
                <button type="button" class="btn btn-primary" onClick="$('#viewReport').modal('hide'); declineReport(gAbuseId);">Decline Request</button>
            </div>
        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>