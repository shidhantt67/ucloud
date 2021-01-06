<?php

/* setup includes */
require_once('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// prepare result
$result = array();

// get total files in root folder
$result['totalRootFiles'] = (int) $db->getValue('SELECT COUNT(id) FROM file WHERE (userId = ' . (int) $Auth->id . ' OR file.uploadedUserId = ' . (int) $Auth->id . ') AND folderId IS NULL AND status = "active"');

// get total files in trash
$result['totalTrashFiles'] = (int) $db->getValue('SELECT COUNT(id) FROM file WHERE (userId = ' . (int) $Auth->id . ' OR file.uploadedUserId = ' . (int) $Auth->id . ') AND status = "trash"');

// get total active files
$sQL = 'SELECT COUNT(id) FROM file WHERE (userId = ' . (int) $Auth->id . ' OR file.uploadedUserId = ' . (int) $Auth->id;
if ($Auth->loggedIn()) {
    // clause to add any shared files
    $sQL .= ' OR ((file.folderId IN (SELECT folder_id FROM file_folder_share WHERE file_folder_share.shared_with_user_id = ' . (int) $Auth->id . ')) AND file.status = "active")';
}
$sQL .= ') AND status = "active"';
$result['totalActiveFiles'] = (int) $db->getValue($sQL);

// get total used space
$result['totalActiveFileSize'] = file::getTotalActiveFileSizeByUser($Auth->id);
$result['totalFileStorage'] = UserPeer::getMaxFileStorage($Auth->id);
$result['totalActiveFileSizeFormatted'] = coreFunctions::formatSize($result['totalActiveFileSize']);
$storagePercentage = 0;
if ($result['totalActiveFileSize'] > 0) {
    $storagePercentage = ($result['totalActiveFileSize'] / $result['totalFileStorage']) * 100;
    if ($storagePercentage < 1) {
        $storagePercentage = 1;
    }
    else {
        $storagePercentage = floor($storagePercentage);
    }
}
$result['totalStoragePercentage'] = $storagePercentage;

// get folder listing
$folderListing = fileFolder::loadAllActiveForSelect($Auth->id, '|||');
$folderListingArr = array();
foreach ($folderListing AS $k => $folderListingItem) {
    $folderListingArr[$k] = validation::safeOutputToScreen($folderListingItem);
}
$result['folderArray'] = json_encode($folderListing);

// create the drop-down select for the uploader
$folderArr = fileFolder::loadAllActiveForSelect($Auth->id);
$html = '';
$html .= '<select id="folder_id" name="folder_id" class="form-control">';
$html .= '<option value="">' . t("index_default", "- default -") . '</option>';
if (COUNT($folderArr)) {
    foreach ($folderArr AS $id => $folderLabel) {
        $html .= '<option value="' . (int) $id . '">' . validation::safeOutputToScreen($folderLabel) . '</option>';
    }
}
$html .= '</select>';
$result['folderSelectForUploader'] = $html;

echo json_encode($result);
exit;
