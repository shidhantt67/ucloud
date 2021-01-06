<?php

// includes and security
include_once('../_local_auth.inc.php');

$iDisplayLength = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart  = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0     = (isset($_REQUEST['sSortDir_0']) && $_REQUEST['sSortDir_0'] === 'desc') ? 'desc' : 'asc';
$filterText     = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : null;

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'user_level.level_id';

$sqlClause = "WHERE 1=1 ";
if ($filterText)
{
    $filterText = $db->escape($filterText);
    $sqlClause .= "AND (user_level.label LIKE '%" . $filterText . "%')";
}

$sQL     = "SELECT user_level.*, (SELECT COUNT(users.id) FROM users WHERE users.level_id=user_level.id) AS totalUsers, user_level.id AS package_id FROM user_level ";
$sQL .= $sqlClause . " ";
$totalRS = $db->getRows($sQL);

$sQL .= "ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength;
$limitedRS = $db->getRows($sQL);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $row)
    {
        $lRow = array();

        $lRow[] = '<img src="assets/images/icons/system/16x16/tag_blue.png" width="16" height="16" title="User Package" alt="user group"/>';
        $lRow[] = adminFunctions::makeSafe(UCWords($row['label']));
		$lRow[] = '<a href="'.ADMIN_WEB_ROOT.'/user_manage.php?filterByAccountType=' . (int) $row['package_id'] . '">'.adminFunctions::makeSafe($row['totalUsers']).'</a>';
        $lRow[] = adminFunctions::makeSafe($row['can_upload']==1?'Yes':'No');
        $lRow[] = adminFunctions::makeSafe($row['max_upload_size'] == 0 ? 'Unlimited' : adminFunctions::formatSize($row['max_upload_size'], 2));
        $lRow[] = adminFunctions::makeSafe($row['max_storage_bytes'] == 0 ? 'Unlimited' : adminFunctions::formatSize($row['max_storage_bytes'], 2));
        if(themeHelper::getCurrentProductType() == 'cloudable') {
            $lRow[] = adminFunctions::makeSafe($row['days_to_keep_inactive_files']==0?'Unlimited':$row['days_to_keep_inactive_files']);
        }
        else {
            $lRow[] = adminFunctions::makeSafe($row['on_upgrade_page']==1?'Yes':'-');
        }

        $links   = array();
        $links[] = '<a class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="settings" href="#" onClick="editPackageForm(' . (int) $row['id'] . '); return false;"><span class="fa fa-pencil" aria-hidden="true"></span></a>';
        if($row['level_type'] == 'paid')
        {
            $links[] = '<a class="btn btn-default btn-sm nav_account_packages" data-toggle="tooltip" data-placement="top" data-original-title="pricing options" href="account_package_pricing_manage.php?level_id=' . (int) $row['level_id'] . '"><span class="fa fa-money" aria-hidden="true"></span></a>';
        }
	$linkStr = '<div class="btn-group">'.implode(" ", $links).'</div>';
        $lRow[] = $linkStr;

        $data[] = $lRow;
    }
}

$resultArr                         = array();
$resultArr["sEcho"]                = intval($_GET['sEcho']);
$resultArr["iTotalRecords"]        = (int) COUNT($totalRS);
$resultArr["iTotalDisplayRecords"] = $resultArr["iTotalRecords"];
$resultArr["aaData"]               = $data;

echo json_encode($resultArr);