<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

$iDisplayLength = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0 = (isset($_REQUEST['sSortDir_0']) && $_REQUEST['sSortDir_0'] === 'desc') ? 'desc' : 'asc';
$filterText = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : "";
$filterByUser = strlen($_REQUEST['filterByUser']) ? $_REQUEST['filterByUser'] : false;
$filterByServer = strlen($_REQUEST['filterByServer']) ? (int) $_REQUEST['filterByServer'] : false;
$filterByStatus = strlen($_REQUEST['filterByStatus']) ? $_REQUEST['filterByStatus'] : false;
$filterBySource = strlen($_REQUEST['filterBySource']) ? $_REQUEST['filterBySource'] : false;
$filterView = strlen($_REQUEST['filterView']) ? $_REQUEST['filterView'] : 'list';

// setup joins
$joins = array();

// get sorting columns
$iSortCol_0 = (int) $_REQUEST['iSortCol_0'];
$sColumns = trim($_REQUEST['sColumns']);
$arrCols = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort = 'originalFilename';
switch($sortColumnName)
{
    case 'filename':
        $sort = 'originalFilename';
        break;
    case 'filesize':
        $sort = 'fileSize';
        break;
    case 'date_uploaded':
        $sort = 'uploadedDate';
        break;
    case 'downloads':
        $sort = 'visits';
        break;
    case 'status':
        $sort = 'status';
        break;
    case 'owner':
        $sort = 'users.username';
        $joins['users'] = 'LEFT JOIN users ON file.userId = users.id';
        break;
}

$sqlClause = "WHERE 1=1 ";
if(strlen($filterText))
{
    $filterText = $db->escape($filterText);
    $sqlClause .= "AND (CONCAT('" . _CONFIG_SITE_FULL_URL . "/', file.shortUrl) LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "file.originalFilename LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "file.uploadedIP LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "file.id = '" . $filterText . "')";
}

if($filterByUser)
{
    $sqlClause .= " AND users.username = " . $db->quote($filterByUser);
    $joins['users'] = 'LEFT JOIN users ON file.userId = users.id';
}

if($filterByServer)
{
    $sqlClause .= " AND file.serverId = " . $filterByServer;
}

if($filterByStatus)
{
    $sqlClause .= " AND file.status = " . $db->quote($filterByStatus);
}

if($filterBySource)
{
    $sqlClause .= " AND file.uploadSource = " . $db->quote($filterBySource);
}
$totalRS = $db->getValue("SELECT COUNT(1) AS total FROM file " . implode(' ', $joins) . " " . $sqlClause);
$limitedRS = $db->getRows("SELECT file.*, file.status AS label, users.username, (SELECT file_action.id FROM file_action WHERE file_action.file_id = file.id AND (file_action.status = 'pending' OR file_action.status='processing') LIMIT 1) AS has_pending_action FROM file LEFT JOIN users ON file.userId = users.id " . $sqlClause . " ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength);
//var_dump($limitedRS);
$data = array();
if(COUNT($limitedRS) > 0)
{
    foreach($limitedRS AS $row)
    {
        $lRow = array();
        $icon = 'assets/images/icons/file_types/16px/' . $row['extension'] . '.png';
        if(!file_exists(ADMIN_ROOT . '/' . $icon))
        {
            $icon = 'assets/images/icons/file_types/16px/_page.png';
        }
        $typeIcon = '<span style="vertical-align: middle;"><img src="' . $icon . '" width="16" height="16" title="' . $row['extension'] . '" alt="' . $row['extension'] . '" style="margin-right: 5px;"/></span>';

        // checkbox
        $checkbox = '<input type="checkbox" id="cbElement' . $row['id'] . '" value="' . $row['id'] . '" name="table_records" class="checkbox flat"/>';
        if((int) $row['has_pending_action'] > 0)
        {
            $checkbox = '';
        }
        if($row['status'] != 'active')
        {
            $checkbox = '';
        }
        $lRow[] = $checkbox;

        if($row['status'] == 'active')
        {
            if($filterView == 'list')
            {
                // list item
                $colContent = '<span class="file-listing-view">' . $typeIcon . '<a href="' . file::getFileUrl($row['id']) . '~i" target="_blank" title="' . file::getFileUrl($row['id']) . '">' . adminFunctions::makeSafe(adminFunctions::limitStringLength($row['originalFilename'], 35)) . '</a></span>';
            }
            else
            {
                // file thumbnail
                $previewImageUrlLarge = file::getIconPreviewImageUrl($row, false, 160, false, 200, 200, 'cropped');
                $colContent = '<span class="file-thumbnail-view"><a href="' . file::getFileUrl($row['id']) . '~i" target="_blank" title="' . file::getFileUrl($row['id']) . '" style="display:block; text-align: center;"><img src="' . ((substr($previewImageUrlLarge, 0, 4) == 'http') ? $previewImageUrlLarge : (SITE_IMAGE_PATH . '/trans_1x1.gif')) . '" alt="" class="' . ((substr($previewImageUrlLarge, 0, 4) != 'http') ? $previewImageUrlLarge : '#') . '" style="border: 1px solid #ffffff; margin: 2px;"><br/>' . adminFunctions::makeSafe(adminFunctions::limitStringLength($row['originalFilename'], 35)) . '</a></span>';
            }

            $lRow[] .= $colContent;
        }
        else
        {
            $lRow[] = $typeIcon . adminFunctions::makeSafe(adminFunctions::limitStringLength($row['originalFilename'], 35));
        }
        $lRow[] = coreFunctions::formatDate($row['uploadedDate'], SITE_CONFIG_DATE_FORMAT);
        $lRow[] = (int) $row['fileSize'] > 0 ? adminFunctions::formatSize($row['fileSize']) : 0;
        $lRow[] = (int) $row['visits'] > 0 ? ((int) $row['visits'] . ' <a href="download_previous.php?fileId=' . $row['id'] . '"> <span class="fa fa-search" aria-hidden="true"></span></a>') : 0;
        $lRow[] = strlen($row['username']) ? ('<a title="IP: ' . adminFunctions::makeSafe($row['uploadedIP']) . '" href="' . ADMIN_WEB_ROOT . '/file_manage.php?filterByUser=' . adminFunctions::makeSafe($row['userId']) . '">' . adminFunctions::makeSafe($row['username']) . ' <span class="fa fa-search" aria-hidden="true"></span></a>') : '<span style="color: #aaa;" title="[no login]"><a href="' . ADMIN_WEB_ROOT . '/file_manage.php?filterText=' . adminFunctions::makeSafe($row['uploadedIP']) . '">' . adminFunctions::makeSafe($row['uploadedIP']) . ' <span class="fa fa-search" aria-hidden="true"></span></a></span>';
        $statusRow = '<span class="statusText' . str_replace(" ", "", adminFunctions::makeSafe(UCWords($row['label']))) . '"';
        $statusRow .= '>' . $row['label'] . '</span>';
        $lRow[] = $statusRow;

        $linkStr = '';
        $links = array();
        if($row['status'] == 'active')
        {
            $links[] = '<a href="#" class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="edit" onClick="editFile(' . (int) $row['id'] . '); return false;"><span class="fa fa-pencil" aria-hidden="true"></span></a>';
        }
        $links[] = '<a class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="stats" href="' . file::getFileStatisticsUrl($row['id']) . '" target="_blank"><span class="fa fa-pie-chart text-default" aria-hidden="true"></span></a>';
        if($row['status'] == 'active')
        {
            $links[] = '<a class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="remove" href="#" onClick="confirmRemoveFile(' . (int) $row['id'] . '); return false;"><span class="fa fa-trash text-danger" aria-hidden="true"></span></a>';
        }
        if($row['status'] == 'active')
        {
            $links[] = '<a class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="download" href="' . file::getFileUrl($row['id']) . '" target="_blank"><span class="fa fa-download" aria-hidden="true"></span></a>';
        }
        if(strlen($row['adminNotes']))
        {
            $links[] = '<a class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="notes" href="#" onClick="showNotes(\'' . str_replace(array("\n", "\r"), "<br/>", adminFunctions::makeSafe(str_replace("'", "\"", $row['adminNotes']))) . '\'); return false;"><span class="fa fa-file-text-o" aria-hidden="true"></span></a>';
        }
        
        $linkStr = '<div class="btn-group">'.implode(" ", $links).'</div>';
        $lRow[] = $linkStr;

        $data[] = $lRow;
    }
}

$resultArr = array();
$resultArr["sEcho"] = intval($_GET['sEcho']);
$resultArr["iTotalRecords"] = (int) $totalRS;
$resultArr["iTotalDisplayRecords"] = $resultArr["iTotalRecords"];
$resultArr["aaData"] = $data;

echo json_encode($resultArr);
