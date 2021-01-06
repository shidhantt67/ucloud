<?php
// includes and security
include_once('_local_auth.inc.php');

// handle deletes
if(isset($_REQUEST['del']))
{
    if(_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(t("no_changes_in_demo_mode"));
    }
    else
    {
        $db->query('DELETE FROM user_level_pricing WHERE id = ' . (int) $_REQUEST['del'] . ' LIMIT 1');
        adminFunctions::setSuccess('The package pricing item has been removed.');
    }
}

$appendTitle = '';
if(isset($_REQUEST['level_id']))
{
    $packageName = $db->getValue('SELECT label FROM user_level WHERE level_id = ' . (int) $_REQUEST['level_id'] . ' LIMIT 1');
    if($packageName)
    {
        $appendTitle = ' for "' . UCWords($packageName) . '"';
    }
}

// initial constants
define('ADMIN_PAGE_TITLE', 'Account Type Pricing' . $appendTitle);
define('ADMIN_SELECTED_PAGE', 'account_package_pricing');
define('ADMIN_SELECTED_SUB_PAGE', 'account_package_pricing');

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    gEditPricingId = null;
    $(document).ready(function () {
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/account_package_pricing_manage.ajax.php<?php echo (isset($_REQUEST['level_id'])) ? ('?level_id=' . (int) $_REQUEST['level_id']) : ''; ?>',
            "iDisplayLength": 25,
            "aaSorting": [[1, "asc"]],
            "aoColumns": [
                {bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide"},
                {bSortable: false, sWidth: '18%', sClass: "adminResponsiveHide"},
                {bSortable: false},
                {bSortable: false, sWidth: '30%', sClass: "adminResponsiveHide"},
                {bSortable: false, sWidth: '11%', sClass: "center"},
                {bSortable: false, sWidth: '11%', sClass: "center adminResponsiveHide"}
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

    function addNewPricingForm()
    {
        showBasicModal('Loading...', 'Add Pricing', '<button type="button" class="btn btn-primary" onClick="processAddPricing(); return false;">Add Pricing</button>');
        loadAddNewPricingForm();
    }

    function loadAddNewPricingForm()
    {
        $.ajax({
            type: "POST",
            url: "ajax/account_package_pricing_manage_add_form.ajax.php",
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
                updateAddPricingOpt();
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                setBasicModalContent(XMLHttpRequest.responseText);
            }
        });
    }

    function processAddPricing()
    {
        // get data
        existing_pricing_id = gEditPricingId;
        pricing_label = $('#pricing_label').val();
        package_pricing_type = $('#package_pricing_type').val();
        period = null;
        download_allowance = null;
        if (package_pricing_type == 'period')
        {
            period = $('#period').val();
        } else
        {
            download_allowance = $('#download_allowance').val();
        }
        user_level_id = $('#user_level_id').val();
        price = $('#price').val();

        $.ajax({
            type: "POST",
            url: "ajax/account_package_pricing_manage_add_process.ajax.php",
            data: {existing_pricing_id: existing_pricing_id, pricing_label: pricing_label, package_pricing_type: package_pricing_type, period: period, download_allowance: download_allowance, user_level_id: user_level_id, price: price},
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

    function editPackagePricingForm(pricingId)
    {
        gEditPricingId = pricingId;
        showBasicModal('Loading...', 'Edit Pricing', '<button type="button" class="btn btn-primary" onClick="processAddPricing(); return false;">Update Pricing</button>');
        loadEditPackagePricingForm();
    }
    
    function loadEditPackagePricingForm()
    {
        $.ajax({
            type: "POST",
            url: "ajax/account_package_pricing_manage_add_form.ajax.php",
            data: {gEditPricingId: gEditPricingId},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    setBasicModalContent(json.msg);
                } else
                {
                    setBasicModalContent(json.html);
                }
                updateAddPricingOpt();
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                setBasicModalContent(XMLHttpRequest.responseText);
            }
        });
    }

    function reloadTable()
    {
        oTable.fnDraw(false);
    }

    function updateAddPricingOpt()
    {
        if ($('#addPricingForm #package_pricing_type').val() == 'bandwidth')
        {
            $('.period-class').hide();
            $('.bandwidth-class').show();
        } else
        {
            $('.bandwidth-class').hide();
            $('.period-class').show();
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
                        <h2>Package Pricing</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <table id="fileTable" class="table table-striped table-only-border dtLoading bulk_action">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("pricing_label", "pricing label")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("account_package", "account package")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("package_type", "package type")); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t("price", "price")); ?></th>
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
                
                <div class="x_panel">
                    <a href="account_package_manage.php" type="button" class="btn btn-default">< Back to Packages</a>
                    <a href="#" type="button" class="btn btn-primary" onClick="addNewPricingForm(); return false;">Add New Pricing</a>
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