<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Manage Files');
define('ADMIN_SELECTED_PAGE', 'files');
define('ADMIN_SELECTED_SUB_PAGE', 'file_manage');

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('_local_auth.inc.php');

// page header
include_once('_header.inc.php');

// load all users
$sQL         = "SELECT id, username AS selectValue FROM users ORDER BY username";
$userDetails = $db->getRows($sQL);

// load all file servers
$sQL           = "SELECT id, serverLabel FROM file_server ORDER BY serverLabel";
$serverDetails = $db->getRows($sQL);

// load all file status
$statusDetails = array('active', 'trash', 'deleted');

// defaults
$filterText = '';
if (isset($_REQUEST['filterText']))
{
    $filterText = trim($_REQUEST['filterText']);
}

$filterByStatus = 1;
if (isset($_REQUEST['filterByStatus']))
{
    $filterByStatus = (int) $_REQUEST['filterByStatus'];
}

$filterByServer = null;
if (isset($_REQUEST['filterByServer']))
{
    $filterByServer = (int) $_REQUEST['filterByServer'];
}

$filterByUser = null;
$filterByUserLabel = '';
if (isset($_REQUEST['filterByUser']))
{
    $filterByUser = (int) $_REQUEST['filterByUser'];
	$filterByUserLabel = $db->getValue('SELECT username FROM users WHERE id = '.(int)$filterByUser.' LIMIT 1');
}

// UPLOAD SOURCE
$filterBySource = null;
if (isset($_REQUEST['filterBySource']))
{
    $filterBySource = $_REQUEST['filterBySource'];
}
// UPLOAD SOURCE
?>

<script>
    oTable = null;
    gFileId = null;
    gEditFileId = null;
    oldStart = 0;
    $(document).ready(function(){
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/file_manage.ajax.php',
            "deferRender": true,
            "iDisplayLength": 25,
            "aaSorting": [[ 2, "desc" ]],
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'filename' },
                { sName: 'date_uploaded', sWidth: '12%', sClass: "center adminResponsiveHide" },
                { sName: 'filesize', sWidth: '10%', sClass: "center adminResponsiveHide" },
                { sName: 'downloads', sWidth: '10%', sClass: "center adminResponsiveHide" },
                { sName: 'owner', sWidth: '11%', sClass: "center adminResponsiveHide" },
                { sName: 'status', sWidth: '11%', sClass: "center adminResponsiveHide" },
                { bSortable: false, sWidth: '14%', sClass: "center removeMultiFilesButton" }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback, oSettings ) {
                setTableLoading();
                if ( oSettings._iDisplayStart != oldStart ) {
                    var targetOffset = $('.dataTables_wrapper').offset().top-10;
                    $('html, body').animate({scrollTop: targetOffset}, 300);
                    oldStart = oSettings._iDisplayStart;
                }
                aoData.push( { "name": "filterText", "value": $('#filterText').val() } );
                aoData.push( { "name": "filterByUser", "value": $('#filterByUser').val() } );
                aoData.push( { "name": "filterByServer", "value": $('#filterByServer').val() } );
                aoData.push( { "name": "filterByStatus", "value": $('#filterByStatus').val() } );
                aoData.push( { "name": "filterBySource", "value": $('#filterBySource').val() } );
                aoData.push( { "name": "filterView", "value": $('#filterView').val() } );
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

        $('#filterByUser').typeahead({
            source: function( request, response ) {
                $.ajax({
                    url : 'ajax/file_manage_auto_complete.ajax.php',
                    dataType: "json",
                    data: {
                       filterByUser: $("#filterByUser").val()
                    },
                     success: function( data ) {
                        response( data );
                    }
                });
            },
            minLength: 3,
            delay: 1,
            afterSelect: function() { 
                reloadTable();
            }
        });
    });
    
    function bulkMoveFiles()
    {
        if(countChecked() == 0)
        {
            alert('Please select some files to move.');
            return false;
        }
        
        // show popup
        loadMoveFileForm();
        $('#moveFilesForm').modal('show');
    }
    
    function loadMoveFileForm()
    {
        $('#moveFilesForm .modal-body').html('');
        $.ajax({
            type: "POST",
            url: "ajax/file_manage_move_form.ajax.php",
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    $('#moveFilesForm .modal-body').html(json.msg);
                }
                else
                {
                    $('#moveFilesForm .modal-body').html(json.html);
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('#moveFilesForm .modal-body').html(XMLHttpRequest.responseText);
            }
        });
    }
    
    function processMoveFileForm()
    {
        // get data
        serverIds = $('#server_ids').val();
        $.ajax({
            type: "POST",
            url: "ajax/file_manage_move_form_process.ajax.php",
            data: {serverIds: serverIds, gFileIds: getCheckboxFiles()},
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    showError(json.msg, 'popupMessageContainer');
                }
                else
                {
                    showSuccess(json.msg);
                    reloadTable();
                    clearBulkResponses();
                    checkboxIds = {};
                    updateButtonText();
                    $("#moveFilesForm").modal('hide');
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });

    }

    function reloadTable()
    {
        oTable.fnDraw(false);
    }
    
    function confirmRemoveFile(fileId)
    {
        showModal($('#confirmDelete .modal-body'), $('#confirmDelete .modal-footer'));
        gFileId = fileId;
    }
    
    function processRemoveFile()
    {
        removeFile(function() {
            hideModal();
        });
    }
    
    function showNotes(notes)
    {
        showBasicModal('<p>'+notes+'</p>', 'File Notes');
    }
    
    function removeFile(callback)
    {
        // find out file server first
        $.ajax({
            type: "POST",
            url: "ajax/update_file_state.ajax.php",
            data: { fileId: gFileId, statusId: $('#removal_type').val(), adminNotes: $('#admin_notes').val(), blockUploads: $('#block_uploads').val() },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    showError(json.msg);
                }
                else
                {
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
    
    function setupActionButtons()
    {
        updateButtonText();
    }

    function updateButtonText()
    {
        totalFiles = countChecked();
        $('#actionMultiMenu li').removeClass('disabled');
        if(totalFiles == 0)
        {
            totalFilesStr = '';
            $('#actionMultiMenu li').addClass('disabled');
            $('#actionMultiMenuBase').addClass('btn-default');
            $('#actionMultiMenuBase').removeClass('btn-primary');
        }
        else
        {
            totalFilesStr = ' ('+totalFiles+' File';
            if(totalFiles > 1)
            {
                totalFilesStr += 's';
            }
            totalFilesStr += ')';
            $('#actionMultiMenuBase').removeClass('btn-default');
            $('#actionMultiMenuBase').addClass('btn-primary');
        }
        
        $('#actionMultiMenuBase .fileCount').html(totalFilesStr);
    }
    
    function getCheckboxFiles()
    {
        count = 0;
        for(i in checkboxIds)
        {
            count++;
        }
        
        return checkboxIds;
    }
    
    function bulkDeleteFiles(deleteData)
    {
        if(typeof(deleteData) == 'undefined')
        {
            deleteData = false;
        }

        if(countChecked() == 0)
        {
            alert('Please select some files to remove.');
            return false;
        }
        
        msg = 'Are you sure you want to remove '+countChecked()+' files? This can not be undone once confirmed.';
        if(deleteData == true)
        {
            msg += '\n\nAll file data and associated data such as the stats, will also be deleted from the database. This will entirely clear any record of the upload. (exc logs)';
        }
        else
        {
            msg += '\n\nThe original file record will be retained along with the file stats.';
        }
        
        if(confirm(msg))
        {
            bulkDeleteConfirm(deleteData);
        }
    }
    
    var bulkError = '';
    var bulkSuccess = '';
    var totalDone = 0;
    function addBulkError(x)
    {
        bulkError += x;
    }
    function getBulkError(x)
    {
        return bulkError;
    }
    function addBulkSuccess(x)
    {
        bulkSuccess += x;
    }
    function getBulkSuccess(x)
    {
        return bulkSuccess;
    }
    function clearBulkResponses()
    {
        bulkError = '';
        bulkSuccess = '';
    }
    function bulkDeleteConfirm(deleteData)
    {
        // get server list first
        $.ajax({
            type: "POST",
            url: "ajax/file_manage_bulk_delete.ajax.php",
            data: { fileIds: checkboxIds, deleteData: deleteData },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    showError(json.msg);
                }
                else
                {
                    addBulkSuccess(json.msg);
                    finishBulkProcess();
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError('Failed connecting to server to get the list of servers, please try again later.');
            }
        });
    }
    
    function finishBulkProcess()
    {
        // get final response
        bulkError = getBulkError();
        bulkSuccess = getBulkSuccess();

        // compile result
        if(bulkError.length > 0)
        {
            showError(bulkError+bulkSuccess);
        }
        else
        {
            showSuccess(bulkSuccess);
        }
        reloadTable();
        clearBulkResponses();
        checkboxIds = {};
        updateButtonText();
        
        // scroll to the top of the page
        $("html, body").animate({ scrollTop: 0 }, "slow");
        $('#selectAllCB').prop('checked', false);
    }
    
    function editFile(fileId)
    {
        gEditFileId = fileId;
        loadEditFileForm();
        $('#editFileForm').modal('show');
    }
    
    function loadEditFileForm()
    {
        $('#editFileFormInner').html('Loading...');
        $.ajax({
            type: "POST",
            url: "ajax/file_manage_edit_form.ajax.php",
            data: {gEditFileId: gEditFileId},
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    $('#editFileFormInner').html(json.msg);
                }
                else
                {
                    $('#editFileFormInner').html(json.html);
                    setupTagInterface();
                    toggleFilePasswordField();
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('#editFileFormInner').html(textStatus+': '+errorThrown);
            }
        });
    }
    
    function toggleFilePasswordField()
    {
        if($('#editFileForm #enablePassword').is(':checked'))
        {
            $('#editFileForm #password').attr('READONLY', false);
        }
        else
        {
            $('#editFileForm #password').attr('READONLY', true);
        }
    }
    
    function processEditFile()
    {
        $.ajax({
            type: "POST",
            url: "ajax/file_manage_edit_process.ajax.php",
            data: $('form#editFileFormInner').serialize(),
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    showError(json.msg, 'popupMessageContainer');
                }
                else
                {
                    showSuccess(json.msg);
                    reloadTable();
                    $("#editFileForm").modal('hide');
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                showError(textStatus+': '+errorThrown, 'popupMessageContainer');
            }
        });

    }

    function updateSelectedRemoveFileSelect()
    {
        if($('#removal_type').val() == '4')
        {
            $('#block_uploads').val('1');
        }
        else
        {
            $('#block_uploads').val('0');
        }
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
                        <h2>File List</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <table id="fileTable" class="table table-striped table-only-border dtLoading bulk_action">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="check-all" class="flat"/></th>
                                    <th class="align-left fileManageFileName"><?php echo UCWords(adminFunctions::t('filename', 'Filename')); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t('date_uploaded', 'Date Uploaded')); ?></th>
                                    <th ><?php echo UCWords(adminFunctions::t('filesize', 'Filesize')); ?></th>
                                    <th><?php echo UCWords(adminFunctions::t('downloads', 'Downloads')); ?></th>
                                    <th><?php echo UCWords(adminFunctions::t('owner', 'Owner')); ?></th>
                                    <th><?php echo UCWords(adminFunctions::t('status', 'Status')); ?></th>
                                    <th class="align-left fileManageActions"><?php echo UCWords(adminFunctions::t('actions', 'Actions')); ?></th>
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
                    <div class="btn-group">
                        <div class="dropup">
                            <button class="btn btn-default dropdown-toggle" type="button" id="actionMultiMenuBase" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                Bulk Action<span class="fileCount"></span>
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="actionMultiMenuBase" id="actionMultiMenu">
                                <li class="disabled"><a href="#" onClick="bulkDeleteFiles(false); return false;"><?php echo adminFunctions::t('remove_files_total', 'Remove Files[[[FILE_COUNT]]]', array('FILE_COUNT' => '')); ?></a></li>
                                <li class="disabled"><a href="#" onClick="bulkDeleteFiles(true); return false;"><?php echo adminFunctions::t('delete_files_and_data_total', 'Delete Files And Stats Data[[[FILE_COUNT]]]', array('FILE_COUNT' => '')); ?></a></li>
                                <li class="disabled"><a href="#" onClick="bulkMoveFiles(); return false;"><?php echo adminFunctions::t('move_files_total', 'Move Files[[[FILE_COUNT]]]', array('FILE_COUNT' => '')); ?></a></li>
                            </ul>
                        </div>
                    </div>
                    <?php if($Auth->hasAccessLevel(20)): ?>
                        <div class="btn-group">
                            <a href="export_csv.php?type=files" type="button" class="btn btn-default">Export All Data (CSV)</a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="customFilter" id="customFilter" style="display: none;">
    <label>
        Filter:
        <input name="filterText" id="filterText" type="text" value="<?php echo adminFunctions::makeSafe($filterText); ?>" onKeyUp="reloadTable(); return false;" style="width: 180px;" class="form-control input-sm"/>
    </label>
    <label id="username" style="padding-left: 6px;">
        User:
        <input name="filterByUser" id="filterByUser" type="text" class="filterByUser form-control input-sm txt-auto" style="width: 120px;" value="<?php echo adminFunctions::makeSafe($filterByUserLabel); ?>" autocomplete="off"/>
    </label>
    <label class="adminResponsiveHide filterByServerWrapper" style="padding-left: 6px;">
        Server:
        <select name="filterByServer" id="filterByServer" onChange="reloadTable(); return false;" style="width: 120px;" class="form-control input-sm">
            <option value="">- all -</option>
            <?php
            if(COUNT($serverDetails))
            {
                foreach($serverDetails AS $serverDetail)
                {
                    echo '<option value="' . $serverDetail['id'] . '"';
                    if(($filterByServer) && ($filterByServer == $serverDetail['id']))
                    {
                        echo ' SELECTED';
                    }
                    echo '>' . $serverDetail['serverLabel'] . '</option>';
                }
            }
            ?>
        </select>
    </label>
    <label class="adminResponsiveHide filterByStatusWrapper" style="padding-left: 6px;">
        Status:
        <select name="filterByStatus" id="filterByStatus" onChange="reloadTable(); return false;" style="width: 120px;" class="form-control input-sm">
            <option value="">- all -</option>
            <?php
            foreach($statusDetails AS $statusDetail)
            {
                echo '<option value="' . $statusDetail . '"';
                if(($filterByStatus) && ($filterByStatus == $statusDetail))
                {
                    echo ' SELECTED';
                }
                echo '>' . $statusDetail . '</option>';
            }
            ?>
        </select>
    </label>
    <label class="adminResponsiveHide filterBySourceWrapper" style="padding-left: 6px; display: none;">
        Src:
        <select name="filterBySource" id="filterBySource" onChange="reloadTable(); return false;" style="width: 80px;" class="form-control input-sm">
            <option value="">- all -</option>
            <option value="direct">Direct</option>
            <option value="ftp">FTP</option>
            <option value="remote">Remote</option>
            <option value="torrent">Torrent</option>
            <option value="leech">Leech</option>
            <option value="webdav">Webdav</option>
            <option value="api">API</option>
            <option value="other">Other</option>
        </select>
    </label>

    <label class="adminResponsiveHide updateViewWrapper" style="padding-left: 6px;">
        View:
        <select name="filterView" id="filterView" onChange="reloadTable(); return false;" style="width: 80px;" class="form-control input-sm">
            <option value="list" <?php echo SITE_CONFIG_DEFAULT_ADMIN_FILE_MANAGER_VIEW == 'list' ? 'SELECTED' : ''; ?>>List</option>
            <option value="thumb" <?php echo SITE_CONFIG_DEFAULT_ADMIN_FILE_MANAGER_VIEW == 'thumb' ? 'SELECTED' : ''; ?>>Thumbnails</option>
        </select>
    </label>
</div>

<div id="editFileForm" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button></div>
            <div class="modal-body" id="editFileFormInner"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onClick="processEditFile();">Update File</button>
            </div>
        </div>
    </div>
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
                                      <option value="4">Copyright Breach (DMCA)</option>
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
                                        <option value="1">Yes (this file will be blocked from uploading again)</option>
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

<div id="moveFilesForm" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button></div>
            <div class="modal-body" id="moveFilesRawFileForm"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onClick="processMoveFileForm();">Move Files</button>
            </div>
        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>