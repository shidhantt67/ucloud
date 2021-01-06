<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Manage Download Pages');
define('ADMIN_SELECTED_PAGE', 'configuration');
define('ADMIN_SELECTED_SUB_PAGE', 'download_page_manage');

// includes and security
include_once('_local_auth.inc.php');

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    gPageId = null;
    gDefaultLanguage = '';
    gEditPageId = null;
    $(document).ready(function(){
        // datatable
        oTable = $('#downloadPagesTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/download_page_manage.ajax.php',
            "iDisplayLength": 25,
            "aaSorting": [[ 1, "asc" ]],
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide"},
                { bSortable: false, sName: 'user_level', sWidth: '20%' },
                { bSortable: false , sClass: "adminResponsiveHide"},
                { bSortable: false, sWidth: '25%', sClass: "center" }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback, oSettings ) {
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
                "sEmptyTable": "There are no download pages in the current filters."
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
    
    function addDownloadPageForm()
    {
        showBasicModal('Loading...', 'Add Download Page', '<button type="button" class="btn btn-primary" onClick="processAddDownloadPage(); return false;">Add Download Page</button>');
        loadAddDownloadPageForm();
    }
    
    function loadAddDownloadPageForm()
    {
        $.ajax({
            type: "POST",
            url: "ajax/download_page_manage_add_form.ajax.php",
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
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                setBasicModalContent(XMLHttpRequest.responseText);
            }
        });
    }
    
    function processAddDownloadPage()
    {
        // get data
        download_page = $('#download_page').val();
        user_level_id = $('#user_level_id').val();
        page_order = $('#page_order').val();
        optional_timer = $('#optional_timer').val();
        additional_javascript_code = $('#additional_javascript_code').val();
        
        $.ajax({
            type: "POST",
            url: "ajax/download_page_manage_add_process.ajax.php",
            data: { download_page: download_page, user_level_id: user_level_id, page_order: page_order, optional_timer: optional_timer, additional_javascript_code: additional_javascript_code },
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
    
    function editDownloadPageForm(pageId)
    {
        gEditPageId = pageId;
        showBasicModal('Loading...', 'Edit Download Page', '<button type="button" class="btn btn-primary" onClick="processEditDownloadPage(); return false;">Update Download Page</button>');
        loadEditDownloadPageForm();
    }
    
    function loadEditDownloadPageForm()
    {
        $.ajax({
            type: "POST",
            url: "ajax/download_page_manage_add_form.ajax.php",
            data: { pageId: gEditPageId },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    setBasicModalContent(json.msg);
                }
                else
                {
                    setBasicModalContent(json.html);
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                setBasicModalContent(XMLHttpRequest.responseText);
            }
        });
    }
    
    function processEditDownloadPage()
    {
        // get data
        download_page = $('#download_page').val();
        user_level_id = $('#user_level_id').val();
        page_order = $('#page_order').val();
        optional_timer = $('#optional_timer').val();
        additional_javascript_code = $('#additional_javascript_code').val();
        
        $.ajax({
            type: "POST",
            url: "ajax/download_page_manage_add_process.ajax.php",
            data: { download_page: download_page, user_level_id: user_level_id, page_order: page_order, optional_timer: optional_timer, additional_javascript_code: additional_javascript_code, pageId: gEditPageId },
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
    
    function deletePageType(pageId)
    {
        gPageId = pageId;
        showBasicModal('<p>Are you sure you want to delete the download page on this user type?</p>', 'Confirm Removal', '<button type="button" class="btn btn-primary" onClick="removePageOnUserType(); return false;">Delete Download Page</button>');        
    }
    
    function removePageOnUserType()
    {
        $.ajax({
            type: "POST",
            url: "ajax/download_page_manage_remove.ajax.php",
            data: { pageId: gPageId },
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
                        <h2>Download Pages</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <p>Use this section to manage which pages your users see when they request a file download. Set 1 or more pages depending on user type. If a user type doesn't have any pages, the file will be downloaded directly. Some pages support countdown timers which you can also define here. If you manually add new pages, as long as the filename starts _download_page_ it will appear here.</p>
                        <table id="downloadPagesTable" class="table table-striped table-only-border dtLoading bulk_action">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t('user_level_page', 'User Level / Page')); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t('download_page', 'Download Page')); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t('actions', 'Actions')); ?></th>
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
                        <a href="#" type="button" class="btn btn-primary" onClick="addDownloadPageForm(); return false;">Set Download Page To User Type</a>
                    </div>
                </div>
                
                <div class="x_panel">
                    <div class="x_title">
                        <h2>Customising Download Pages</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <p>If you create any custom download pages, ensure you use the following PHP code to create the link to the next download page:<br/><br/><code><?php echo htmlentities('<?php echo $file->getNextDownloadPageLink(); ?>'); ?></code><br/><br/>Use this on any 'next' links or buttons for every download page you create. See _download_page_compare_all.inc.php source code for an example.</p>
                    </div>
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
</div>

<?php
include_once('_footer.inc.php');
?>