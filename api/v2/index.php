<?php

// setup includes
require_once('../../core/includes/master.inc.php');

// requests from the same server don't have a HTTP_ORIGIN header
if(!array_key_exists('HTTP_ORIGIN', $_SERVER))
{
    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}

try
{
    $apiv2 = apiv2::init($_REQUEST['_page_url'], $_SERVER['HTTP_ORIGIN']);
    echo $apiv2->processAPI();
}
catch(Exception $e)
{
    // log
    $logParams = $apiv2->request;
    if(isset($logParams['password']))
    {
        unset($logParams['password']);
    }
    log::info('User Error: '.$e->getMessage().' - Params: '.json_encode($logParams, true));
    
    echo json_encode(array('status' => 'error', 'response' => $e->getMessage(), '_datetime' => coreFunctions::sqlDateTime()));
}