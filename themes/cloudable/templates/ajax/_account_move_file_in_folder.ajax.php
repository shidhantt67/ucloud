<?php

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// prepare result
$result = array();
$result['error'] = false;
$result['msg'] = 'Files moved.';

if (_CONFIG_DEMO_MODE == true) {
    $result['error'] = true;
    $result['msg'] = t("no_changes_in_demo_mode");
}
elseif (corefunctions::getUsersAccountLockStatus($Auth->id) == 1) {
    $result['error'] = true;
    $result['msg'] = t('account_locked_folder_edit_error_message', 'This account has been locked, please unlock the account to regain full functionality.');
}
else {
    $folderId = NULL;

    // try to load the folder
    $newStatus = 'active';
    $fileFolder = fileFolder::loadById((int) $_REQUEST['folderId']);
    if ($fileFolder) {
        $newStatus = $fileFolder->status;
        // make sure the current logged in user is the owner
        if ($fileFolder->userId == $Auth->id) {
            $folderId = (int) $fileFolder->id;
        }
        // if not, check to see if the current user has access rights
        else {
            $hasAccess = $db->getValue('SELECT id FROM file_folder_share WHERE folder_id = :folder_id AND shared_with_user_id = :shared_with_user_id AND share_permission_level IN ("all", "upload_download") LIMIT 1', array(
                'folder_id' => $fileFolder->id,
                'shared_with_user_id' => $Auth->id,
            ));
            if ($hasAccess) {
                // user has write access
                $folderId = (int) $fileFolder->id;
            }
        }
    }

    // update files
    $fileIds = $_REQUEST['fileIds'];
    if (COUNT($fileIds)) {
        $filteredIds = array();
        foreach ($fileIds AS $fileId) {
            $filteredIds[] = (int) $fileId;
        }

        // load all original filenames to check for duplicates
        $oldFolderId = null;
        $files = $db->getRows('SELECT originalFilename, folderId FROM file WHERE id IN (' . implode(',', $filteredIds) . ') AND (userId = ' . (int) $Auth->id . ' OR file.uploadedUserId = ' . (int) $Auth->id . ')');
        $originalFilenames = array();
        foreach ($files AS $file) {
            $originalFilenames[] = $db->quote($file['originalFilename']);
            $oldFolderId = $file['folderId'];
        }

        // make sure files don't exist already in folder
        $total = (int) $db->getValue('SELECT COUNT(id) AS total FROM file WHERE originalFilename IN (' . implode(',', $originalFilenames) . ') AND status = "'.$newStatus.'" AND folderId ' . ($folderId == NULL ? '= NULL' : '= ' . (int) $folderId) . ' AND (userId = ' . (int) $Auth->id . ' OR file.uploadedUserId = ' . (int) $Auth->id . ')');
        if ($total > 0) {
            $result['error'] = true;
            $result['msg'] = t("items_with_same_name_in_folder", "There are already [[[TOTAL_SAME]]] file(s) with the same filename in that folder. Files can not be moved.", array('TOTAL_SAME' => $total));
        }
        else {
            $db->query('UPDATE file SET folderId ' . ($folderId == NULL ? '= NULL' : '= ' . (int) $folderId) . ', status="'.$newStatus.'", date_updated=NOW() WHERE id IN (' . implode(',', $filteredIds) . ') AND (userId = ' . (int) $Auth->id . ' OR file.uploadedUserId = ' . (int) $Auth->id . ')');

            // clear file preview cache
            if (COUNT($filteredIds)) {
                $pluginObj = pluginHelper::getInstance('filepreviewer');
                foreach ($filteredIds AS $fileId) {
                    $pluginObj->deleteImagePreviewCache((int) $fileId);
                }
            }

            // update the old folder total
            if ($oldFolderId !== null) {
                fileFolder::updateFolderFilesize((int) $oldFolderId);
            }

            // update the new folder total
            fileFolder::updateFolderFilesize((int) $fileFolder->id);
        }
    }
    
    // update folders
    $folderIds = $_REQUEST['folderIds'];
    if (COUNT($folderIds)) {
        $filteredIds = array();
        foreach ($folderIds AS $folderIdItem) {
            $filteredIds[] = (int) $folderIdItem;
        }

        // make sure $fileFolder does not existing in list of folders
        if (($key = array_search($folderId, $filteredIds)) !== false) {
            unset($filteredIds[$key]);
        }

        // load all original filenames to check for duplicates
        $oldFolderId = null;
        $folders = $db->getRows('SELECT * FROM file_folder WHERE id IN (' . implode(',', $filteredIds) . ') AND (userId = ' . (int) $Auth->id . ')');
        $folderNames = array();
        foreach ($folders AS $folder) {
            $folderNames[] = $db->quote($folder['folderName']);
            $oldFolderId = (int) $folder['parentId'] != 0 ? $folder['parentId'] : null;
        }

        // make sure files don't exist already in folder
        $total = (int) $db->getValue('SELECT COUNT(id) AS total FROM file_folder WHERE folderName IN (' . implode(',', $folderNames) . ') AND status = "'.$newStatus.'" AND parentId ' . ($folderId == NULL ? '= NULL' : '= ' . (int) $folderId) . ' AND (userId = ' . (int) $Auth->id . ')');
        if ($total > 0) {
            $result['error'] = true;
            $result['msg'] = t("folders_with_same_name_in_folder", "There are already [[[TOTAL_SAME]]] folders(s) with the same name in that folder. Folders can not be moved.", array('TOTAL_SAME' => $total));
        }
        else {
            // restore if the folder is in the trash
            foreach ($folders AS $folderItem) {
                // hydrate
                $folder = fileFolder::hydrate($folderItem);

                // if this is a trash item, restore it
                if ($folder->status === 'trash') {
                    $folder->restoreFromTrash($folderId);
                }
                else {
                    $db->query('UPDATE file_folder SET parentId ' . ($folderId == NULL ? '= NULL' : '= ' . (int) $folderId) . ', status="'.$newStatus.'", date_updated=NOW() WHERE id = ' . $folder->id . ' AND (userId = ' . (int) $Auth->id . ')');
                }
            }

            // update the old folder total
            if ($oldFolderId !== null) {
                fileFolder::updateFolderFilesize((int) $oldFolderId);
            }

            // update the new folder total
            if ($folderId !== null) {
                fileFolder::updateFolderFilesize((int) $fileFolder->id);
            }
        }
    }
}

echo json_encode($result);
exit;
