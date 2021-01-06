<?php

// Determine our absolute document root
define('ADMIN_ROOT', realpath(dirname(dirname(__FILE__))));

// global includes
require_once(ADMIN_ROOT . '/../core/includes/master.inc.php');

use Omnipay\Omnipay;

$gateway = Omnipay::create('Coinbase');
print_r($gateway->getParameters());die();
$gateway->setApiKey('abc123');
$formData = array('number' => '4242424242424242', 'expiryMonth' => '6', 'expiryYear' => '2030', 'cvv' => '123');
$response = $gateway->purchase(array('amount' => '10.00', 'currency' => 'USD', 'card' => $formData))->send();

if ($response->isRedirect()) {
    // redirect to offsite payment gateway
    $response->redirect();
} elseif ($response->isSuccessful()) {
    // payment was successful: update database
    print_r($response);
} else {
    // payment failed: display message to customer
    echo $response->getMessage();
}