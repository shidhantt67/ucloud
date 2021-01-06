<?php

/* setup includes */
require_once('../../../core/includes/master.inc.php');

// for cross domain access
coreFunctions::allowCrossSiteAjax();

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// prepare result
$result = array();
$result['error'] = false;
$result['msg'] = '';

// pick up file ids
$fileIds = $_REQUEST['fileIds'];
$folderIds = $_REQUEST['folderIds'];

if (_CONFIG_DEMO_MODE == true) {
    $result['error'] = true;
    $result['msg'] = t("no_changes_in_demo_mode");
}
elseif (coreFunctions::getUsersAccountLockStatus($Auth->id) == 1) {
    $result['error'] = true;
    $result['msg'] = t('account_locked_error_message', 'This account has been locked, please unlock the account to regain full functionality.');
}
else {
    $totalRemoved = 0;

    // track the affected folders so we can fix stats later
    $affectedFolderIds = array();
    
    // do folder removals
    if (COUNT($folderIds)) {
        foreach ($folderIds AS $folderId) {
            // load folder and process if active and belongs to the currently logged in user
            $folder = fileFolder::loadById($folderId);
            if (($folder) && ($folder->status == 'trash') && ($folder->userId == $Auth->id)) {
                // log folder id for later
                if ((int) $folder->parentId) {
                    $affectedFolderIds[$folder->parentId] = $folder->parentId;
                }

                // remove file
                $rs = $folder->removeByUser();
                if ($rs) {
                    $totalRemoved++;
                }
            }
        }
    }
    
    // do file removals
    if (COUNT($fileIds)) {
        foreach ($fileIds AS $fileId) {
            // load file and process if active and belongs to the currently logged in user
            $file = file::loadById($fileId);
            if (($file) && ($file->status == 'trash') && ($file->userId == $Auth->id || $file->uploadedUserId == $Auth->id)) {
                // log folder id for later
                if ((int) $file->folderId) {
                    $affectedFolderIds[$file->folderId] = $file->folderId;
                }

                // remove file
                $rs = $file->removeByUser();
                if ($rs) {
                    $totalRemoved++;
                }
            }
        }
    }

    // handle folder sizes regeneration
    if (COUNT($affectedFolderIds)) {
        foreach ($affectedFolderIds AS $affectedFolderId) {
            fileFolder::updateFolderFilesize((int) $affectedFolderId);
        }
    }

    $result['msg'] = 'Permanently deleted ' . $totalRemoved . ' file' . ($totalRemoved != 1 ? 's' : '') . '.';
}

echo json_encode($result);
exit;
