<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Account Packages');
define('ADMIN_SELECTED_PAGE', 'account_level');
define('ADMIN_SELECTED_SUB_PAGE', 'account_level');

// includes and security
include_once('_local_auth.inc.php');

// update to sync id & level_id
$db->query('UPDATE user_level SET level_id = id');

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    gFileServerId = null;
    gEditUserLevelId = null;
    gTestFileServerId = null;
    gDeleteFileServerId = null;
    $(document).ready(function () {
        // datatable
        oTable = $('#packagesTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/account_package_manage.ajax.php',
            "iDisplayLength": 25,
            "aaSorting": [[1, "asc"]],
            "aoColumns": [
                {bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide"},
                {bSortable: false},
                {bSortable: false, sWidth: '13%', sClass: "center adminResponsiveHide"},
                {bSortable: false, sWidth: '13%', sClass: "center adminResponsiveHide"},
                {bSortable: false, sWidth: '13%', sClass: "center adminResponsiveHide"},
                {bSortable: false, sWidth: '13%', sClass: "center adminResponsiveHide"},
                {bSortable: false, sWidth: '12%', sClass: "center adminResponsiveHide"},
                {bSortable: false, sWidth: '20%', sClass: "center"}
            ],
            "fnServerData": function (sSource, aoData, fnCallback) {
                aoData.push({"name": "filterText", "value": $('#filterText').val()});
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
                "sEmptyTable": "There are no packages in the current filters."
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

    function editPackageForm(userLevelId)
    {
        gEditUserLevelId = userLevelId;
        showBasicModal('Loading...', 'Edit Package', '<button type="button" class="btn btn-primary" onClick="processAddUserPackage(); return false;">Update Package</button>');
        loadEditUserPackageForm();
    }
    
    function loadEditUserPackageForm()
    {
        $.ajax({
            type: "POST",
            url: "ajax/account_package_manage_add_form.ajax.php",
            data: {gEditUserLevelId: gEditUserLevelId},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    setBasicModalContent(json.msg);
                } else
                {
                    setBasicModalContent(json.html);
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                setBasicModalContent(XMLHttpRequest.responseText);
            }
        });
    }

    function addPackageForm()
    {
        gEditUserLevelId = null;
        showBasicModal('Loading...', 'Add Package', '<button type="button" class="btn btn-primary" onClick="processAddUserPackage(); return false;">Add Package</button>');
        loadAddPackageForm();
    }
    
    function loadAddPackageForm()
    {
        $.ajax({
            type: "POST",
            url: "ajax/account_package_manage_add_form.ajax.php",
            data: {},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    setBasicModalContent(json.msg);
                } else
                {
                    setBasicModalContent(json.html);
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                setBasicModalContent(XMLHttpRequest.responseText);
            }
        });
    }

    function processAddUserPackage()
    {
        // get data
        if (gEditUserLevelId !== null)
        {
            formData = $('#editUserPackageForm').serialize();
        }
        else
        {
            formData = $('#addUserPackageForm').serialize();
        }
        formData += "&existing_user_level_id=" + encodeURIComponent(gEditUserLevelId);

        $.ajax({
            type: "POST",
            url: "ajax/account_package_manage_add_process.ajax.php",
            data: formData,
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    showError(json.msg, 'popupMessageContainer');
                } else
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
    
    function toggleElements(ele)
    {
        var eleId = $(ele).prop('id');
        if($(ele).val() == 1) {
            $('.'+eleId+' input').prop("disabled", false);
        }
        else {
            $('.'+eleId+' input').prop("disabled", true);
        }
    }
    
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
                        <h2>Existing Packages</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <table id="packagesTable" class="table table-striped table-only-border dtLoading bulk_action">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("package_label", "package label")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("users", "users")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("allow_upload", "allow upload")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("max_upload_size", "max upload size")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("storage", "storage")); ?></th>
                                    <th class="align-left"><?php echo UCWords(themeHelper::getCurrentProductType() == 'cloudable'?adminFunctions::t("inactive_files_days", "inactive files days"):adminFunctions::t("on_upgrade_page", "upgrade page")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("action", "action")); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="20"><?php echo adminFunctions::t('admin_loading_data', 'Loading data...'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="clearfix"></div>
                        <br/><p>Note: Only packages which have a "Package Type" of "Paid" have the option to set pricing.</p>
                    </div>
                </div>
                
                <div class="x_panel">
                    <div class="btn-group">
                        <a href="#" type="button" class="btn btn-primary" onClick="addPackageForm(); return false;">New Account Package</a>
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