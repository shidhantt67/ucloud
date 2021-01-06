<?php

// includes and security
include_once('../_local_auth.inc.php');

$iDisplayLength = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0 = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "desc";
$filterText = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : null;

// get sorting columns
$iSortCol_0 = (int) $_REQUEST['iSortCol_0'];
$sColumns = trim($_REQUEST['sColumns']);
$arrCols = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort = 'payment_subscription.date_added';
switch($sortColumnName)
{
    case 'title':
        $sort = 'users.username';
        break;
    case 'date':
        $sort = 'payment_subscription.date_added';
        break;
    case 'payment_gateway':
        $sort = 'payment_subscription.payment_gateway';
        break;
    case 'status':
        $sort = 'payment_subscription.sub_status';
        break;
}

$sqlClause = "WHERE 1=1 ";
if($filterText)
{
    $filterText = strtolower($db->escape($filterText));
    $sqlClause .= "AND (LOWER(users.username) LIKE '" . $filterText . "%' OR ";
    $sqlClause .= "LOWER(payment_subscription.sub_status) = '" . $filterText . "%')";
}

$sQL = "SELECT payment_subscription.*, users.username FROM payment_subscription LEFT JOIN users ON payment_subscription.user_id = users.id ";
$sQL .= $sqlClause . " ";
$totalRS = $db->getRows($sQL);

$sQL .= "ORDER BY " . $sort . " " . $sSortDir_0 . " ";
$sQL .= "LIMIT " . $iDisplayStart . ", " . $iDisplayLength;
$limitedRS = $db->getRows($sQL);

$data = array();
if(COUNT($limitedRS) > 0)
{
    foreach($limitedRS AS $row)
    {
        $pricingData = $db->getRow('SELECT pricing_label, period, price FROM user_level_pricing WHERE id = ' . (int) $row['user_level_pricing_id'] . ' LIMIT 1');

        $lRow = array();

        $icon = 'local';
        $lRow[] = '<img src="assets/images/icons/16px_' . $row['sub_status'] . '.png" width="16" height="16" title="' . UCWords($row['sub_status']) . '" alt="' . UCWords($row['sub_status']) . '"/>';
        $lRow[] = adminFunctions::makeSafe(coreFunctions::formatDate($row['date_added'], SITE_CONFIG_DATE_TIME_FORMAT));
        $lRow[] = adminFunctions::makeSafe($row['username']);
        $lRow[] = adminFunctions::makeSafe($pricingData['period']);
        $lRow[] = adminFunctions::makeSafe($pricingData['price']) . ' ' . SITE_CONFIG_COST_CURRENCY_CODE;
        $lRow[] = adminFunctions::makeSafe($row['payment_gateway']);
        $lRow[] = '<span class="statusText' . str_replace(" ", "", UCWords($row['sub_status'])) . '">' . UCWords($row['sub_status']) . '</span>';

        $data[] = $lRow;
    }
}

$resultArr = array();
$resultArr["sEcho"] = intval($_GET['sEcho']);
$resultArr["iTotalRecords"] = (int) COUNT($totalRS);
$resultArr["iTotalDisplayRecords"] = $resultArr["iTotalRecords"];
$resultArr["aaData"] = $data;

echo json_encode($resultArr);
