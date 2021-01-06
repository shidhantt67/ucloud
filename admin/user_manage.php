<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Manage Users');
define('ADMIN_SELECTED_PAGE', 'users');
define('ADMIN_SELECTED_SUB_PAGE', 'user_manage');

// includes and security
include_once('_local_auth.inc.php');

// handle impersonate requests
if(isset($_REQUEST['impersonate']))
{
    $impUserId = (int) $_REQUEST['impersonate'];
    if($impUserId)
    {
        // load user
        $impUser = UserPeer::loadUserById($impUserId);
        if($impUser)
        {
            // make sure they are not an admin user for security purposes
            $userType = UserPeer::getUserLevelValue('level_type', $impUser->level_id);
            if($userType != 'admin')
            {
                // fine to impersonate user
                $_SESSION['_old_user'] = $_SESSION['user'];
                $rs = $Auth->impersonate($impUserId);
                if($rs)
                {
                    // redirect to customer file manager
                    coreFunctions::redirect(WEB_ROOT . '/account_home.html');
                }
                else
                {
                    // failed impersonating user
                    unset($_SESSION['_old_user']);
                    adminFunctions::setError("Failed impersonating user account, please try again later.");
                }
            }
        }
    }
}

// page header
include_once('_header.inc.php');

// account types
$accountTypeDetails = $db->getRows('SELECT id, level_id, label FROM user_level WHERE id > 0 ORDER BY level_id ASC');

// account status
$accountStatusDetails = array('active', 'pending', 'disabled', 'suspended');

// error/success messages
if(isset($_REQUEST['sa']))
{
    adminFunctions::setSuccess('New user successfully added.');
}
elseif(isset($_REQUEST['se']))
{
    adminFunctions::setSuccess('User successfully updated.');
}
elseif(isset($_REQUEST['error']))
{
    adminFunctions::setError(urldecode($_REQUEST['error']));
}

// get any params
$filterByAccountType = '';
if(isset($_REQUEST['filterByAccountType']))
{
    $filterByAccountType = trim($_REQUEST['filterByAccountType']);
}

$filterByAccountStatus = 'active';
if(isset($_REQUEST['filterByAccountStatus']))
{
    $filterByAccountStatus = trim($_REQUEST['filterByAccountStatus']);
}
?>

<script>
    oTable = null;
    gUserId = null;
    oldStart = 0;
    $(document).ready(function () {
        // datatable
        oTable = $('#userTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/user_manage.ajax.php',
            "iDisplayLength": 25,
            "aaSorting": [[1, "asc"]],
            "aoColumns": [
                {bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide"},
                {sName: 'username'},
                {sName: 'email_address', sClass: "adminResponsiveHide"},
                {sName: 'account_type', sWidth: '10%', sClass: "center adminResponsiveHide"},
                {sName: 'last_login', sWidth: '15%', sClass: "center adminResponsiveHide"},
                {sName: 'space_used', sWidth: '9%', sClass: "center adminResponsiveHide"},
                {sName: 'total_files', sWidth: '8%', sClass: "center adminResponsiveHide"},
                {sName: 'status', sWidth: '10%', sClass: "center adminResponsiveHide"},
                {bSortable: false, sWidth: '16%', sClass: "center dataTableFix responsiveTableColumn"}
            ],
            "fnServerData": function (sSource, aoData, fnCallback, oSettings) {
                setTableLoading();
                if ( oSettings._iDisplayStart != oldStart ) {
                    var targetOffset = $('.dataTables_wrapper').offset().top-10;
                    $('html, body').animate({scrollTop: targetOffset}, 300);
                    oldStart = oSettings._iDisplayStart;
                }
                aoData.push({"name": "filterText", "value": $('#filterText').val()});
                aoData.push({"name": "filterByAccountType", "value": $('#filterByAccountType').val()});
                aoData.push({"name": "filterByAccountStatus", "value": $('#filterByAccountStatus').val()});
                aoData.push({"name": "filterByAccountId", "value": $('#filterByAccountId').val()});
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
    });

    function reloadTable()
    {
        oTable.fnDraw(false);
    }

    function confirmRemoveUser(userId)
    {
        showBasicModal('<p>Are you sure you want to permanently remove this user? All files and data relating to the user will be removed. This can not be undone.</p>', 'Remove User', '<button type="button" class="btn btn-primary" data-dismiss="modal" onClick="removeUser('+userId+'); return false;">Remove</button>');
        return false;
    }

    function removeUser(userId)
    {
        setCurrentUserId(userId);
        bulkDeleteConfirm();
    }

    var bulkError = '';
    var bulkSuccess = '';
    var totalDone = 0;
    var currentUserId = 0;
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
    function setCurrentUserId(userId)
    {
        currentUserId = userId;
    }
    function getCurrentUserId(userId)
    {
        return currentUserId;
    }
    function bulkDeleteConfirm(userId)
    {
        // get server list for deleting all files
        $.ajax({
            type: "POST",
            url: "ajax/file_manage_bulk_delete.ajax.php",
            data: {userId: getCurrentUserId()},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
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
        // delete actual user
        $.ajax({
            type: "POST",
            url: "ajax/user_remove.ajax.php",
            data: {userId: getCurrentUserId()},
            dataType: 'json',
            success: function (json) {
                // compile result
                if (json.error == true)
                {
                    showError(json.msg);
                } else
                {
                    showSuccess(json.msg);
                }
                tidyBulkProcess();
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError('Failed deleting user, please try again later.');
                tidyBulkProcess();
            }
        });
    }

    function tidyBulkProcess()
    {
        reloadTable();
        clearBulkResponses();

        // scroll to the top of the page
        $("html, body").animate({scrollTop: 0}, "slow");
        $('#selectAllCB').prop('checked', false);
    }

    function confirmImpersonateUser(userId)
    {
        if (confirm("Are you sure you want to login as this user account? You'll have access to their account as they would see it.\n\nWhen you logout of the impersonated user, you'll be reverted to this admin user account again."))
        {
            window.location = "user_manage.php?impersonate=" + userId;
            return true;
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
                        <h2>User List</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <table id="userTable" class="table table-striped table-only-border dtLoading bulk_action">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="align-left"><?php echo adminFunctions::t('username', 'Username'); ?></th>
                                    <th><?php echo adminFunctions::t('email_address', 'Email Address'); ?></th>
                                    <th><?php echo adminFunctions::t('type', 'Type'); ?></th>
                                    <th class="align-left"><?php echo adminFunctions::t('last_login', 'Last Login'); ?></th>
                                    <th class="align-left"><?php echo adminFunctions::t('space_used', 'HD Used'); ?></th>
                                    <th><?php echo adminFunctions::t('files', 'Files'); ?></th>
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
                    <div class="btn-group">
                        <div class="dropup">
                            <a href="user_add.php" class="btn btn-default dropdown-toggle" type="button">
                                Add User
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="customFilter" id="customFilter" style="display: none;">
    <label>
        Filter Results:
        <input name="filterText" id="filterText" type="text" class="form-control" value="<?php echo isset($_REQUEST['filterText']) ? adminFunctions::makeSafe($_REQUEST['filterText']) : ''; ?>" onKeyUp="reloadTable(); return false;" style="width: 160px;"/>
    </label>
    <label class="adminResponsiveHide" style="padding-left: 6px;">
        By Type:
        <select name="filterByAccountType" id="filterByAccountType" onChange="reloadTable();
                return false;" style="width: 160px;" class="form-control">
            <option value="">- all -</option>
<?php
if(COUNT($accountTypeDetails))
{
    foreach($accountTypeDetails AS $accountTypeDetail)
    {
        echo '<option value="' . $accountTypeDetail['id'] . '"';
        if(($filterByAccountType) && ($filterByAccountType == $accountTypeDetail['id']))
        {
            echo ' SELECTED';
        }
        echo '>' . UCWords($accountTypeDetail['label']) . '</option>';
    }
}
?>
        </select>
    </label>
    <label class="adminResponsiveHide" style="padding-left: 6px;">
        By Status:
        <select name="filterByAccountStatus" id="filterByAccountStatus" onChange="reloadTable();
                return false;" style="width: 120px;" class="form-control">
            <option value="">- all -</option>
<?php
if(COUNT($accountStatusDetails))
{
    foreach($accountStatusDetails AS $accountStatusDetail)
    {
        echo '<option value="' . $accountStatusDetail . '"';
        if(($filterByAccountStatus) && ($filterByAccountStatus == $accountStatusDetail))
        {
            echo ' SELECTED';
        }
        echo '>' . UCWords($accountStatusDetail) . '</option>';
    }
}
?>
        </select>
    </label>
    <input type="hidden" value="<?php echo isset($_REQUEST['filterByAccountId']) ? adminFunctions::makeSafe($_REQUEST['filterByAccountId']) : ''; ?>" name="filterByAccountId" id="filterByAccountId"/>
</div>

<?php
include_once('_footer.inc.php');
?>