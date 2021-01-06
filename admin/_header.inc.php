<?php
if(!defined('ADMIN_PAGE_TITLE'))
{
    define('ADMIN_PAGE_TITLE', adminFunctions::t("admin_area", "admin area"));
}
if(!defined('ADMIN_SELECTED_PAGE'))
{
    define('ADMIN_SELECTED_PAGE', 'dashboard');
}
if(!defined('ADMIN_SELECTED_SUB_PAGE'))
{
    define('ADMIN_SELECTED_SUB_PAGE', 'dashboard');
}
$AuthUser = Auth::getAuth();
$db = Database::getDatabase();

// load totals for navigation
$totalReports = (int) $db->getValue("SELECT COUNT(id) AS total FROM file_report WHERE report_status='pending'");
$totalPendingFileActions = (int) $db->getValue('SELECT COUNT(id) AS total FROM file_action WHERE status=\'pending\' OR status=\'processing\'');

// load all config groups for navigation
$groupDetails = $db->getRows("SELECT config_group FROM site_config WHERE config_group != 'system' GROUP BY config_group ORDER BY config_group");
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <!-- Meta, title, CSS, favicons, etc. -->
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title><?php echo adminFunctions::makeSafe(UCwords(ADMIN_PAGE_TITLE)); ?> - Admin</title>

        <!-- Bootstrap -->
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Font Awesome -->
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
        <!-- NProgress -->
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/nprogress/nprogress.css" rel="stylesheet">
        <!-- iCheck -->
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/iCheck/skins/flat/green.css" rel="stylesheet">
        <!-- bootstrap-wysiwyg -->
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/google-code-prettify/bin/prettify.min.css" rel="stylesheet">
        <!-- Select2 -->
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/select2/dist/css/select2.min.css" rel="stylesheet">
        <!-- Switchery -->
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/switchery/dist/switchery.min.css" rel="stylesheet">
        <!-- starrr -->
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/starrr/dist/starrr.css" rel="stylesheet">
        <!-- bootstrap-daterangepicker -->
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
        <!-- Datatables -->
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css" rel="stylesheet">
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css" rel="stylesheet">
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css" rel="stylesheet">
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css" rel="stylesheet">
        <!-- PNotify -->
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/pnotify/dist/pnotify.css" rel="stylesheet">
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/pnotify/dist/pnotify.buttons.css" rel="stylesheet">
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/pnotify/dist/pnotify.nonblock.css" rel="stylesheet">
    
        <!-- Custom Theme Styles -->
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/css/responsive.css" rel="stylesheet">
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/css/custom.css" rel="stylesheet">
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/css/pre_v44_compat.css" rel="stylesheet">
        
        <!-- Colour Skin -->
        <!--<link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/css/skins/brown.css" rel="stylesheet">-->

        <!-- append any theme admin css -->
        <?php
        $adminThemeCss = themeHelper::getAdminThemeCss();
        if($adminThemeCss)
        {
            echo '<link rel="stylesheet" href="' . $adminThemeCss . '" type="text/css" media="screen" />';
        }
        ?>
        
        <script type="text/javascript">
            var ADMIN_WEB_ROOT = "<?php echo ADMIN_WEB_ROOT; ?>";
        </script>
        
        <!-- jQuery -->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/jquery/dist/jquery.min.js"></script>
        <!-- Bootstrap -->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
        <!-- Pre v4.4 compatibility, i.e. third party plugins -->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/js/pre_v44_compat.js"></script>
    </head>

    <body class="nav-md">
        <div class="container body">
            <div class="main_container">
                <div class="col-md-3 left_col">
                    <div class="left_col scroll-view">
                        <div class="navbar nav_title" style="border: 0;">
                            <a href="<?php echo ADMIN_WEB_ROOT; ?>/index.php" class="site_title"><i class="fa fa-cogs"></i> <span><?php echo UCWords(adminFunctions::t("site_admin", "site admin")); ?></span></a>
                        </div>

                        <div class="clearfix"></div>

                        <!-- menu profile quick info -->
                        <div class="profile">
                            <div class="profile_pic">
                                <img src="<?php echo ADMIN_WEB_ROOT; ?>/ajax/account_view_avatar.ajax.php?userId=<?php echo (int)$AuthUser->id; ?>&width=44&height=44" alt="..." class="img-circle profile_img" style="width: 56px; height: 56px;">
                            </div>
                            <div class="profile_info">
                                <span>Welcome,</span>
                                <h2><?php echo adminFunctions::makeSafe($AuthUser->getAccountScreenName()); ?></h2>
                            </div>
                        </div>
                        <!-- /menu profile quick info -->

                        <br />

                        <!-- sidebar menu -->
                        <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
                            <div class="menu_section">
                                <h3>&nbsp;</h3>
                                <ul class="nav side-menu">
                                    <li class="<?php echo (ADMIN_SELECTED_PAGE == 'dashboard')?'active':''; ?>">
                                        <a href="<?php echo ADMIN_WEB_ROOT; ?>/index.php?t=dashboard"><i class="fa fa-home"></i> <?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('dashboard', 'dashboard')))); ?></a>
                                    </li>
                                    <li class="<?php echo ((ADMIN_SELECTED_PAGE == 'files') || (ADMIN_SELECTED_PAGE == 'downloads'))?'active':''; ?>">
                                        <a><i class="fa fa-cloud-upload"></i> <?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('files', 'files')))); ?> <span class="fa fa-chevron-down"></span></a>
                                        <ul class="nav child_menu">
                                            <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/file_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('manage_files', 'manage files')))); ?></a></li>
                                            <?php if($AuthUser->hasAccessLevel(20)): ?>
                                                <li class="nav_active_downloads"><a href="<?php echo ADMIN_WEB_ROOT; ?>/download_current.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('active_downloads', 'active downloads')))); ?></a></li>
                                                <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/file_manage_action_queue.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('action_queue', 'action queue')))); ?> (<?php echo $totalPendingFileActions; ?>)</a></li>
                                            <?php endif; ?>
                                            <li class="nav_abuse_reports">
                                                <a><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('abuse_reports', 'abuse reports')))); ?><?php if($totalReports > 0): ?> (<?php echo $totalReports; ?>)<?php endif; ?><span class="fa fa-chevron-down"></span></a>
                                                <ul class="nav child_menu">
                                                    <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/file_report_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('manage_reports', 'manage reports')))); ?><?php if($totalReports > 0): ?> (<?php echo $totalReports; ?>)<?php endif; ?></a></li>
                                                    <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/file_report_manage_bulk_remove.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('bulk_remove_abuse_reports', 'bulk remove')))); ?></a></li>
                                                </ul>
                                            </li>
                                            <?php if($AuthUser->hasAccessLevel(20)): ?>
                                                <li><a href="<?php echo PLUGIN_WEB_ROOT; ?>/fileimport/admin/settings.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('bulk_import', 'bulk import')))); ?></a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </li>
                                    <?php if($AuthUser->hasAccessLevel(20)): ?>
                                        <li class="<?php echo (ADMIN_SELECTED_PAGE == 'users')?'active':''; ?>">
                                            <a><i class="fa fa-users"></i> <?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('users', 'users')))); ?> <span class="fa fa-chevron-down"></span></a>
                                            <ul class="nav child_menu">
                                                <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/user_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('manage_users', 'manage users')))); ?></a></li>
                                                <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/user_add.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('add_user', 'add user')))); ?></a></li>
                                                <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/payment.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('payment', 'payment')))); ?></a></li>
                                                
                                                <li class="nav_received_payments">
                                                    <a><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('received_payments', 'received payments')))); ?><span class="fa fa-chevron-down"></span></a>
                                                    <ul class="nav child_menu">
                                                        <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/payment_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('list_payments', 'list payments')))); ?></a></li>
                                                        <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/payment_manage.php?log=1"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('log_payment', 'log payment')))); ?></a></li>
                                                    </ul>
                                                </li>
                                                <li class="nav_subscriptions"><a href="<?php echo ADMIN_WEB_ROOT; ?>/payment_subscription_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('manage_subscriptions', 'manage subscriptions')))); ?></a></li>
                                            </ul>
                                        </li>
                                        <li class="<?php echo (ADMIN_SELECTED_PAGE == 'file_servers')?'active':''; ?>">
                                            <a><i class="fa fa-hdd-o"></i> <?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('file_servers', 'file servers')))); ?> <span class="fa fa-chevron-down"></span></a>
                                            <ul class="nav child_menu">
                                                <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/server_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('manage_file_servers', 'manage file servers')))); ?></a></li>
                                                <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/server_manage.php?add=1"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('add_file_server', 'add file server')))); ?></a></li>
                                            </ul>
                                        </li>
                                    <?php endif; ?>

                                    <?php if($AuthUser->hasAccessLevel(20)): ?>
                                        <?php
                                        $sQL = "SELECT id, folder_name, plugin_name FROM plugin WHERE is_installed = 1 ORDER BY plugin_name";
                                        $pluginList = $db->getRows($sQL);
                                        ?>

                                        <li class="<?php echo (ADMIN_SELECTED_PAGE == 'plugins')?'active':''; ?> nav_plugins">
                                            <a><i class="fa fa-plug"></i> <?php echo adminFunctions::t("plugins", "Plugins"); ?> <span class="fa fa-chevron-down"></span></a>
                                            <ul class="nav child_menu">
                                                <li>
                                                    <a href="<?php echo ADMIN_WEB_ROOT; ?>/plugin_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('manage_plugins', 'manage plugins')))); ?></a>
                                                </li>
                                                <li>
                                                    <a><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('plugin_settings', 'plugin settings')))); ?> <span class="fa fa-chevron-down"></span></a>
                                                    <?php if(COUNT($pluginList)): ?>
                                                        <ul class="nav child_menu">
                                                            <?php
                                                            foreach($pluginList AS $k => $pluginItem)
                                                            {
                                                                if($k < 10)
                                                                {
                                                                    ?>
                                                                    <li><a href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo adminFunctions::makeSafe($pluginItem['folder_name']); ?>/admin/settings.php?id=<?php echo (int) $pluginItem['id']; ?>"><?php echo adminFunctions::makeSafe($pluginItem['plugin_name']); ?></a></li>
                                                                    <?php
                                                                }
                                                            }
                                                            ?>
                                                            <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/plugin_manage.php"><?php echo adminFunctions::t("more", "more...."); ?></a></li>
                                                        </ul>
                                                    <?php endif; ?>
                                                </li>
                                                <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/plugin_manage_add.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('add_plugin', 'add plugin')))); ?></a></li>
                                                <li><a href="<?php echo themeHelper::getCurrentProductUrl(); ?>/plugins.html" target="_blank"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('get_plugin', 'get plugins')))); ?></a></li>
                                            </ul>
                                        </li>

                                        <li class="<?php echo (ADMIN_SELECTED_PAGE == 'themes')?'active':''; ?>">
                                            <a><i class="fa fa-photo"></i> <?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('themes', 'themes')))); ?> <span class="fa fa-chevron-down"></span></a>
                                            <ul class="nav child_menu">
                                                <li><a href="<?php echo SITE_THEME_PATH; ?>/admin/settings.php"><?php echo adminFunctions::makeSafe(themeHelper::getCurrentThemeName()); ?></a></li>
                                                <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/theme_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('manage_themes', 'manage themes')))); ?></a></li>
                                                <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/theme_manage_add.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('add_theme', 'add theme')))); ?></a></li>
                                                <li><a href="<?php echo themeHelper::getCurrentProductUrl(); ?>/themes.html" target="_blank"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('get_themes', 'get themes')))); ?></a></li>
                                            </ul>
                                        </li>
                                        
                                        <li class="<?php echo (ADMIN_SELECTED_PAGE == 'api')?'active':''; ?>">
                                            <a><i class="fa fa-database"></i> <?php echo adminFunctions::makeSafe(strtoupper(adminFunctions::t('api', 'api'))); ?> <span class="fa fa-chevron-down"></span></a>
                                            <ul class="nav child_menu">
                                                <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/setting_manage.php?filterByGroup=API"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('api_settings', 'settings')))); ?></a></li>
                                                <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/api_documentation.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('api_documentation', 'documentation')))); ?></a></li>
                                                <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/api_test_framework.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('api_test_framework', 'testing tool')))); ?></a></li>
                                            </ul>
                                        </li>

                                        <li class="<?php echo (ADMIN_SELECTED_PAGE == 'configuration')?'active':''; ?>">
                                            <a><i class="fa fa-cog"></i> <?php echo adminFunctions::t("site_configuration", "Site Configuration"); ?> <span class="fa fa-chevron-down"></span></a>
                                            <ul class="nav child_menu">
                                                <li>
                                                    <a><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('site_settings', 'site settings')))); ?> <span class="fa fa-chevron-down"></span></a>
                                                    <ul class="nav child_menu">
                                                        <?php
                                                        foreach($groupDetails AS $groupDetail)
                                                        {
                                                            echo '<li><a href="'.ADMIN_WEB_ROOT.'/setting_manage.php?filterByGroup='.urlencode(adminFunctions::makeSafe($groupDetail['config_group'])).'">'.adminFunctions::makeSafe($groupDetail['config_group']).'</a></li>';
                                                        }
                                                        ?>
                                                    </ul>
                                                </li>
                                                <li>
                                                    <a><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('user_settings', 'user settings')))); ?> <span class="fa fa-chevron-down"></span></a>
                                                    <ul class="nav child_menu">
                                                        <li class="nav_manage_download_pages"><a href="<?php echo ADMIN_WEB_ROOT; ?>/download_page_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('download_pages', 'download pages')))); ?></a></li>
                                                        <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/account_package_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('account_packages', 'account packages')))); ?></a></li>
                                                        <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/banned_ip_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('banned_ips', 'banned ips')))); ?></a></li>
                                                    </ul>
                                                </li>

                                                <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/translation_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('translations', 'translations')))); ?></a></li>

                                                <li>
                                                    <a><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('system_tools', 'system tools')))); ?> <span class="fa fa-chevron-down"></span></a>
                                                    <ul class="nav child_menu">
                                                        <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/log_file_viewer.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('system_logs', 'system logs')))); ?></a></li>
                                                        <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/background_task_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('background_task_logs', 'background task logs')))); ?></a></li>
                                                        <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/database_browser.php?username=&db=<?php echo _CONFIG_DB_NAME; ?>"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('database_browser', 'database browser')))); ?></a></li>
                                                        <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/backup_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('backups', 'backups')))); ?></a></li>
                                                        <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/server_info.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('server_info', 'server info')))); ?></a></li>
                                                        <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/support_info.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('support_info', 'support info')))); ?></a></li>
                                                    </ul>
                                                </li>
                                            </ul>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                                    <?php
                                    // add additional menu items from the themes and plugins
                                    if($AuthUser->hasAccessLevel(20))
                                    {
                                        // add any theme navigation
                                        $themeNav = themeHelper::getThemeAdminNavV2();

                                        // add any plugin navigation
                                        $pluginNav = pluginHelper::getPluginAdminNavV2();

                                        if((strlen($themeNav) > 0) || (strlen($pluginNav) > 0))
                                        {
                                        ?>
                                            <div class="menu_section">
                                                <h3><?php echo adminFunctions::t('admin_plugin_pages', 'Plugin Pages'); ?></h3>
                                                <ul class="nav side-menu">
                                                    <?php
                                                    // add any theme navigation
                                                    echo themeHelper::getThemeAdminNavV2();

                                                    // add any plugin navigation
                                                    echo pluginHelper::getPluginAdminNavV2();
                                                    ?>
                                                </ul>
                                            </div>
                                        <?php
                                        }
                                    }
                                    ?>
                        </div>
                        <!-- /sidebar menu -->

                        <!-- /menu footer buttons -->
                        <div class="sidebar-footer hidden-small">
                            <a href="<?php echo ADMIN_WEB_ROOT; ?>/setting_manage.php" data-toggle="tooltip" data-placement="top" title="Site Settings">
                                <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
                            </a>
                            <a href="#" data-toggle="tooltip" data-placement="top" title="Toggle FullScreen" onClick="toggleFullScreen(); return false;">
                                <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
                            </a>
                            <a href="<?php echo ADMIN_WEB_ROOT; ?>/user_edit.php?id=<?php echo $AuthUser->id; ?>" data-toggle="tooltip" data-placement="top" title="Manage Your Account">
                                <span class="glyphicon glyphicon-lock" aria-hidden="true"></span>
                            </a>
                            <a href="<?php echo ADMIN_WEB_ROOT; ?>/logout.php" data-toggle="tooltip" data-placement="top" title="<?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('logout', 'logout')))); ?>">
                                <span class="glyphicon glyphicon-off" aria-hidden="true"></span>
                            </a>
                        </div>
                        <!-- /menu footer buttons -->
                    </div>
                </div>

                <!-- top navigation -->
                <div class="top_nav">
                    <div class="nav_menu">
                        <nav>
                            <div class="nav toggle">
                                <a id="menu_toggle"><i class="fa fa-bars"></i></a>
                            </div>

                            <ul class="nav navbar-nav navbar-right">
                                <li class="">
                                    <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                        <img src="<?php echo ADMIN_WEB_ROOT; ?>/ajax/account_view_avatar.ajax.php?userId=<?php echo (int)$AuthUser->id; ?>&width=44&height=44" alt=""><?php echo adminFunctions::makeSafe($AuthUser->getAccountScreenName()); ?>
                                        <span class=" fa fa-angle-down"></span>
                                    </a>
                                    <ul class="dropdown-menu dropdown-usermenu pull-right">
                                        <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/user_edit.php?id=<?php echo $AuthUser->id; ?>"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('your_account_settings', 'your account settings')))); ?></a></li>
                                        <li><a href="https://forum.mfscripts.com" target="_blank"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('support', 'support')))); ?></a></li>
                                        <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/logout.php"><i class="fa fa-sign-out pull-right"></i> <?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('logout', 'logout')))); ?></a></li>
                                    </ul>
                                </li>

                                <!--
                                <li role="presentation" class="dropdown">
                                    <a href="javascript:;" class="dropdown-toggle info-number" data-toggle="dropdown" aria-expanded="false">
                                        <i class="fa fa-envelope-o"></i>
                                        <span class="badge bg-green">2</span>
                                    </a>
                                    <ul id="menu1" class="dropdown-menu list-unstyled msg_list" role="menu">
                                        <li>
                                            <a>
                                                <span class="image"><img src="<?php echo ADMIN_WEB_ROOT; ?>/assets/images/img.jpg" alt="Profile Image" /></span>
                                                <span>
                                                    <span>John Smith</span>
                                                    <span class="time">3 mins ago</span>
                                                </span>
                                                <span class="message">
                                                    Film festivals used to be do-or-die moments for movie makers. They were where...
                                                </span>
                                            </a>
                                        </li>
                                        <li>
                                            <div class="text-center">
                                                <a>
                                                    <strong>See All Alerts</strong>
                                                    <i class="fa fa-angle-right"></i>
                                                </a>
                                            </div>
                                        </li>
                                    </ul>
                                </li>
                                -->

                            </ul>
                        </nav>
                    </div>
                </div>
                <!-- /top navigation -->


