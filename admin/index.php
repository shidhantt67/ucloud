<?php
define('ADMIN_PAGE_TITLE', 'Dashboard');
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('_local_auth.inc.php');

// make sure the install folder has been removed
if(file_exists('../install/'))
{
    adminFunctions::setSuccess("Remove the /install/ folder within your webroot asap.");
}

// should we show a warning about lack of an encryption key
if((isset($_REQUEST['shash'])) && (!defined('_CONFIG_UNIQUE_ENCRYPTION_KEY')))
{
    // check for write permissions
    $configFile = '../_config.inc.php';
    if(!is_writable($configFile))
    {
        adminFunctions::setError("The site config file (_config.inc.php) is not writable (CHMOD 777 or 755). Please update and <a href='index.php?shash=1'>try again</a>.");
    }
    else
    {
        // try to set _config file
        $oldContent = file_get_contents($configFile);
        if(strlen($oldContent))
        {
            $newHash = coreFunctions::generateRandomString(125, "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890");
            if(strlen($newHash))
            {
                $newHashLine = "/* key used for encoding data within the site */\ndefine(\"_CONFIG_UNIQUE_ENCRYPTION_KEY\", \"" . $newHash . "\");\n";
                $newContent = $oldContent . "\n\n" . $newHashLine;

                // write new file contents
                $rs = file_put_contents($configFile, $newContent);
                if($rs)
                {
                    adminFunctions::setSuccess("Security key set, please revert the permissions on your _config.inc.php file. If you run external file servers, please copy the new '_CONFIG_UNIQUE_ENCRYPTION_KEY' line in your _config.inc.php file onto each file server config file. The key should be the same on all servers.");
                }
            }
        }
    }
}
elseif(!defined('_CONFIG_UNIQUE_ENCRYPTION_KEY'))
{
    adminFunctions::setError("<strong>IMPORTANT:</strong> The latest code offers enhanced security by encrypting certain values before storing them within the database. The key for this needs set within your _config.inc.php file. To automatically create this, set write permissions on _config.inc.php (CHMOD 777 or 755) and <a href='index.php?shash=1'>click here</a>.");
}

include_once('_header.inc.php');

// load stats
$totalActiveFiles = (int) $db->getValue("SELECT COUNT(1) AS total FROM file WHERE status = 'active'");
$totalDownloads = (int) $db->getValue("SELECT SUM(visits) AS total FROM file");
$totalHDSpace = $db->getValue("SELECT SUM(file_server.totalSpaceUsed) FROM file_server");
$totalRegisteredUsers = (int) $db->getValue("SELECT COUNT(1) AS total FROM users WHERE status='active'");
$totalPaidUsers = (int) $db->getValue("SELECT COUNT(1) AS total FROM users WHERE status='active' AND level_id IN (SELECT id FROM user_level WHERE level_type = 'paid')");
$totalReports = (int) $db->getValue("SELECT COUNT(1) AS total FROM file_report WHERE report_status='pending'");
$payments30Days = $db->getRows("SELECT SUM(amount) AS total, currency_code FROM payment_log WHERE date_created BETWEEN NOW() - INTERVAL 30 DAY AND NOW() GROUP BY currency_code");

$topBoxSize = 2;
if(themeHelper::getCurrentProductType() == 'cloudable') {
    $topBoxSize = 3;
}
?>

<script>
// check for script upgrades
    $(document).ready(function () {
        $.ajax({
            url: "ajax/check_for_upgrade.ajax.php",
            dataType: "json"
        }).done(function (response) {
            if (typeof(response['core']) != "undefined")
            {
                showInfo("There is an update available to "+response['core']['latest_version']+" of your core script. Please login to your account to access the update.");
            }
        });

        loadCharts();
    });

    function loadCharts()
    {
        $('#wrapper_14_day_chart').load('ajax/_dashboard_chart_14_day_chart.ajax.php');
        $('#wrapper_file_status_chart').load('ajax/_dashboard_chart_file_status_chart.ajax.php');
        $('#wrapper_12_month_chart').load('ajax/_dashboard_chart_12_months_chart.ajax.php');
        $('#wrapper_file_type_chart').load('ajax/_dashboard_chart_file_type_chart.ajax.php');
        $('#wrapper_14_day_users').load('ajax/_dashboard_chart_14_day_users.ajax.php');
        $('#wrapper_user_status_chart').load('ajax/_dashboard_chart_user_status_chart.ajax.php');
    }
</script>

<!-- page content -->
<div class="right_col" role="main">
    <!-- top tiles -->
    <div class="row tile_count">
        <div class="col-md-<?php echo (int)$topBoxSize; ?> col-sm-4 col-xs-6 tile_stats_count">
            <a href="file_manage.php">
                <span class="count_top"><i class="fa fa-file-o"></i> Active Files</span>
                <div class="count green"><?php echo $totalActiveFiles; ?></div>
            </a>
        </div>
        <div class="col-md-<?php echo (int)$topBoxSize; ?> col-sm-4 col-xs-6 tile_stats_count">
            <a href="<?php if($Auth->hasAccessLevel(20)): ?>server_manage.php<?php else: ?>#<?php endif; ?>">
                <span class="count_top"><i class="fa fa-clock-o"></i> Total Space Used</span>
                <div class="count green"><?php echo adminFunctions::formatSize($totalHDSpace, 0); ?></div>
            </a>
        </div>
        <div class="col-md-<?php echo (int)$topBoxSize; ?> col-sm-4 col-xs-6 tile_stats_count">
            <a href="file_manage.php">
                <span class="count_top"><i class="fa fa-download"></i> Total Downloads</span>
                <div class="count green"><?php echo $totalDownloads; ?></div>
            </a>
        </div>
        <?php if($Auth->hasAccessLevel(20)): ?>         
            <div class="col-md-<?php echo (int)$topBoxSize; ?> col-sm-4 col-xs-6 tile_stats_count">
                <a href="user_manage.php?filterByAccountStatus=active">
                    <span class="count_top"><i class="fa fa-user"></i> Total Registered/Paid Users</span>
                    <div class="count green"><?php echo $totalRegisteredUsers; ?>/<?php echo $totalPaidUsers; ?></div>
                </a>
            </div>
        <?php endif; ?>
        
        <div class="col-md-<?php echo (int)$topBoxSize; ?> col-sm-4 col-xs-6 tile_stats_count paid-account-option">
            <a href="file_report_manage.php?filterByReportStatus=pending">
                <span class="count_top"><i class="fa fa-support"></i> Pending Reports</span>
                <div class="count green"><?php echo $totalReports; ?></div>
            </a>
        </div>
        
        <?php if($Auth->hasAccessLevel(20)): ?>
        <?php if(COUNT($payments30Days) == 0): ?>
        <div class="col-md-<?php echo (int)$topBoxSize; ?> col-sm-4 col-xs-6 tile_stats_count">
            <a href="payment_manage.php" class="paid-account-option">
                <span class="count_top"><i class="fa fa-credit-card"></i> 30 Day Payments</span>
                <div class="count green"><?php echo SITE_CONFIG_COST_CURRENCY_SYMBOL; ?> 0</div>
 
            </a>
        </div>
        <?php else: ?>
        <?php foreach($payments30Days AS $payments30Day): ?>
        <div class="col-md-<?php echo (int)$topBoxSize; ?> col-sm-4 col-xs-6 tile_stats_count">
            <a href="payment_manage.php" class="paid-account-option">
                <span class="count_top"><i class="fa fa-credit-card"></i> 30 Day Payments</span>
                <div class="count green"><?php echo number_format($payments30Day['total'], 0, '.', '').' '.$payments30Day['currency_code']; ?></div>
            </a>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php endif; ?>
        <div class="clear"></div>
    </div>
    <!-- /top tiles -->

    <div class="row">
        <div class="col-md-8 col-sm-8 col-xs-12">
            <div class="x_panel tile fixed_height_320">
                <div class="x_title">
                    <h2>New Files <small>Last 14 Days</small></h2>
                    <div class="clearfix"></div>
                </div>

                <div class="x_content">
                    <div id="placeholder33" style="height: 234px; display: none" class="demo-placeholder"></div>
                    <div style="width: 100%;">
                        <div id="canvas_dahs" class="demo-placeholder" style="width: 100%; height:244px;"></div>
                    </div>
                    <span id="wrapper_14_day_chart"></span>
                </div>
            </div>
        </div>
        
        
        <div class="col-md-4 col-sm-4 col-xs-12">
            <div class="x_panel tile fixed_height_320 wrapper_file_status_chart">
                <div class="x_title">
                    <h2>File Status</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <table class="" style="width:100%">
                        <tr>
                            <th style="width:37%;">
                                &nbsp;
                            </th>
                            <th>
                                <div class="col-lg-7 col-md-7 col-sm-7 col-xs-7">
                                    <p class="">Status</p>
                                </div>
                                <div class="col-lg-5 col-md-5 col-sm-5 col-xs-5 text-right">
                                    <p class="">Total Files</p>
                                </div>
                            </th>
                        </tr>
                        <tr>
                            <td>
                                <canvas id="canvas1" height="140" width="140" style="margin: 15px 10px 10px 0"></canvas>
                                <span id="wrapper_file_status_chart"></div>
                            </td>
                            <td>
                                <table class="tile_info">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>

    <br/>
    
    <div class="row">
        <div class="col-md-8 col-sm-8 col-xs-12">
            <div class="x_panel tile fixed_height_320">
                <div class="x_title">
                    <h2>New Files <small>Last 12 Months</small></h2>
                    <div class="clearfix"></div>
                </div>

                <div class="x_content">
                    <div id="placeholder33" style="height: 234px; display: none" class="demo-placeholder"></div>
                    <div style="width: 100%;">
                        <div id="canvas_dahs2" class="demo-placeholder" style="width: 100%; height:244px;"></div>
                    </div>
                    <span id="wrapper_12_month_chart"></span>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 col-sm-4 col-xs-12">
            <div class="x_panel tile fixed_height_320 wrapper_file_type_chart">
                <div class="x_title">
                    <h2>File Type</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <table class="" style="width:100%">
                        <tr>
                            <th style="width:37%;">
                                &nbsp;
                            </th>
                            <th>
                                <div class="col-lg-7 col-md-7 col-sm-7 col-xs-7">
                                    <p class="">Type</p>
                                </div>
                                <div class="col-lg-5 col-md-5 col-sm-5 col-xs-5 text-right">
                                    <p class="">Total Files</p>
                                </div>
                            </th>
                        </tr>
                        <tr>
                            <td>
                                <canvas id="canvas2" height="140" width="140" style="margin: 15px 10px 10px 0"></canvas>
                                <span id="wrapper_file_type_chart"></div>
                            </td>
                            <td>
                                <table class="tile_info">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>
 
    <?php if(themeHelper::getCurrentProductType() != 'cloudable'): ?>
    <br />
    
    <div class="row">
        <div class="col-md-8 col-sm-8 col-xs-12">
            <div class="x_panel tile fixed_height_320">
                <div class="x_title">
                    <h2>New Users <small>Last 14 Days</small></h2>
                    <div class="clearfix"></div>
                </div>

                <div class="x_content">
                    <div id="placeholder33" style="height: 234px; display: none" class="demo-placeholder"></div>
                    <div style="width: 100%;">
                        <div id="canvas_dahs3" class="demo-placeholder" style="width: 100%; height:244px;"></div>
                    </div>
                    <span id="wrapper_14_day_users"></span>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 col-sm-4 col-xs-12">
            <div class="x_panel tile fixed_height_320 wrapper_user_status_chart">
                <div class="x_title">
                    <h2>User Status</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <table class="" style="width:100%">
                        <tr>
                            <th style="width:37%;">
                                &nbsp;
                            </th>
                            <th>
                                <div class="col-lg-7 col-md-7 col-sm-7 col-xs-7">
                                    <p class="">Status</p>
                                </div>
                                <div class="col-lg-5 col-md-5 col-sm-5 col-xs-5 text-right">
                                    <p class="">Total Users<p>
                                </div>
                            </th>
                        </tr>
                        <tr>
                            <td>
                                <canvas id="canvas3" height="140" width="140" style="margin: 15px 10px 10px 0"></canvas>
                                <span id="wrapper_user_status_chart"></div>
                            </td>
                            <td>
                                <table class="tile_info">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<!-- /page content -->

<!-- FastClick -->
<script src="assets/vendors/fastclick/lib/fastclick.js"></script>
<!-- NProgress -->
<script src="assets/vendors/nprogress/nprogress.js"></script>
<!-- Chart.js -->
<script src="assets/vendors/Chart.js/dist/Chart.min.js"></script>
<!-- gauge.js -->
<script src="assets/vendors/gauge.js/dist/gauge.min.js"></script>
<!-- bootstrap-progressbar -->
<script src="assets/vendors/bootstrap-progressbar/bootstrap-progressbar.min.js"></script>
<!-- iCheck -->
<script src="assets/vendors/iCheck/icheck.min.js"></script>
<!-- Skycons -->
<script src="assets/vendors/skycons/skycons.js"></script>
<!-- Flot -->
<script src="assets/vendors/Flot/jquery.flot.js"></script>
<script src="assets/vendors/Flot/jquery.flot.pie.js"></script>
<script src="assets/vendors/Flot/jquery.flot.time.js"></script>
<script src="assets/vendors/Flot/jquery.flot.stack.js"></script>
<script src="assets/vendors/Flot/jquery.flot.resize.js"></script>
<!-- Flot plugins -->
<script src="assets/vendors/flot.orderbars/js/jquery.flot.orderBars.js"></script>
<script src="assets/vendors/flot-spline/js/jquery.flot.spline.min.js"></script>
<script src="assets/vendors/flot.curvedlines/curvedLines.js"></script>
<!-- DateJS -->
<script src="assets/vendors/DateJS/build/date.js"></script>
<!-- JQVMap -->
<script src="assets/vendors/jqvmap/dist/jquery.vmap.js"></script>
<script src="assets/vendors/jqvmap/dist/maps/jquery.vmap.world.js"></script>
<script src="assets/vendors/jqvmap/examples/js/jquery.vmap.sampledata.js"></script>
<!-- bootstrap-daterangepicker -->
<script src="assets/vendors/moment/min/moment.min.js"></script>
<script src="assets/vendors/bootstrap-daterangepicker/daterangepicker.js"></script>

<?php
include_once('_footer.inc.php');
?>