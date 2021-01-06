<?php

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

//setup database
$db = Database::getDatabase(true);

// load folder structure as array
$folderListing = fileFolder::loadAllActiveForSelect($Auth->id);

// handle submission
if ((int) $_REQUEST['submitme']) {
    // make sure the user owns the folder to restore to
    $restoreFolderId = (int) $_REQUEST['restoreFolderId'];
    
    // load existing folder data
    if($restoreFolderId > 0) {
        $fileFolder = fileFolder::loadById((int) $restoreFolderId);
        if ($fileFolder->userId !== $Auth->id) {
            // setup edit folder
            $restoreFolderId = 0;
        }
    }
    
    // if $restoreFolderId = 0, assume root, which is null
    $restoreFolderId = (int)$restoreFolderId===0?null:(int)$restoreFolderId;
    
    // get the file and folder ids
    $fileIds = $_REQUEST['fileIds'];
    $safeFileIds = array();
    foreach($fileIds AS $fileId) {
        $safeFileIds[] = (int)$fileId;
    }

    $folderIds = $_REQUEST['folderIds'];
    $safeFolderIds = array();
    foreach($folderIds AS $folderId) {
        $safeFolderIds[] = (int)$folderId;
    }

    // load our items for later
    $checkedFiles = $db->getRows('SELECT * '
            . 'FROM file '
            . 'WHERE id IN ('.implode(',', $safeFileIds).') '
            . 'AND (userId = :userId OR uploadedUserId = :uploadedUserId)', array(
                'userId' => $Auth->id,
                'uploadedUserId' => $Auth->id,
            ));
    $checkedFolders = $db->getRows('SELECT * '
            . 'FROM file_folder '
            . 'WHERE id IN ('.implode(',', $safeFolderIds).') '
            . 'AND userId = :userId', array(
                'userId' => $Auth->id,
            ));

    // restore folders
    if(COUNT($checkedFolders)) {
        foreach($checkedFolders AS $checkedFolder) {
            // hydrate to get access to the object methods
            $folder = fileFolder::hydrate($checkedFolder);

            // restore the file
            $folder->restoreFromTrash($restoreFolderId);
        }
    }
    
    // restore files
    if(COUNT($checkedFiles)) {
        foreach($checkedFiles AS $checkedFile) {
            // hydrate to get access to the object methods
            $file = file::hydrate($checkedFile);

            // restore the file
            $file->restoreFromTrash($restoreFolderId);
        }
    }
}

// prepare result
$returnJson = array();
$returnJson['success'] = false;
$returnJson['msg'] = t("problem_restoring_items", "There was a problem restoring the items, please try again later.");
if (notification::isErrors()) {
    // error
    $returnJson['success'] = false;
    $returnJson['msg'] = implode('<br/>', notification::getErrors());
}
else {
    // success
    $returnJson['success'] = true;
    $returnJson['msg'] = implode('<br/>', notification::getSuccess());
}

echo json_encode($returnJson);
