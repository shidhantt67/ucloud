<?php

// includes and security
include_once('../_local_auth.inc.php');

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

if (!isset($_REQUEST['paymentId']))
{
    $result['error'] = true;
    $result['msg']   = 'Failed finding payment information.';
}
else
{
    $paymentId = (int) $_REQUEST['paymentId'];

    // load all server statuses
    $sQL           = "SELECT payment_log.id, payment_log.date_created, payment_log.description, payment_log.amount, payment_log.currency_code, payment_log.request_log, payment_log.payment_method, users.username, users.id AS user_id FROM payment_log LEFT JOIN users ON payment_log.user_id = users.id WHERE payment_log.id=" . $paymentId . " LIMIT 1";
    $paymentDetail = $db->getRow($sQL);
    if (!$paymentDetail)
    {
        $result['error'] = true;
        $result['msg']   = 'Failed finding payment information.';
    }
    else
    {
        $result['html'] .= '<div class="x_panel">';
        $result['html'] .= '    <div class="x_title">';
        $result['html'] .= '        <h2>View Payment</h2>';
        $result['html'] .= '        <div class="clearfix"></div>';
        $result['html'] .= '    </div>';
        $result['html'] .= '    <div class="x_content">';
        $result['html'] .= '    <p>Full details of the payment are below:</p>';

        $result['html'] .= '<table class="table table-data-list">';
        
        $result['html'] .= '<tr>';
        $result['html'] .= '<td style="width: 110px;">User:</td>';
        $result['html'] .= '<td><a href="user_manage.php?filterByAccountId='.urlencode($paymentDetail['user_id']).'">'.adminFunctions::makeSafe($paymentDetail['username']).'</a></td>';
        $result['html'] .= '</tr>';
        
        $result['html'] .= '<tr>';
        $result['html'] .= '<td>Amount:</td>';
        $result['html'] .= '<td>'.adminFunctions::makeSafe($paymentDetail['amount']).' '.adminFunctions::makeSafe($paymentDetail['currency_code']).' '.(strlen($paymentDetail['payment_method'])?('&nbsp;('.$paymentDetail['payment_method'].')'):'').'</td>';
        $result['html'] .= '</tr>';

        
        $result['html'] .= '<tr>';
        $result['html'] .= '<td>Payment Date:</td>';
        $result['html'] .= '<td>'.adminFunctions::makeSafe(coreFunctions::formatDate($paymentDetail['date_created'], SITE_CONFIG_DATE_TIME_FORMAT)).'</td>';
        $result['html'] .= '</tr>';

        $result['html'] .= '<tr>';
        $result['html'] .= '<td>Description:</td>';
        $result['html'] .= '<td>'.adminFunctions::makeSafe($paymentDetail['description']).'</td>';
        $result['html'] .= '</tr>';
        
        $result['html'] .= '<tr>';
        $result['html'] .= '<td>Payment Notes:</td>';
        $result['html'] .= '<td>'.nl2br(adminFunctions::makeSafe(strlen($paymentDetail['request_log'])?$paymentDetail['request_log']:'-')).'</td>';
        $result['html'] .= '</tr>';
        
        $result['html'] .= '</table>';
        
        $result['html'] .= '    </div>';
        $result['html'] .= '</div>';
    }
}

echo json_encode($result);
exit;
