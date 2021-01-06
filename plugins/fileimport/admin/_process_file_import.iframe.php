<?php
// includes and security
include_once('../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// allow 12 hours and more memory
set_time_limit(60*12);
ini_set('memory_limit', '2048M');

// handle submissions
$startImport = false;
if(isset($_REQUEST['import_path']))
{
    $import_path = trim($_REQUEST['import_path']);
    $import_account = trim($_REQUEST['import_account']);
    $import_folder = (int)$_REQUEST['import_folder'];
    if(strlen($import_path) == 0)
    {
        adminFunctions::setError('Please set the import path.');
    }
    elseif($import_path == DIRECTORY_SEPARATOR)
    {
        adminFunctions::setError('The import path can not be root.');
    }
    elseif(!is_readable($import_path))
    {
        adminFunctions::setError('The import path is not readable, please move the files to a readable directory and try again.');
    }
    elseif(strlen($import_account) == 0)
    {
        adminFunctions::setError('Please set the account to import the files into.');
    }
    else
    {
        // lookup the account
        $user = UserPeer::loadUserByUsername($import_account);
        if(!$user)
        {
            adminFunctions::setError('User account not found, please check and try again');
        }
    }
}
?><html lang="en-us">

    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=Edge;chrome=1" >
        <meta charset="utf-8" />
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/css/responsive.css" rel="stylesheet">
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/css/custom.css" rel="stylesheet">
    </head>
    <body style="background: #ffffff;">
        <?php
        if(adminFunctions::isErrors())
        {
            echo implode('<br/>', adminFunctions::getErrors());
        }
        else
        {
            // setup access to the plugin functions
            $pluginObj = pluginHelper::getInstance('fileimport');

            // prepare folder id
            if ((int) $import_folder == 0)
            {
                $import_folder = null;
            }

            // prepare path
            if (substr($import_path, strlen($import_path) - 1, 1) != '/')
            {
                $import_path .= '/';
            }

            // scan for files
            $items = coreFunctions::getDirectoryListing($import_path);
            if (COUNT($items) == 0)
            {
                output('ERROR: No files or folders found in folder. Total: ' . COUNT($items), true);
            }
            
            // 1KB of initial data, required by Webkit browsers
            echo "<span style='display: none;'>" . str_repeat("0", 1000) . "</span>";

            // import files
            $pluginObj->importFiles($import_path, $user->id, $import_folder);

            // finish
            $pluginObj->output('<br/><span class="text-success"><strong>Import process completed.</strong></span>');
            $pluginObj->output('<br/>Note: The original files in your import folder have not been removed. You should manually remove these once you are happy the import has fully completed.');
        }
        ?>
    </body>
</html>