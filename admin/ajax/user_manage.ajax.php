<?php

// includes and security
include_once('../_local_auth.inc.php');

$iDisplayLength = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0 = (isset($_REQUEST['sSortDir_0']) && $_REQUEST['sSortDir_0'] === 'desc') ? 'desc' : 'asc';
$filterText = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : "";
$filterByAccountType = strlen($_REQUEST['filterByAccountType']) ? $_REQUEST['filterByAccountType'] : false;
$filterByAccountStatus = strlen($_REQUEST['filterByAccountStatus']) ? $_REQUEST['filterByAccountStatus'] : false;
$filterByAccountId = (int) $_REQUEST['filterByAccountId'] ? (int) $_REQUEST['filterByAccountId'] : false;

// account types
$accountTypeDetailsLookup = array();
$accountTypeDetails = $db->getRows('SELECT id, label, level_type FROM user_level ORDER BY id ASC');
foreach($accountTypeDetails AS $accountTypeDetail)
{
    $accountTypeDetailsLookup[$accountTypeDetail{'id'}] = array('label' => $accountTypeDetail['label'], 'level_type' => $accountTypeDetail['level_type']);
}

// get sorting columns
$iSortCol_0 = (int) $_REQUEST['iSortCol_0'];
$sColumns = trim($_REQUEST['sColumns']);
$arrCols = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort = 'username';
switch($sortColumnName)
{
    case 'username':
        $sort = 'username';
        break;
    case 'email_address':
        $sort = 'email';
        break;
    case 'account_type':
        $sort = 'level_id';
        break;
    case 'last_login':
        $sort = 'lastlogindate';
        break;
    case 'status':
        $sort = 'status';
        break;
    case 'space_used':
        $sort = '(SELECT SUM(fileSize) FROM file WHERE file.userId=users.id AND file.status="active")';
        break;
    case 'total_files':
        $sort = '(SELECT COUNT(id) FROM file WHERE file.userId=users.id AND file.status="active")';
        break;
}

$sqlClause = "WHERE 1=1 ";
if($filterText)
{
    $filterText = $db->escape($filterText);
    $sqlClause .= "AND (users.status = '" . $filterText . "' OR ";
    $sqlClause .= "users.username LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "users.email LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "users.firstname LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "users.lastname LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "users.id = '" . $filterText . "')";
}

if($filterByAccountType)
{
    $sqlClause .= " AND users.level_id = '" . $db->escape($filterByAccountType) . "'";
}

if($filterByAccountStatus)
{
    $sqlClause .= " AND users.status = '" . $db->escape($filterByAccountStatus) . "'";
}

if($filterByAccountId)
{
    $sqlClause .= " AND users.id = " . (int) $filterByAccountId;
}

$totalRS = $db->getValue("SELECT COUNT(users.id) AS total FROM users " . $sqlClause);
$limitedRS = $db->getRows("SELECT users.*, (SELECT SUM(fileSize) FROM file WHERE file.userId=users.id AND file.status='active') AS totalFileSize, (SELECT COUNT(id) FROM file WHERE file.userId=users.id AND file.status='active') AS totalFiles FROM users " . $sqlClause . " ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength);

$data = array();
if(COUNT($limitedRS) > 0)
{
    foreach($limitedRS AS $row)
    {
        // calculate css class to use for account type badge
        $cssClass = 'default';
        switch($accountTypeDetailsLookup[$row{'level_id'}]['level_type'])
        {
            case 'admin':
                $cssClass = 'danger';
                break;
            case 'moderator':
                $cssClass = 'warning';
                break;
            case 'paid':
                $cssClass = 'primary';
                break;
            case 'free':
                $cssClass = 'info';
                break;
        }
        $accountLevelLabel = UCWords(adminFunctions::makeSafe($accountTypeDetailsLookup[$row{'level_id'}]['label']));
        $accountLevelHtml = '<span class="label label-' . $cssClass . '">' . $accountLevelLabel . '</span>';

        $lRow = array();

        // load avatar
        $avatarCachePath = 'user/' . (int) $row['id'] . '/profile';
        $avatarWidth = 44;
        $avatarHeight = 44;
        $avatarCacheFilename = MD5((int) $row['id'] . $avatarWidth . $avatarHeight . 'square') . '.jpg';
        $icon = CACHE_WEB_ROOT . '/' . $avatarCachePath . '/' . $avatarCacheFilename;
        if(!cache::checkCacheFileExists($avatarCachePath . '/' . $avatarCacheFilename))
        {
            // if one hasn't been uploaded
            if(!cache::getCacheFromFile($avatarCachePath . '/avatar_original.jpg'))
            {
                $icon = ADMIN_WEB_ROOT . '/assets/images/avatar_default.jpg';
            }
            // if the user has uploaded one but the cache file just doesn't exist
            else
            {
                $icon = ADMIN_WEB_ROOT.'/ajax/account_view_avatar.ajax.php?userId='.(int) $row['id'].'&width='.$avatarWidth.'&height='.$avatarHeight;
            }
        }

        $lRow[] = '<img src="' . $icon . '" width="16" height="16" class="avatar" title="User" alt="User"/>';
        $lRow[] = '<a href="user_edit.php?id=' . $row['id'] . '">'.adminFunctions::makeSafe($row['username']).'</a>';
        $lRow[] = adminFunctions::makeSafe($row['email']);
        $lRow[] = $accountLevelHtml;
        $lRow[] = coreFunctions::formatDate($row['lastlogindate'], SITE_CONFIG_DATE_TIME_FORMAT);
        $lRow[] = (int) $row['totalFileSize'] > 0 ? adminFunctions::formatSize($row['totalFileSize']) : 0;
        $lRow[] = (int) $row['totalFiles'] > 0 ? ((int) $row['totalFiles'] . ' <a href="file_manage.php?fileByUser=' . $row['id'] . '"> <span class="fa fa-search" aria-hidden="true"></span></a>') : 0;
        $lRow[] = '<span class="statusText' . str_replace(" ", "", UCWords($row['status'])) . '">' . $row['status'] . '</span>';

        $links = array();
        $links[] = '<a class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="edit" href="user_edit.php?id=' . $row['id'] . '"><span class="fa fa-pencil" aria-hidden="true"></span></a>';
        $links[] = '<a class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="files" href="file_manage.php?filterByUser=' . $row['id'] . '"><span class="fa fa-upload" aria-hidden="true"></span></a>';
        if(coreFunctions::formatDate($row['lastlogindate'], SITE_CONFIG_DATE_TIME_FORMAT) !== null)
        {
            $links[] = '<a class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="login history" href="user_login_history.php?id=' . $row['id'] . '"><span class="fa fa-file-text-o" aria-hidden="true"></span></a>';
        }
        if($Auth->id != $row['id'])
        {
            $links[] = '<a class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="delete user" href="#" onClick="confirmRemoveUser(' . $row['id'] . '); return false;"><span class="fa fa-trash text-danger" aria-hidden="true"></span></a>';
            $links[] = '<a class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="impersonate user" href="#" onClick="confirmImpersonateUser(' . $row['id'] . '); return false;"><span class="fa fa-sign-in" aria-hidden="true"></span></a>';
        }
        $lRow[] = '<div class="btn-group">' . implode(" ", $links) . '</div>';

        $data[] = $lRow;
    }
}

$resultArr = array();
$resultArr["sEcho"] = intval($_GET['sEcho']);
$resultArr["iTotalRecords"] = (int) $totalRS;
$resultArr["iTotalDisplayRecords"] = $resultArr["iTotalRecords"];
$resultArr["aaData"] = $data;

echo json_encode($resultArr);
