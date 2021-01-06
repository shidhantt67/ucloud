<?php
// initial constants
define('ADMIN_SELECTED_PAGE', 'users');
define('ADMIN_SELECTED_SUB_PAGE', 'user_manage');

// includes and security
include_once('_local_auth.inc.php');

// load user details
$userId = (int) $_REQUEST['id'];
$user   = $db->getRow("SELECT * FROM users WHERE id = " . (int) $userId . " LIMIT 1");
if (!$user)
{
    adminFunctions::redirect('user_manage.php?error=' . urlencode('There was a problem loading the user details.'));
}
define('ADMIN_PAGE_TITLE', '30 Day Login History for \'' . $user['username'] . '\'');

// get all login data
$loginData = $db->getRows('SELECT login_success.*, country_info.name AS country_name FROM login_success LEFT JOIN country_info ON login_success.country_code = country_info.iso_alpha2 WHERE login_success.user_id = '.(int)$userId.' ORDER BY date_added DESC');

// get data for stats
$totalDifferentIps = (int)$db->getValue('SELECT COUNT(DISTINCT ip_address) FROM login_success WHERE login_success.user_id = '.(int)$userId);
$totalDifferentCountries = (int)$db->getValue('SELECT COUNT(DISTINCT country_code) FROM login_success WHERE login_success.user_id = '.(int)$userId);

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    gPluginId = null;
    $(document).ready(function(){
        // datatable
        oTable = $('#userLoginsTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": false,
            "iDisplayLength": 100,
            "bLengthChange": false,
            "aaSorting": [[ 0, "desc" ]],
            "fnDrawCallback": function (oSettings) {
                postDatatableRender();
            },
            "oLanguage": {
                "sEmptyTable": "There are no logins in the current filters."
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
            <div class="col-md-3 col-sm-3 col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>Account Logins</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <p>Details of any successful account logins for the past 30 days, the IP address and which country it was from.</p>
                        <table class="countries_list">
                            <tr><td><i class="fa fa-lock"></i>Total Logins:</td><td class="fs15 fw700 text-right"><?php echo COUNT($loginData); ?></td></tr>
                            <tr><td><i class="fa fa-share-alt"></i>Different IPs Used:</td><td class="fs15 fw700 text-right"><?php echo $totalDifferentIps; ?></td></tr>
                            <tr><td><i class="fa fa-globe"></i>Different Countries Used:</td><td class="fs15 fw700 text-right"><?php echo $totalDifferentCountries; ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9 col-sm-9 col-xs-12">
                <div class="x_panel">
                    <div class="x_content">
                        <table id="userLoginsTable" class="table table-striped table-only-border bulk_action">
                            <thead>
                                <tr>
                                    <th class='align-left'>Login Date</th>
                                    <th class='align-left'>IP Address</th>
                                    <th class='align-left'>Country</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if(COUNT($loginData) == 0)
                                {
                                    echo '<tr><td>No logins in the past 30 days.</td><td></td><td></td></tr>';
                                }
                                else
                                {
                                    foreach($loginData AS $loginDataItem): ?>
                                    <tr>
                                        <td><?php echo adminFunctions::makeSafe(coreFunctions::formatDate($loginDataItem['date_added'], SITE_CONFIG_DATE_TIME_FORMAT)); ?></td>
                                        <td><?php echo adminFunctions::makeSafe($loginDataItem['ip_address']); ?></td>
                                        <td><?php echo adminFunctions::makeSafe(strlen($loginDataItem['country_name'])?$loginDataItem['country_name']:''); ?></td>
                                    </tr>
                                    <?php endforeach;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="x_panel">
                    <div class="btn-group">
                        <div class="dropup">
                            <a href="user_manage.php?id=<?php echo $userId; ?>" class="btn btn-default dropdown-toggle" type="button">
                                Return to Manage Users
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>