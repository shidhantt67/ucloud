<?php
error_reporting(E_ALL);
// initial constants
define('ADMIN_PAGE_TITLE', 'Themes');
define('ADMIN_SELECTED_PAGE', 'themes');
define('ADMIN_SELECTED_SUB_PAGE', 'theme_manage');

// includes and security
include_once('_local_auth.inc.php');

// import any new themes as uninstalled
themeHelper::clearCachedThemeSettings();
themeHelper::registerThemes();

// update theme config cache
themeHelper::loadThemeConfigurationFiles(true);

if(isset($_REQUEST['activate']))
{
    // validate submission
    if(_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }

    if(adminFunctions::isErrors() == false)
    {
        $folderName = trim($_REQUEST['activate']);

        // double check the folder exists
        $themeExists = (int) $db->getValue('SELECT COUNT(id) AS total FROM theme WHERE folder_name = ' . $db->quote($folderName));
        if($themeExists)
        {
            // activate theme
            $db->query('UPDATE theme SET is_installed = 0 WHERE is_installed = 1');
            $db->query('UPDATE theme SET is_installed = 1 WHERE folder_name = ' . $db->quote($folderName));
            $db->query('UPDATE site_config SET config_value = ' . $db->quote($folderName) . ' WHERE config_key = \'site_theme\' LIMIT 1');

            // success message, do on a redirect to refresh the admin area changes for the theme
            adminFunctions::redirect(ADMIN_WEB_ROOT . '/theme_manage.php?st=' . urlencode($folderName));
        }
        else
        {
            adminFunctions::setError('Can not find theme to set active.');
        }
    }
}

if(isset($_REQUEST['delete']))
{
    $delete = trim($_REQUEST['delete']);
    // validate submission
    if(_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    elseif(strlen($delete) == 0)
    {
        adminFunctions::setError('Can not find a theme to delete.');
    }

    if(adminFunctions::isErrors() == false)
    {
        $themeDetails = $db->getRow("SELECT * FROM theme WHERE folder_name = '" . $db->escape($delete) . "' AND is_installed = '0' LIMIT 1");
        if(!$themeDetails)
        {
            adminFunctions::setError('Could not get the theme details, please try again.');
        }
        else
        {
            $themePath = SITE_THEME_DIRECTORY_ROOT . $themeDetails['folder_name'];
            if(file_exists($themePath))
            {
                if(adminFunctions::recursiveThemeDelete($themePath) == false)
                {
                    adminFunctions::setError('Could not delete some files, please delete them manually.');
                }
            }
            if(file_exists($themePath))
            {
                if(!rmdir($themePath))
                {
                    adminFunctions::setError('Could not delete some files, please delete them manually.');
                }
            }
        }
        if(adminFunctions::isErrors() == false)
        {
            $db->query("DELETE FROM theme WHERE folder_name = '" . $themeDetails['folder_name'] . "'");
            adminFunctions::redirect(ADMIN_WEB_ROOT . '/theme_manage.php?de=1');
        }
    }
}

// error/success messages
if(isset($_REQUEST['sa']))
{
    adminFunctions::setSuccess('Theme successfully added. Activate it below.');
}
elseif(isset($_REQUEST['de']))
{
    adminFunctions::setSuccess('Theme successfully deleted.');
}
elseif(isset($_REQUEST['st']))
{
    adminFunctions::setSuccess('Theme successfully set to ' . adminFunctions::makeSafe($_REQUEST['st']));
}
elseif(isset($_REQUEST['error']))
{
    adminFunctions::setError(urldecode($_REQUEST['error']));
}

// load current theme from config, can not use the SITE_CONFIG_SITE_THEME constant encase it's been changed
$siteTheme = $db->getValue('SELECT config_value FROM site_config WHERE config_key = \'site_theme\' LIMIT 1');

// load all themes
$sQL = "SELECT * FROM theme ORDER BY theme_name";
$limitedRS = $db->getRows($sQL);

// page header
include_once('_header.inc.php');
?>
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
                        <h2>Manage Themes</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <div class="row">
                        <?php
                        $tracker = 1;
                        foreach($limitedRS AS $row)
                        {
                            // check for settings file
                            $settingsPath = '';
                            if(file_exists(SITE_THEME_DIRECTORY_ROOT . $row['folder_name'] . '/admin/settings.php'))
                            {
                                $settingsPath = SITE_THEME_WEB_ROOT . $row['folder_name'] . '/admin/settings.php';
                            }
                            ?>
                            <div class="col-md-6 col-sm-6 col-xs-12 profile_details">
                                <div class="well profile_view">
                                    <div class="col-sm-12">
                                        <h4 class="brief"></h4>
                                        <div class="left col-xs-7">
                                            <h2><?php echo adminFunctions::makeSafe($row['theme_name']); ?><?php echo $row['folder_name'] == $siteTheme ? ('&nbsp;&nbsp;<span style="color: green;">(active)</a>') : ''; ?></h2>
                                            <p><strong>Description: </strong><?php echo adminFunctions::makeSafe($row['theme_description']); ?></p>
                                            <ul class="list-unstyled">
                                                <li><i class="fa fa-user" title="author"></i> <?php echo adminFunctions::makeSafe($row['author_name']); ?><?php echo strlen($row['author_website']) ? (' (<a href="' . adminFunctions::makeSafe($row['author_website']) . '" target="_blank">' . adminFunctions::makeSafe($row['author_website']) . '</a>)') : ''; ?></li>
                                                <li><i class="fa fa-folder-o" title="folder"></i> /<?php echo adminFunctions::makeSafe($row['folder_name']); ?></li>
                                            </ul>
                                        </div>
                                        <div class="right col-xs-5 text-center">
                                            <img src="<?php echo SITE_THEME_WEB_ROOT; ?><?php echo adminFunctions::makeSafe($row['folder_name']); ?>/thumb_preview.png" class="img-square img-responsive pull-right" style="max-width: 261px;"/>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 bottom text-center">
                                        <div class="col-xs-12 col-sm-12 emphasis">
                                            <?php if($row['folder_name'] != $siteTheme): ?>
                                            <a type="button" class="btn btn-disabled" href="<?php echo ADMIN_WEB_ROOT; ?>/theme_manage.php?delete=<?php echo adminFunctions::makeSafe($row['folder_name']); ?>" onClick="return confirm('Are you sure that you want to completely delete the theme files from the server.');">
                                                <i class="fa fa-trash"> </i> Remove
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if(strlen($settingsPath)): ?>
                                            <a type="button" class="btn btn-primary" href="<?php echo $settingsPath; ?>">
                                                <i class="fa fa-cogs"> </i> Settings
                                            </a>
                                            <?php endif; ?>

                                            
                                            <?php if($row['folder_name'] != $siteTheme): ?>
                                                <a type="button" class="btn btn-default" onClick="return confirm('This will set your current logged in session to use this theme, switch back by logging out or by clicking the preview for the original on this page.');" href="<?php echo CORE_PAGE_WEB_ROOT; ?>/set_theme.php?theme=<?php echo adminFunctions::makeSafe($row['folder_name']); ?>" target="_blank">
                                                    <i class="fa fa-search-plus"> </i> Preview
                                                </a>
                                            
                                                <a type="button" class="btn btn-success" onClick="return confirm('Are you sure you want to enable this theme? The website will be immediately updated.');" href="theme_manage.php?activate=<?php echo adminFunctions::makeSafe($row['folder_name']); ?>">
                                                    <i class="fa fa-check"> </i> Activate
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                            $tracker++;
                        }
                        ?>
                        </div>
                    </div>
                </div>

                <div class="x_panel">
                    <a href="theme_manage_add.php" type="button" class="btn btn-primary">Add Theme</a>
                    <a href="<?php echo themeHelper::getCurrentProductUrl(); ?>/themes.html" target="_blank" type="button" class="btn btn-default">Get Themes</a>
                </div>

            </div>
        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>