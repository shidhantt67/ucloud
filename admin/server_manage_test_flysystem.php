<?php
// includes and security
include_once('_local_auth.inc.php');

if (!isset($_REQUEST['serverId']))
{
    die('Could not find server id.');
}
else
{
    $serverId = (int) $_REQUEST['serverId'];
}
?>

<html lang="en-us">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=Edge;chrome=1" >
        <meta charset="utf-8" />
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/css/responsive.css" rel="stylesheet">
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/css/custom.css" rel="stylesheet">
    </head>
    <body style="background: #ffffff;">

<?php
/* load server details */
$row = file::getServerDetailsById($serverId);
if (!$row)
{
    echo t("could_not_load_server", "Could not load server details.");
}
else
{
    $serverConfigArr = '';
    if(strlen($row['serverConfig']))
    {
        $serverConfig = json_decode($row['serverConfig'], true);
        if(is_array($serverConfig))
        {
            $serverConfigArr = $serverConfig;
        }
    }
    
    $error = '';
    ?>
    <p><?php echo t("file_server_test_flysystem_intro", "Testing connection to file server... ([[[SERVER_LABEL]]])", array('SERVER_LABEL' => $row['serverLabel'])); ?></p>
    <?php

    // start output buffering
    ob_start();
    ob_end_flush();

    if (strlen($error) == 0)
    {
        echo '<p>- Setting up Flysystem adapter... ';
        try
        {
            $filesystem = fileServerContainer::init($row['id']);
            if(!$filesystem)
            {
                $error = 'Could not setup adapter.';
            }
        }
        catch (Exception $e)
        {
            $error = $e->getMessage();
        }
    }

    // output results
    ob_start();
    ob_end_flush();

    if (strlen($error) == 0)
    {
        echo '<font style="color: green;">Successfully setup adapter.</font></p>';
        echo '<p>- Attempting test upload... ';

        // create test file
        $testFilename = '_test_'.MD5(microtime()).'.txt';
        
        try
        {
            $rs = $filesystem->write($testFilename, 'Test - Feel free to remove this file.');
            if (!$rs)
            {
                $error = 'Could not upload test file! Please check the file storage settings and try again.';
            }
            else
            {
                // delete the file we just uploaded
                $filesystem->delete($testFilename);
            }
        }
        catch (Exception $e)
        {
            $error = $e->getMessage();
        }
    }

    // output results
    ob_start();
    ob_end_flush();

    if (strlen($error) == 0)
    {
        echo '<font style="color: green;">Successfully uploaded then removed test file.</font></p>';
    }

    // output results
    ob_start();
    ob_end_flush();

    if (strlen($error) > 0)
    {
        echo '<font style="color: red; font-weight:bold;">' . $error . '</font></p>';
    }
    else
    {
        echo '<p style="color: green; font-weight:bold;">- No errors found connecting to "' . $row['serverLabel'] . '".</p>';
    }
}
?>

    </body>
</html>