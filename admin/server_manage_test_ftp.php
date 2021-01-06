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

<p><?php echo t("file_server_test_ftp_intro", "Testing connection to file server... (via ftp)"); ?></p>
<?php
/* load server details */
$sQL = "SELECT file_server.* ";
$sQL .= "FROM file_server ";
$sQL .= "WHERE file_server.serverType = 'ftp' AND id=" . (int) $serverId;
$row = $db->getRow($sQL);
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

    // start output buffering
    ob_start();
    ob_end_flush();

    echo '<p>- Making sure ftp functions are available in PHP... ';

    // make sure ftp functions exists
    if (!function_exists('ftp_connect'))
    {
        $error = 'Could not find PHP ftp functions! Please contact your host to request they\'re enabled.';
    }

    // output results
    ob_start();
    ob_end_flush();

    if (strlen($error) == 0)
    {
        echo '<font style="color: green;">FTP functions found.</font></p>';
        echo '<p>- Finding file server ' . $row['serverLabel'] . ' on ip ' . $row['ipAddress'] . ' (port: ' . $row['ftpPort'] . ')... ';

        // connect via ftp
        $conn_id = ftp_connect($row['ipAddress'], $row['ftpPort'], 30);
        if ($conn_id === false)
        {
            $error = 'Could not connect!';
        }
    }

    // output results
    ob_start();
    ob_end_flush();

    if (strlen($error) == 0)
    {
        echo '<font style="color: green;">Successfully found.</font></p>';
        echo '<p>- Authenticating with stored user \'' . $row['ftpUsername'] . '\' and password [HIDDEN]... ';

        // authenticate
        $login_result = ftp_login($conn_id, $row['ftpUsername'], $row['ftpPassword']);
        if ($login_result === false)
        {
            $error = 'Could not authenticate!';
            // close ftp
            ftp_close($conn_id);
        }
    }

    // output results
    ob_start();
    ob_end_flush();

    if (strlen($error) == 0)
    {
        if((isset($serverConfigArr['ftp_passive_mode'])) && ($serverConfigArr['ftp_passive_mode'] == 'yes'))
        {
            // enable passive mode
            ftp_pasv($conn_id, true);
        }
        
        echo '<font style="color: green;">Successfully authenticated.</font></p>';
        echo '<p>- Changing to storage directory: ' . $row['storagePath'] . '... ';

        // change directory
        if (ftp_chdir($conn_id, $row['storagePath']) === false)
        {
            $error = 'Could not find storage directory!';
            // close ftp
            ftp_close($conn_id);
        }
    }
    
    // output results
    ob_start();
    ob_end_flush();

    if (strlen($error) == 0)
    {
        echo '<font style="color: green;">Successfully changed directory.</font></p>';
        echo '<p>- Attempting test upload to: ' . $row['storagePath'] . '... ';

        $testFile = tmpfile();
        if(!$testFile)
        {
            $error = 'Could not create tmp file for testing upload!';
        }
        else
        {
            // upload test file
            $testFilename = "_yetishare_test_".time().".txt";
            fwrite($testFile, 'YetiShare text file.');
            fseek($testFile, 0);
            if (!ftp_fput($conn_id, $testFilename, $testFile, FTP_BINARY))
            {
                $error = 'Could not upload a file to '.$row['storagePath'].'!';
            }
            else
            {
                // remove test file
                ftp_delete($conn_id, $testFilename);
            }
            fclose($fSetup);
        }
    }

    // output results
    ob_start();
    ob_end_flush();

    if (strlen($error) == 0)
    {
        echo '<font style="color: green;">Successfully uploaded and removed test file.</font></p>';
        // close ftp
        ftp_close($conn_id);
        echo '<p>- Disconnected from ftp.</p>';
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
        echo '<p style="color: green; font-weight:bold;">- No errors found connecting to ' . $row['serverLabel'] . '.</p>';
    }
}
?>

    </body>
</html>