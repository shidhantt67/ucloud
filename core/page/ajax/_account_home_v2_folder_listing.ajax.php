<?php

/* setup includes */
require_once('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// some initial headers
header("HTTP/1.0 200 OK");
header('Content-type: application/json; charset=utf-8');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Pragma: no-cache");

// prepare clause for user owned folders
$clause = '(userId = ' . (int) $Auth->id . ' AND file_folder.status = "active" AND ';
$clause2 = '(file_folder.id IN (SELECT folder_id FROM file_folder_share WHERE shared_with_user_id = ' . (int) $Auth->id . ') AND ';
if ((isset($_REQUEST['folder'])) && ($_REQUEST['folder'] != -1)) {
    $folder = $_REQUEST['folder'];
    $clause .= 'parentId = ' . (int) $folder;
    $clause2 .= 'parentId = ' . (int) $folder;
}
else {
    $clause .= 'parentId IS NULL';
    $clause2 .= 'parentId IS NULL';
}
$clause .= ')';
$clause2 .= ')';

$rs = array();

// load folder data for user
$rows = $db->getRows('SELECT file_folder.id, folderName, totalSize, users.username, '
        . '(SELECT COUNT(ffchild.id) AS total FROM file_folder ffchild WHERE ffchild.parentId = file_folder.id) AS childrenCount, '
        . 'accessPassword, (SELECT COUNT(file.id) AS total FROM file WHERE folderId = file_folder.id AND '
        . 'file.status = "active") AS fileCount '
        . 'FROM file_folder '
        . 'LEFT JOIN users ON file_folder.userId = users.id '
        . 'WHERE ' . $clause . ' '
        . 'ORDER BY folderName '
        . 'LIMIT 150');
if ($rows) {
    foreach ($rows AS $row) {
        $folderType = 'folder';
        if (((int) $row['fileCount'] > 0) || ((int) $row['childrenCount'] > 0)) {
            $folderType = 'folderfull';
        }

        if (strlen($row['accessPassword'])) {
            $folderType = 'folderpassword';
        }

        if ($row['shared_with_user_id'] == $Auth->id) {
            $folderType = 'foldershared';
        }

        $permission = 'all';
        $totalSize = $row['totalSize'];
        if ($totalSize === NULL) {
            $totalSize = fileFolder::updateFolderFilesize($row['id']);
        }

        if ((int) $row['childrenCount'] > 0) {
            $rs[$row{'folderName'}] = array(
                'data' => $row['folderName'] . (((int) $row['fileCount'] > 0) ? (' (' . number_format($row['fileCount']) . ')') : '') . ' ',
                'attr' => array(
                    'id' => $row['id'],
                    'owner' => $row['username'],
                    'permission' => $permission,
                    'total_size' => coreFunctions::formatSize($totalSize),
                    'title' => t('account_home_folder_treeview_double_click', 'Double click to view/hide subfolders'),
                    'rel' => $folderType
                ),
                'children' => array(
                    'state' => 'closed'
                ),
                'state' => 'closed'
            );
        }
        else {
            $rs[$row{'folderName'}] = array(
                'data' => $row['folderName'] . (((int) $row['fileCount'] > 0) ? (' (' . number_format($row['fileCount']) . ')') : ''),
                'attr' => array(
                    'id' => $row['id'],
                    'owner' => $row['username'],
                    'permission' => $permission,
                    'total_size' => coreFunctions::formatSize($totalSize),
                    'title' => '',
                    'rel' => $folderType
                )
            );
        }
    }
}

// add on any shared folders
$rows = $db->getRows('SELECT file_folder.id, folderName, totalSize, users.username, '
        . 'file_folder_share.share_permission_level, '
        . '(SELECT COUNT(ffchild.id) AS total FROM file_folder ffchild WHERE ffchild.parentId = file_folder.id) AS childrenCount, '
        . 'accessPassword, '
        . '(SELECT COUNT(file.id) AS total FROM file WHERE folderId = file_folder.id AND file.status = "active") AS fileCount '
        . 'FROM file_folder '
        . 'LEFT JOIN users ON file_folder.userId = users.id '
        . 'LEFT JOIN file_folder_share ON file_folder.id = file_folder_share.folder_id '
        . 'WHERE ' . $clause2 . ' ORDER BY folderName LIMIT 100');
if ($rows) {
    foreach ($rows AS $row) {
        $folderType = 'sharedicon';
        $permission = $row['share_permission_level'];
        $totalSize = $row['totalSize'];
        if ($totalSize === NULL) {
            $totalSize = fileFolder::updateFolderFilesize($row['id']);
        }

        if ((int) $row['childrenCount'] > 0) {
            $rs[$row{'folderName'}] = array(
                'data' => $row['folderName'] . (((int) $row['fileCount'] > 0) ? (' (' . number_format($row['fileCount']) . ')') : '') . ' ',
                'attr' => array(
                    'id' => $row['id'],
                    'owner' => $row['username'],
                    'permission' => $permission,
                    'total_size' => coreFunctions::formatSize($totalSize),
                    'title' => t('account_home_folder_treeview_double_click', 'Double click to view/hide subfolders'),
                    'rel' => $folderType
                ),
                'children' => array(
                    'state' => 'closed'
                ),
                'state' => 'closed');
        }
        else {
            $rs[$row{'folderName'}] = array(
                'data' => $row['folderName'] . (((int) $row['fileCount'] > 0) ? (' (' . number_format($row['fileCount']) . ')') : ''),
                'attr' => array(
                    'id' => $row['id'],
                    'owner' => $row['username'],
                    'permission' => $permission,
                    'total_size' => coreFunctions::formatSize($totalSize),
                    'title' => '',
                    'rel' => $folderType
                )
            );
        }
    }
}

// sort by keys to order folder listing
ksort($rs);

// remove keys as they cause issues with the treeview
$rs = array_values($rs);

echo json_encode($rs);
