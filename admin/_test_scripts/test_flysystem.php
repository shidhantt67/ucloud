<?php

// Determine our absolute document root
define('ADMIN_ROOT', realpath(dirname(dirname(__FILE__))));

// global includes
require_once(ADMIN_ROOT . '/../core/includes/master.inc.php');

// sample structure for the file_server_container.expected_config_json column
$arr = array(
    'username' => array('label'=>'Rackspace Username', 'type' => 'text', 'default'=>''),
    'apiKey' => array('label'=>'API Key', 'type' => 'text', 'default'=>''),
    'container' => array('label'=>'Cloud Files Container', 'type' => 'text', 'default'=>''),
    'region' => array('label'=>'Container Region', 'type' => 'select', 'default'=>'IAD', 'option_values' => array(
        'IAD' => 'Nothern Virginia (IAD)',
        'DFW' => 'Dallas (DFW)',
        'HKG' => 'Hong Kong (HKG)',
        'SYD' => 'Sydney (SYD)',
        'LON' => 'London (LON)',
    )),
);
        
echo json_encode($arr);
echo "<br/><br/>";
die('Done');