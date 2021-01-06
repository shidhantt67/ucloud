<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

// add the core script for checking
$items = array();
$items[] = array('t'=>'core', 'st'=>themeHelper::getCurrentProductType(), 'uid'=>WEB_ROOT, 'v'=>_CONFIG_SCRIPT_VERSION);

// load list of plugins and their current version numbers for checking
$plugins = $db->getRows('SELECT plugin_name, folder_name FROM plugin ORDER BY plugin_name');
if($plugins)
{
    foreach($plugins AS $plugin)
    {
        // load version number
        $pluginVersion = null;
        $configPath = PLUGIN_DIRECTORY_ROOT . $plugin['folder_name'] . '/_plugin_config.inc.php';
        if(file_exists($configPath))
        {
            include($configPath);
            $pluginVersion = $pluginConfig['plugin_version'];
        }

        if($pluginVersion != null)
        {
            $items[] = array('t'=>'plugin', 'uid'=>$plugin['folder_name'], 'v'=>$pluginVersion);
        }
    }
}

// prep url
$url = 'yetishare.php';
if(themeHelper::getCurrentProductType() == 'image_hosting')
{
    $url = 'reservo.php';
}
elseif(themeHelper::getCurrentProductType() == 'cloudable')
{
    $url = 'cloudable.php';
}
$url = 'https://mfscripts.com/_script_internal/v2/'.$url;

// check we have curl
if(!function_exists('curl_init'))
{
    // send via normal get
    $responseStr = coreFunctions::getRemoteUrlContent($url.'?req='.urlencode(json_encode($items)));
}
else
{
    // send the data via curl
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('req' => json_encode($items))));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $responseStr = curl_exec($ch);
    curl_close($ch);
}

print_r($responseStr);
die();










// check for script upgrade
$fileContents = coreFunctions::getRemoteUrlContent($url);
if(($fileContents) && (strlen($fileContents)))
{
    $lines = explode("\n", $fileContents);
    $newVersion = (float) $lines[0];
    $upgradeMessage = trim($lines[1]);

    // check against current version
    if(version_compare($newVersion, _CONFIG_SCRIPT_VERSION) > 0)
    {
        echo $upgradeMessage;
    }
}
