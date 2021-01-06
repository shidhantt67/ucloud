<?php

class fileFolder
{

    public function getFolderUrl() {
        return WEB_ROOT . '/folder/' . (int) $this->id . '/' . $this->getSafeFoldernameForUrl();
    }

    public function getAlbumUrl() {
        return WEB_ROOT . '/album/' . (int) $this->id . '/' . $this->getSafeFoldernameForUrl();
    }

    public function getSafeFoldernameForUrl() {
        return str_replace(array(" ", "\"", "'", ";", "#", "%"), "_", strip_tags($this->folderName));
    }

    public function getCoverData() {
        $db = Database::getDatabase();

        // get convert id
        $coverImageId = $this->coverImageId;
        if ($coverImageId == null) {
            // load new and set in the db
            $coverImageData = $db->getRow('SELECT id, unique_hash FROM file WHERE folderId = ' . (int) $this->id . ' AND status = "active" AND extension IN(' . file::getImageExtStringForSql() . ') LIMIT 1');
            if ($coverImageData) {
                $this->setCoverId($coverImageData['id']);
            }

            // make sure we have the file hash
            $uniqueHash = $coverImageData['unique_hash'];
            if (strlen($uniqueHash) == 0) {
                $uniqueHash = file::createUniqueFileHash($coverImageData['id']);
            }

            return array('file_id' => $coverImageData['id'], 'unique_hash' => $uniqueHash);
        }

        // make sure cover image exists, update to new if not
        $coverImageData = $db->getRow('SELECT id, unique_hash FROM file WHERE id = ' . (int) $coverImageId . ' AND status = "active" AND extension IN(' . file::getImageExtStringForSql() . ') LIMIT 1');
        if (!$coverImageData) {
            $coverImageData = $db->getRow('SELECT id, unique_hash FROM file WHERE folderId = ' . (int) $this->id . ' AND status = "active" AND extension IN(' . file::getImageExtStringForSql() . ') LIMIT 1');
            if ($coverImageData) {
                $this->setCoverId($coverImageData['id']);
            }
        }

        // make sure we have the file hash
        $uniqueHash = $coverImageData['unique_hash'];
        if (strlen($uniqueHash) == 0) {
            $uniqueHash = file::createUniqueFileHash($coverImageData['id']);
        }

        return array('file_id' => $coverImageData['id'], 'unique_hash' => $uniqueHash);
    }

    public function setCoverId($coverId) {
        $db = Database::getDatabase();
        return $db->query('UPDATE file_folder SET coverImageId = ' . (int) $coverId . ' WHERE id = ' . (int) $this->id . ' LIMIT 1');
    }

    public function isPublic($publicId = 1) {
        return (($this->isPublic) >= (int) $publicId);
    }

    public function getOwner() {
        return UserPeer::loadUserById($this->userId);
    }

    public function getTotalViews() {
        $db = Database::getDatabase();
        return (int) $db->getValue('SELECT SUM(visits) AS total FROM file WHERE folderId = ' . (int) $this->id);
    }

    public function getTotalLikes() {
        $db = Database::getDatabase();
        return (int) $db->getValue('SELECT SUM(total_likes) AS total FROM file WHERE folderId = ' . (int) $this->id);
    }

    public function totalChildFolderCount() {
        $db = Database::getDatabase();
        return (int) $db->getValue('SELECT COUNT(id) AS total FROM file_folder WHERE parentId = ' . (int) $this->id);
    }

    public function totalFileCount() {
        $db = Database::getDatabase();
        return (int) $db->getValue('SELECT COUNT(id) AS total FROM file WHERE status = "active" AND folderId ' . ($this->id == null ? 'is null' : ('= ' . (int) $this->id)) . ' AND userId = ' . (int) $this->userId);
    }

    /**
     * Method to set folder
     */
    public function updateParentFolder($parentId = NULL) {
        $db = Database::getDatabase();
        $parentId = (int) $parentId;
        $sQL = 'UPDATE file_folder SET parentId = ';
        if ($parentId == 0) {
            $sQL .= 'NULL';
        }
        else {
            $sQL .= (int) $parentId;
        }
        $sQL .= ' WHERE id = :id';
        $db->query($sQL, array('id' => $this->id));
    }

    static function getActiveFoldersByUser($userId) {
        // first check for folders in cache and load it if found
        if (cache::cacheExists('FOLDER_ACTIVE_OBJECTS_BY_USERID_' . (int) $userId) == false) {
            $db = Database::getDatabase(true);
            $rows = $db->getRows('SELECT file_folder.*, '
                    . 'file_folder_share.shared_with_user_id, file_folder_share.share_permission_level '
                    . 'FROM file_folder '
                    . 'LEFT JOIN file_folder_share ON file_folder.id = file_folder_share.folder_id '
                    . 'WHERE (file_folder.userId = ' . (int) $userId . ' '
                    . 'OR (file_folder_share.shared_with_user_id = ' . (int) $userId . ' '
                    . 'AND file_folder_share.share_permission_level IN ("upload_download", "all"))) '
                    . 'AND file_folder.status = "active" '
                    . 'ORDER BY folderName ASC');

            // cache for later
            cache::setCache('FOLDER_ACTIVE_OBJECTS_BY_USERID_' . (int) $userId, $rows);
        }

        // get from cache
        return cache::getCache('FOLDER_ACTIVE_OBJECTS_BY_USERID_' . (int) $userId);
    }

    static function loadById($id) {
        $db = Database::getDatabase(true);
        $row = $db->getRow('SELECT * FROM file_folder WHERE id = ' . (int) $id);
        if (!is_array($row)) {
            return false;
        }

        $folderObj = new fileFolder();
        foreach ($row AS $k => $v) {
            $folderObj->$k = $v;
        }

        return $folderObj;
    }
    
    /**
     * Remove by user
     */
    public function trashByUser() {
        // trigger trash static method
        return fileFolder::trashFolder($this->id);
    }
    
    static function trashFolder($folderId) {
        // get db
        $db = Database::getDatabase(true);
        
        // load folder details for later
        $folder = fileFolder::loadById($folderId);

        // recurrsive delete folders
        self::_trashFolder($folderId);

        // update parent folder total filesize
        if ($folder->parentId !== NULL) {
            self::updateFolderFilesize($folder->parentId);
        }
        
        // set the parentId to null so it shows in the root of deleted
        $db->query('UPDATE file_folder '
                . 'SET parentId = NULL, '
                . 'date_updated=NOW() '
                . 'WHERE id = :id '
                . 'LIMIT 1', array(
                    'id' => (int) $folderId,
                ));

        return true;
    }

    static function _trashFolder($folderId) {
        // get db
        $db = Database::getDatabase(true);

        // load children
        $subFolders = $db->getRows('SELECT id '
                . 'FROM file_folder '
                . 'WHERE parentId = :parent_id', array(
                    'parent_id' => $folderId,
                ));
        if ($subFolders) {
            foreach ($subFolders AS $subFolder) {
                self::_trashFolder($subFolder['id']);
            }
        }

        // delete any shared entries
        $db->query('DELETE '
                . 'FROM file_folder_share '
                . 'WHERE folder_id = :folder_id', array(
                    'folder_id' => (int) $folderId,
                ));

        // delete the folder
        $db->query('UPDATE file SET status = "trash", date_updated=NOW() WHERE folderId = ' . (int) $folderId);
        $db->query('UPDATE file_folder SET status = "trash", date_updated=NOW() WHERE id = ' . (int) $folderId . ' LIMIT 1');
    }
    
    /**
     * Restore folder from trash
     */
    public function restoreFromTrash($restoreFolderId = null) {
        // trigger trash static method
        return fileFolder::untrashFolder($this->id, $restoreFolderId);
    }
    
    static function untrashFolder($folderId, $restoreFolderId = null) {
        // get db
        $db = Database::getDatabase(true);

        // recurrsive delete folders
        self::_untrashFolder($folderId, $restoreFolderId);

        // update parent folder total filesize
        if ($restoreFolderId !== null) {
            self::updateFolderFilesize($restoreFolderId);
        }
        
        // set the parentId to $restoreFolderId so it's full restored
        $db->query('UPDATE file_folder '
                . 'SET parentId = '.($restoreFolderId===null?'NULL':(int)$restoreFolderId).', '
                . 'date_updated=NOW() '
                . 'WHERE id = :id '
                . 'LIMIT 1', array(
                    'id' => (int) $folderId,
                ));

        return true;
    }

    static function _untrashFolder($folderId, $restoreFolderId = null) {
        // get db
        $db = Database::getDatabase(true);

        // load children
        $subFolders = $db->getRows('SELECT id '
                . 'FROM file_folder '
                . 'WHERE parentId = :parent_id', array(
                    'parent_id' => $folderId,
                ));
        if ($subFolders) {
            foreach ($subFolders AS $subFolder) {
                self::_untrashFolder($subFolder['id'], $restoreFolderId);
            }
        }

        // delete the folder
        $db->query('UPDATE file SET status = "active", date_updated=NOW() WHERE folderId = ' . (int) $folderId);
        $db->query('UPDATE file_folder SET status = "active", date_updated=NOW() WHERE id = ' . (int) $folderId . ' LIMIT 1');
    }

    /**
     * Remove by user
     */
    public function removeByUser() {
        // trigger delete static method
        return fileFolder::deleteFolder($this->id);
    }
    
    /**
     * Remove by system
     */
    public function removeBySystem() {
        // trigger delete static method
        return fileFolder::deleteFolder($this->id);
    }
    
    static function deleteFolder($folderId) {
        // get db
        $db = Database::getDatabase(true);
        
        // load folder details for later
        $folder = fileFolder::loadById($folderId);

        // recurrsive delete folders
        self::_deleteFolder($folderId);

        // update parent folder total filesize
        if ($folder->parentId !== NULL) {
            self::updateFolderFilesize($folder->parentId);
        }
        
        // set the parentId to null so it shows in the root of deleted
        $db->query('UPDATE file_folder '
                . 'SET status = "deleted", '
                . 'date_updated = NOW() '
                . 'WHERE id = :folder_id '
                . 'LIMIT 1', array(
                    'folder_id' => (int) $folderId,
                ));

        return true;
    }

    static function _deleteFolder($folderId) {
        // get db
        $db = Database::getDatabase(true);

        // load children
        $subFolders = $db->getRows('SELECT id '
                . 'FROM file_folder '
                . 'WHERE parentId = :parent_id', array(
            'parent_id' => (int) $folderId,
        ));
        if ($subFolders) {
            foreach ($subFolders AS $subFolder) {
                self::_deleteFolder($subFolder['id']);
            }
        }

        // delete any shared entries
        $db->query('DELETE FROM file_folder_share WHERE folder_id = ' . (int) $folderId);
        
        // get all files and schedule for deletion
        $filesArr = $db->getRows('SELECT * '
                . 'FROM file '
                . 'WHERE folderId = :folderId '
                . 'AND status != "deleted"', array(
            'folderId' => (int) $folderId
        ));
        if($filesArr) {
            foreach($filesArr AS $filesArrItem) {
                // get our object so we have access to the file methods
                $file = file::hydrate($filesArrItem);
                
                // schedule for removal
                $file->removeByUser();
            }
        }

        // delete the folder
        $db->query('UPDATE file_folder '
                . 'SET status = "deleted", '
                . 'date_updated=NOW(), '
                . 'totalSize = 0 '
                . 'WHERE id = :id '
                . 'LIMIT 1', array(
                    'id' => (int) $folderId,
                ));
    }

    /**
     * Create unique sharing url. Allow 'private' folders to be accessed without an account login.
     */
    public function createUniqueSharingUrl($userId = null, $permissionType = 'view', $recurrsive = true) {
        // get db
        $db = Database::getDatabase();

        // check for existing
        if ($userId) {
            $accessKey = $db->getValue('SELECT access_key FROM file_folder_share WHERE folder_id = ' . (int) $this->id . ' AND created_by_user_id = ' . (int) $this->userId . ' AND shared_with_user_id = ' . (int) $userId . ' LIMIT 1');
            if ($accessKey) {
                return $this->getFolderUrl() . '?sharekey=' . $accessKey;
            }
        }

        // get subfolder ids if recurrsive add
        $folderIds = array($this->id);
        if ($recurrsive === true) {
            $folderIds = fileFolder::getAllChildFolderIdsRecurrsive($this->id);
        }

        // loop the folders and grant access
        $primaryAccessKey = '';
        foreach ($folderIds AS $folderId) {
            // generate random accessKey
            $accessKey = coreFunctions::generateRandomString(64);

            // add to the database
            $db->query('INSERT INTO file_folder_share (folder_id, access_key, date_created, created_by_user_id, shared_with_user_id, share_permission_level) VALUES (' . (int) $folderId . ',  ' . $db->quote($accessKey) . ', NOW(), ' . (int) $this->userId . ', ' . ((int) $userId ? (int) $userId : 'null') . ', ' . $db->quote($permissionType) . ')');

            // capture access key for later use
            if ($folderId == $this->id) {
                $primaryAccessKey = $accessKey;
            }
        }

        // return url
        return $this->getFolderUrl() . '?sharekey=' . $primaryAccessKey;
    }

    public function getAllSharedUsers() {
        // get db
        $db = Database::getDatabase();

        // get list of shares
        return $db->getRows('SELECT users.email, users.id AS user_id, file_folder_share.id, file_folder_share.share_permission_level FROM file_folder_share LEFT JOIN users ON file_folder_share.shared_with_user_id = users.id WHERE file_folder_share.shared_with_user_id IS NOT NULL AND file_folder_share.folder_id = ' . (int) $this->id);
    }

    public function removeUniqueSharingUrl($folderShareId, $recurrsive = true) {
        // get db
        $db = Database::getDatabase();

        // initially lookup user id for later query
        $sharedWithUserId = (int) $db->getValue('SELECT shared_with_user_id FROM file_folder_share WHERE id = :id LIMIT 1', array(
                    'id' => $folderShareId
        ));

        // get subfolder ids if recurrsive remove
        $folderIds = array($this->id);
        if ($recurrsive === true) {
            $folderIds = fileFolder::getAllChildFolderIdsRecurrsive($this->id);
        }

        // remove the share
        return $db->query('DELETE FROM file_folder_share WHERE folder_id IN (' . implode(',', $folderIds) . ') AND shared_with_user_id = ' . (int) $sharedWithUserId);
    }

    static function loadAllActiveByAccount($accountId) {
        return self::getActiveFoldersByUser($accountId);
    }

    static function loadAllActiveForSelect($accountId, $delimiter = '/') {
        $rs = array();
        $folders = self::loadAllActiveByAccount($accountId);
        if ($folders) {
            // first prepare local array for easy lookups
            $lookupArr = array();
            foreach ($folders AS $folder) {
                $lookupArr[$folder{'id'}] = array('l' => $folder['folderName'], 'p' => $folder['parentId']);
            }

            // populate data
            foreach ($folders AS $folder) {
                $folderLabelArr = array();
                $folderLabelArr[] = $folder['folderName'];
                $failSafe = 0;
                $parentId = $folder['parentId'];
                while (($parentId != NULL) && ($failSafe < 30)) {
                    $failSafe++;
                    if (isset($lookupArr[$parentId])) {
                        $folderLabelArr[] = $lookupArr[$parentId]['l'];
                        $parentId = $lookupArr[$parentId]['p'];
                    }
                }

                $folderLabelArr = array_reverse($folderLabelArr);
                $rs[$folder{'id'}] = implode($delimiter, $folderLabelArr);
            }
        }

        // make pretty
        natcasesort($rs);

        return $rs;
    }

    static function loadAllChildren($parentFolderId = null) {
        $db = Database::getDatabase(true);
        $row = $db->getRows('SELECT * FROM file_folder WHERE parentId = ' . (int) $parentFolderId . ' ORDER BY folderName');
        if (!is_array($row)) {
            return false;
        }

        return $row;
    }

    static function loadAllPublicChildren($parentFolderId = null) {
        $db = Database::getDatabase(true);
        $row = $db->getRows('SELECT * FROM file_folder WHERE parentId = ' . (int) $parentFolderId . ' AND isPublic >= 1 ORDER BY folderName');
        if (!is_array($row)) {
            return false;
        }

        return $row;
    }

    static function convertFolderPathToId($pathStr, $accountId) {
        $folderListing = self::loadAllActiveForSelect($accountId, '/');
        if (COUNT($folderListing)) {
            foreach ($folderListing AS $k => $folderListingItem) {
                if ($folderListingItem == $pathStr) {
                    return $k;
                }
            }
        }

        return NULL;
    }

    static function getFolderCoverData($folderId) {
        $folder = fileFolder::loadById($folderId);
        if (!$folder) {
            return false;
        }

        return $folder->getCoverData();
    }

    /**
     * Hydrate folder data into a Folder object, save reloading from database is we already have the data
     * 
     * @param type $folderDataArr
     * @return Folder
     */
    static function hydrate($folderDataArr) {
        $folderObj = new fileFolder();
        foreach ($folderDataArr AS $k => $v) {
            $folderObj->$k = $v;
        }

        return $folderObj;
    }

    static function getTotalActivePublicFolders() {
        $db = Database::getDatabase();

        return $db->getValue('SELECT COUNT(DISTINCT file_folder.id) FROM file_folder LEFT JOIN file ON file_folder.id = file.folderId WHERE file_folder.isPublic = 2 AND file_folder.accessPassword IS NULL AND file.isPublic != 0');
    }

    static function getAllChildFolderIdsRecurrsive($folderId) {
        $children = array();
        $children[] = $folderId;
        $db = Database::getDatabase();
        $subFolders = $db->getRows('SELECT id FROM file_folder WHERE parentId = :parentId', array(
            'parentId' => $folderId
        ));

        if ($subFolders) {
            foreach ($subFolders AS $subFolder) {
                $children = array_merge($children, self::getAllChildFolderIdsRecurrsive($subFolder['id']));
            }
        }

        return $children;
    }

    static function updateFolderFilesize($folderId) {
        // get database
        $db = Database::getDatabase();

        // load folder
        $folder = fileFolder::loadById($folderId);

        // loop all folders from here up
        $loopTracker = 0;
        $fileSizes = array();
        while ($loopTracker < 30) {
            // get all child folder ids
            $folderIds = self::getAllChildFolderIdsRecurrsive((int) $folder->id);

            // load total filesize including all the child folder ids
            $fileSizes[(int) $folder->id] = $db->getValue('SELECT SUM(fileSize) AS total FROM file WHERE folderId IN (' . implode(',', $folderIds) . ') AND status = "active"');

            // update the value stored in the database
            $rs = $db->query('UPDATE file_folder SET totalSize = :total_size WHERE id = :id LIMIT 1', array(
                'id' => (int) $folder->id,
                'total_size' => $fileSizes[(int) $folder->id],
            ));

            // loop again if we have a parentId
            if ($folder->parentId !== NULL) {
                $folder = fileFolder::loadById($folder->parentId);
                if (!$folder) {
                    $loopTracker = 30;
                }
                else {
                    $loopTracker++;
                }
            }
            else {
                $loopTracker = 30;
            }
        }

        return $fileSizes[$folderId];
    }

    static function copyPermissionsToNewFolder($fromFolderId, $toFolderId) {
        // get database
        $db = Database::getDatabase();

        // load sharing for current folder
        $sharing = $db->getRows('SELECT shared_with_user_id, share_permission_level FROM file_folder_share WHERE folder_id = :folder_id', array(
            'folder_id' => $fromFolderId,
        ));

        // get to folder object
        $toFolder = fileFolder::loadById($toFolderId);
        if (!$toFolder) {
            return false;
        }

        // loop existing and ensure they're added
        if (COUNT($sharing)) {
            foreach ($sharing AS $sharingItem) {
                $toFolder->createUniqueSharingUrl($sharingItem['shared_with_user_id'], $sharingItem['share_permission_level']);
            }
        }
    }

}
