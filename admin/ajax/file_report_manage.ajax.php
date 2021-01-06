<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

$iDisplayLength       = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart        = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0           = (isset($_REQUEST['sSortDir_0']) && $_REQUEST['sSortDir_0'] === 'desc') ? 'desc' : 'asc';
$filterText           = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : "";
$filterByReportStatus = strlen($_REQUEST['filterByReportStatus']) ? $_REQUEST['filterByReportStatus'] : false;

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'report_date';
switch ($sortColumnName)
{
    case 'report_date':
        $sort = 'report_date';
        break;
    case 'reported_by_name':
        $sort = 'reported_by_name';
        break;
    case 'file_name':
        $sort = 'file.originalFilename';
        break;
    case 'reported_by_ip':
        $sort = 'reported_by_ip';
        break;
    case 'report_status':
        $sort = 'report_status';
        break;
}

$sqlClause = "WHERE 1=1 ";
if ($filterText)
{
    $filterText = $db->escape($filterText);
    $sqlClause .= "AND (file_report.reported_by_name LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "file_report.reported_by_email LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "file_report.reported_by_address LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "file_report.reported_by_telephone_number LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "file_report.digital_signature LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "file_report.reported_by_ip LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "file.originalFilename LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "file.id = '" . $filterText . "')";
}

if ($filterByReportStatus)
{
    $sqlClause .= " AND file_report.report_status = " . $db->quote($filterByReportStatus);
}

$totalRS   = $db->getValue("SELECT COUNT(file_report.id) AS total FROM file_report LEFT JOIN file ON file_report.file_id = file.id " . $sqlClause);
$limitedRS = $db->getRows("SELECT file_report.*, file.originalFilename, file.extension, file.id AS fileId, file.status FROM file_report LEFT JOIN file ON file_report.file_id = file.id " . $sqlClause . " ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $row)
    {
        $lRow = array();
        $icon = 'assets/images/icons/file_types/16px/' . $row['extension'] . '.png';
        if (!file_exists(ADMIN_ROOT . '/' . $icon))
        {
            $icon = 'assets/images/icons/file_types/16px/_page.png';
        }
        $lRow[] = '<img src="' . $icon . '" width="16" height="16" title="' . $row['extension'] . '" alt="' . $row['extension'] . '"/>';
        $lRow[] = coreFunctions::formatDate($row['report_date'], SITE_CONFIG_DATE_FORMAT);

        if ($row['status'] == 'active')
        {
            $lRow[] = '<a href="' . file::getFileUrl($row['fileId']) . '" target="_blank" title="' . file::getFileUrl($row['fileId']) . '">' . adminFunctions::makeSafe(adminFunctions::limitStringLength($row['originalFilename'], 35)) . '</a>';
        }
        else
        {
            $lRow[] = adminFunctions::makeSafe(adminFunctions::limitStringLength($row['originalFilename'], 35));
        }

        $lRow[]    = adminFunctions::makeSafe($row['reported_by_name']);
        $lRow[]    = adminFunctions::makeSafe($row['reported_by_ip']);
        $statusRow = '<span class="statusText' . str_replace(" ", "", adminFunctions::makeSafe(UCWords($row['report_status']))) . '"';
        $statusRow .= '>' . $row['report_status'] . '</span>';
        $lRow[]    = $statusRow;

        $links   = array();
        $links[] = '<a href="#" class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="view" onClick="viewReport(' . (int) $row['id'] . ', \'Removed after abuse report received on ' . coreFunctions::formatDate($row['report_date'], SITE_CONFIG_DATE_FORMAT) . '. Abuse report #' . $row['id'] . '.\', ' . (int) $row['fileId'] . ', \''.$row['report_status'].'\'); return false;"><span class="fa fa-info text-primary" aria-hidden="true"></span></a>';
        if ($row['status'] == 'active')
        {
            $links[] = '<a href="#" class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="remove file" onClick="confirmRemoveFile(' . (int) $row['id'] . ', \'Removed after abuse report received on ' . coreFunctions::formatDate($row['report_date'], SITE_CONFIG_DATE_FORMAT) . '. Abuse report #' . $row['id'] . '.\', ' . (int) $row['fileId'] . '); return false;"><span class="fa fa-check text-success" aria-hidden="true"></span></a>';
            $links[] = '<a href="#" class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="decline" onClick="declineReport(' . (int) $row['id'] . '); return false;"><span class="fa fa-close text-danger" aria-hidden="true"></span></a>';
        }
        else
        {
            if ($row['report_status'] == 'pending')
            {
                $links[] = '<a href="#" class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="approve" onClick="acceptReport(' . (int) $row['id'] . '); return false;"><span class="fa fa-check text-success" aria-hidden="true"></span></a>';
                $links[] = '<a href="#" class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="decline" onClick="declineReport(' . (int) $row['id'] . '); return false;"><span class="fa fa-close text-danger" aria-hidden="true"></span></a>';
            }
        }
        $lRow[] = '<div class="btn-group">'.implode(" ", $links).'</div>';

        $data[] = $lRow;
    }
}

$resultArr                         = array();
$resultArr["sEcho"]                = intval($_GET['sEcho']);
$resultArr["iTotalRecords"]        = (int) $totalRS;
$resultArr["iTotalDisplayRecords"] = $resultArr["iTotalRecords"];
$resultArr["aaData"]               = $data;

echo json_encode($resultArr);
