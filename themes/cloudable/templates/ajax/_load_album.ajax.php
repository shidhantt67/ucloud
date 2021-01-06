<?php

// layout settings
$thumbnailType = themeHelper::getConfigValue('thumbnail_type');

// per page options
$perPageOptions = array(15, 30, 50, 100, 250);
$defaultPerPage = 30;
$shareAccessLevel = 'none';

// sorting options
$sortingOptions = array();
$sortingOptions['order_by_filename_asc'] = t('order_by_filename_asc', 'Filename ASC');
$sortingOptions['order_by_filename_desc'] = t('order_by_filename_desc', 'Filename DESC');
$sortingOptions['order_by_uploaded_date_asc'] = t('order_by_uploaded_date_asc', 'Uploaded Date ASC');
$sortingOptions['order_by_uploaded_date_desc'] = t('order_by_uploaded_date_desc', 'Uploaded Date DESC');
$sortingOptions['order_by_downloads_asc'] = t('order_by_downloads_asc', 'Total Downloads ASC');
$sortingOptions['order_by_downloads_desc'] = t('order_by_downloads_desc', 'Total Downloads DESC');
$sortingOptions['order_by_filesize_asc'] = t('order_by_filesize_asc', 'Filesize ASC');
$sortingOptions['order_by_filesize_desc'] = t('order_by_filesize_desc', 'Filesize DESC');
$sortingOptions['order_by_last_access_date_asc'] = t('order_by_last_access_date_asc', 'Last Access Date ASC');
$sortingOptions['order_by_last_access_date_desc'] = t('order_by_last_access_date_desc', 'Last Access Date DESC');
$defaultSorting = 'order_by_filename_asc';

// some initial headers
header("HTTP/1.0 200 OK");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Pragma: no-cache");

// setup response
$returnJson = array();
$returnJson['html'] = '';
$returnJson['javascript'] = '';

// setup session params
if(!isset($_SESSION['search']))
{
    $_SESSION['search'] = array();
}
if(!isset($_SESSION['search']['perPage']))
{
    $_SESSION['search']['perPage'] = $defaultPerPage;
}
if(!isset($_SESSION['search']['filterOrderBy']))
{
    $_SESSION['search']['filterOrderBy'] = $defaultSorting;
}
if(!isset($_SESSION['browse']['viewType']))
{
    $_SESSION['browse']['viewType'] = 'fileManagerIcon';
    if(SITE_CONFIG_FILE_MANAGER_DEFAULT_VIEW == 'list')
    {
        $_SESSION['browse']['viewType'] = 'fileManagerList';
    }
}

// setup initial params
$pageStart = (int) $_REQUEST['pageStart'];
$perPage = (int) $_REQUEST['perPage'] > 0 ? (int) $_REQUEST['perPage'] : $_SESSION['search']['perPage'];
$filterOrderBy = strlen($_REQUEST['filterOrderBy']) ? $_REQUEST['filterOrderBy'] : $_SESSION['search']['filterOrderBy'];
$searchTerm = isset($_REQUEST['searchFilter']) ? trim($_REQUEST['searchFilter']) : '';

// advanced filters
$advFilters = isset($_REQUEST['advFilters']) ? $_REQUEST['advFilters'] : array();
$filterImagesAll = (isset($advFilters['filterImagesAll']) && ($advFilters['filterImagesAll'] == 'false')) ? false : true;
$filterUploadedDateRange = (isset($advFilters['filterUploadedDateRange']) && strlen($advFilters['filterUploadedDateRange'])) ? $advFilters['filterUploadedDateRange'] : null;

$searchType = null;
$addClause = '';

$statusFilter = 'active';
$foldersClause = "WHERE 1=1 ";
$userOwnsFolder = false;
if(isset($_REQUEST['nodeId']))
{
    $nodeId = $_REQUEST['nodeId'];
    switch($nodeId)
    {
        case 'recent':
            $searchType = 'recent';
            $foldersClause .= ' AND 1=2'; // disable
            $shareAccessLevel = 'all';
            break;
        case 'trash':
            $searchType = 'trash';
            $statusFilter = 'trash';
            $userOwnsFolder = true;
            break;
        case 'all':
            $searchType = 'all';
            $foldersClause .= ' AND 1=2'; // disable
            $shareAccessLevel = 'all';
            break;
        case '-1':
            $searchType = 'root';
            $foldersClause .= " AND file_folder.parentId IS NULL AND ((userId = " . (int) $Auth->id . ")";

            if($Auth->loggedIn())
            {
                // clause to add any shared folders
                $foldersClause .= ' OR (file_folder_share.shared_with_user_id = '.(int)$Auth->id.' AND (file_folder.parentId NOT IN (SELECT folder_id FROM file_folder_share WHERE shared_with_user_id = '.(int)$Auth->id.') OR parentId IS NULL))';
            }
            $foldersClause .= ")";
            break;
        default:
            $searchType = 'folder';
            $foldersClause .= " AND file_folder.parentId = " . (int) $nodeId;
            break;
    }
}
elseif(isset($_REQUEST['userId']))
{
    $userId = $_REQUEST['userId'];
    if(isset($_REQUEST['likes']))
    {
        $searchType = 'likes';
    }
    else
    {
        $searchType = 'user';
    }
    $foldersClause .= ' AND 1=2'; // disable
}
elseif(isset($_REQUEST['searchType']))
{
    if(in_array($_REQUEST['searchType'], array('browserecent')))
    {
        $searchType = $_REQUEST['searchType'];
    }
    $foldersClause .= ' AND 1=2'; // disable
}

// for recent uploads
if(($searchType == 'recent') || ($searchType == 'browserecent'))
{
    $filterOrderBy = 'order_by_uploaded_date_desc';
}

// save session params
$_SESSION['search']['perPage'] = $perPage;
$_SESSION['search']['filterOrderBy'] = $filterOrderBy;

// setup page title for later
$pageTitle = t('files', 'Files');
$pageUrl = '';
$folder = null;
$owner = null;
$folderId = null;
if($searchType == 'folder')
{
    $folder = fileFolder::loadById($nodeId);
    if($folder)
    {
        $pageTitle = $folder->folderName;
        $pageUrl = $folder->getFolderUrl();
        $folderId = $folder->id;
        
        // if the folder is in the trash, ensure we only show trashed files or
        // sub-folders within
        $statusFilter = $folder->status;

        if((int) $folder->userId)
        {
            // get folder owner details
            $owner = UserPeer::loadUserById($folder->userId);

            // store if the current user owns the folder
            if($owner->id === $Auth->id)
            {
                $userOwnsFolder = true;
                $shareAccessLevel = 'all';
            }
            // internally shared folders
            elseif($Auth->loggedIn())
            {
                // setup access if user has been granted share access to the folder
                $_SESSION['sharekey'.$nodeId] = false;
                $shareData = $db->getRow('SELECT id, share_permission_level, access_key FROM file_folder_share WHERE shared_with_user_id = ' . (int) $Auth->id . ' AND folder_id = ' . (int) $folder->id . ' LIMIT 1');
                if($shareData)
                {
                    $db->query('UPDATE file_folder_share SET last_accessed = NOW() WHERE id = ' . (int) $shareData['id'] . ' LIMIT 1');
                    $_SESSION['sharekey' . $folder->id] = true;
                    $shareAccessLevel = $shareData['share_permission_level'];
                }
            }
        }

        // privacy
        if(((int) $folder->userId > 0) && ($folder->userId != $Auth->id))
        {
            if(coreFunctions::getOverallPublicStatus($folder->userId, $folder->id) == false)
            {
                // output response
                $returnJson['html'] = '<div class="ajax-error-image"><!-- --></div>';
                $returnJson['page_title'] = UCWords(t('error', 'Error'));
                $returnJson['page_url'] = '';
                $returnJson['javascript'] = 'showErrorNotification("' . str_replace("\"", "'", UCWords(t('error', 'Error'))) . '", "' . str_replace("\"", "'", t('folder_is_not_publicly_shared_please_contact', 'Folder is not publicly shared. Please contact the owner and request they update the privacy settings.')) . '");';
                echo json_encode($returnJson);
                exit;
            }
        }

        // check if folder needs a password, ignore if logged in as the owner
        //if((strlen($folder->accessPassword) > 0) && ($owner->id != $Auth->id) && ($_SESSION['sharekey'.$folder->id] == false))
        if((strlen($folder->accessPassword) > 0) && ($owner->id != $Auth->id))
        {
            // see if we have it in the session already
            $askPassword = true;
            if(!isset($_SESSION['folderPassword']))
            {
                $_SESSION['folderPassword'] = array();
            }
            elseif(isset($_SESSION['folderPassword'][$folder->id]))
            {
                if($_SESSION['folderPassword'][$folder->id] == $folder->accessPassword)
                {
                    $askPassword = false;
                }
            }

            if($askPassword == true)
            {
                // output response
                $returnJson['html'] = '<div class="ajax-error-image"><!-- --></div><div id="albumPasswordModel" data-backdrop="static" data-keyboard="false" class="albumPasswordModel modal fade custom-width general-modal"><div class="modal-dialog"><div class="modal-content"><form id="folderPasswordForm" action="' . WEB_ROOT . '/ajax/_folder_password.process.ajax.php" autocomplete="off" onSubmit="$(\'#password-submit-btn\').click(); return false;"><div class="modal-body">';

                $returnJson['html'] .= '<div class="row">';
                $returnJson['html'] .= '	<div class="col-md-3">';
                $returnJson['html'] .= '		<div class="modal-icon-left"><img src="' . SITE_IMAGE_PATH . '/modal_icons/shield_lock.png"/></div>';
                $returnJson['html'] .= '	</div>';
                $returnJson['html'] .= '	<div class="col-md-9">';
                $returnJson['html'] .= '		<h4>' . t('password_required', 'Password Required') . '</h4><hr style="margin-top: 5px;"/>';
                $returnJson['html'] .= '		<div class="form-group">';
                $returnJson['html'] .= '			<p>' . t('this_folder_has_a_password_set', 'This folder requires a password to gain access. Use the form below to enter the password, then click "unlock".') . '</p>';
                $returnJson['html'] .= '		</div>';

                $returnJson['html'] .= '		<div class="form-group">';
                $returnJson['html'] .= '			<label for="folderName" class="control-label">' . UCWords(t('access_password', 'Access Password')) . ':</label>';
                $returnJson['html'] .= '			<div class="input-grsoup">';
                $returnJson['html'] .= '				<input type="password" name="folderPassword" id="folderPassword" class="form-control" placeholder="************"/>';
                $returnJson['html'] .= '			</div>';
                $returnJson['html'] .= '		</div>';
                $returnJson['html'] .= '	</div>';
                $returnJson['html'] .= '</div>';

                $returnJson['html'] .= '</div><div class="modal-footer" style="margin-top: 0px;">';
                $returnJson['html'] .= '<input type="hidden" value="' . (int) $folder->id . '" id="folderId" name="folderId"/>';
                $returnJson['html'] .= '<input type="hidden" value="1" id="submitme" name="submitme"/>';
                $returnJson['html'] .= '<button type="button" class="btn btn-default" data-dismiss="modal">' . t('cancel', 'Cancel') . '</button>';
                $returnJson['html'] .= '<button type="button" class="btn btn-info" id="password-submit-btn" onClick="processAjaxForm(this, function() { $(\'.modal\').modal(\'hide\'); $(\'.modal-backdrop\').remove(); loadFiles(' . (int) $folder->id . '); }); return false;">' . t('unlock', 'Unlock') . ' <i class="entypo-check"></i></button>';
                $returnJson['html'] .= '</div></form></div></div></div>';
                $returnJson['javascript'] = "jQuery('.albumPasswordModel').modal('show');";
                $returnJson['page_title'] = $pageTitle;
                $returnJson['page_url'] = $pageUrl;
                echo json_encode($returnJson);
                exit;
            }
        }
    }
}
elseif($searchType == 'recent')
{
    $pageTitle = t('recent_files', 'Recent Files');
    $pageUrl = WEB_ROOT . '/index.html';
}
elseif($searchType == 'root')
{
    $pageTitle = t('file_manager', 'File Manager');
    $pageUrl = WEB_ROOT . '/index.html';
    $userOwnsFolder = true;
    $shareAccessLevel = 'all';
}
elseif($searchType == 'all')
{
    $pageTitle = t('all_files', 'All Files');
    $pageUrl = WEB_ROOT . '/index.html';
}
elseif($searchType == 'browserecent')
{
    if(strlen($searchTerm))
    {
        $pageTitle = t('file_search_results', 'File Search Results');
    }
    else
    {
        $pageTitle = t('recent_file_uploads', 'Recent File Uploads');
    }
}

$db = Database::getDatabase();
$filesClause = "WHERE 1=1 ";

$filesClause .= ' AND file.status = "'.$statusFilter.'"';
$foldersClause .= ' AND file_folder.status = "'.$statusFilter.'"';

if($filterImagesAll == false)
{
    $filesClause .= " AND (file.userId = " . (int) $Auth->id . " OR file.uploadedUserId = ".(int)$Auth->id;
    if($Auth->loggedIn())
    {
        // clause to add any shared files
        $filesClause .= ' OR ((file.folderId IN (SELECT folder_id FROM file_folder_share WHERE file_folder_share.shared_with_user_id = '.(int)$Auth->id.')) AND file.status = "'.$statusFilter.'")';
    }
    $filesClause .= ")";
}

// root folder listing
if($searchType == 'root')
{
    $filesClause .= " AND file.folderId IS NULL AND (file.userId = " . $Auth->id." OR file.uploadedUserId = ".(int)$Auth->id.")";
}

// all files for user
if($searchType == 'all')
{
    $filesClause .= " AND (file.userId = " . (int) $Auth->id . " OR file.uploadedUserId = ".(int)$Auth->id;
    if($Auth->loggedIn())
    {
        // clause to add any shared files
        $filesClause .= ' OR ((file.folderId IN (SELECT folder_id FROM file_folder_share WHERE file_folder_share.shared_with_user_id = '.(int)$Auth->id.')) AND file.status = "'.$statusFilter.'")';
    }
    $filesClause .= ")";
}

if(strlen($searchTerm))
{
    $filesClause .= ' AND (file.originalFilename LIKE "%' . $db->escape($searchTerm) . '%" OR file.shortUrl LIKE "%' . $db->escape($searchTerm) . '%")';
}

$fileSortColName = 'originalFilename';
$folderSortColName = 'folderName';
$sortDir = 'asc';
switch($_SESSION['search']['filterOrderBy'])
{
    case 'order_by_filename_asc':
        $fileSortColName = 'originalFilename';
        $folderSortColName = 'folderName';
        $sortDir = 'asc';
        break;
    case 'order_by_filename_desc':
        $fileSortColName = 'originalFilename';
        $folderSortColName = 'folderName';
        $sortDir = 'desc';
        break;
    case 'order_by_uploaded_date_asc':
    case '':
        $fileSortColName = 'uploadedDate';
        $folderSortColName = 'IFNULL(date_updated, date_added)';
        $sortDir = 'asc';
        break;
    case 'order_by_uploaded_date_desc':
        $fileSortColName = 'uploadedDate';
        $folderSortColName = 'IFNULL(date_updated, date_added)';
        $sortDir = 'desc';
        break;
    case 'order_by_downloads_asc':
        $fileSortColName = 'visits';
        $sortDir = 'asc';
        break;
    case 'order_by_downloads_desc':
        $fileSortColName = 'visits';
        $sortDir = 'desc';
        break;
    case 'order_by_filesize_asc':
        $fileSortColName = 'fileSize';
        $folderSortColName = 'totalSize';
        $sortDir = 'asc';
        break;
    case 'order_by_filesize_desc':
        $fileSortColName = 'fileSize';
        $folderSortColName = 'totalSize';
        $sortDir = 'desc';
        break;
    case 'order_by_last_access_date_asc':
        $fileSortColName = 'lastAccessed';
        $folderSortColName = 'IFNULL(date_updated, date_added)';
        $sortDir = 'asc';
        break;
    case 'order_by_last_access_date_desc':
        $fileSortColName = 'lastAccessed';
        $folderSortColName = 'IFNULL(date_updated, date_added)';
        $sortDir = 'desc';
        break;
}

// trash can
if($searchType == 'trash')
{
    $filesClause .= " AND (file.userId = " . (int) $Auth->id." OR file.uploadedUserId = ".$Auth->id.") AND file.folderId IS NULL";
    $foldersClause .= ' AND file_folder.parentId IS NULL';
    $pageTitle = t('trash_can', 'Trash Can');
}

// folder listing
if($searchType == 'folder')
{
    $filesClause .= " AND file.folderId = " . (int) $nodeId;
}
elseif($searchType == 'likes')
{
    $filesClause .= " AND file.id IN(SELECT file_id FROM plugin_filepreviewer_image_like WHERE user_id = " . (int) $userId . ")";
}

// paging js
$pagingJs = "loadImages('" . $nodeId . "', ";
$updatePagingJs = 'updatePerPage(';
$updateSortingJs = 'updateSorting(';
if($searchType == 'browserecent')
{
    $pagingJs = "loadBrowsePageRecentImages('" . str_replace(array('"', '\'', '\\'), '', $searchTerm) . "', ";
    $updatePagingJs = 'updateRecentImagesPerPage(';
}

// for recent uploads
if($searchType == 'recent')
{
    $fileSortColName = 'originalFilename';
    $sortDir = 'desc';
    $filesClause .= " AND (file.userId = " . (int) $Auth->id." OR file.uploadedUserId = ".(int)$Auth->id;
    
    if($Auth->loggedIn())
    {
        // clause to add any shared files
        $filesClause .= ' OR ((file.folderId IN (SELECT folder_id FROM file_folder_share WHERE file_folder_share.shared_with_user_id = '.(int)$Auth->id.')) AND file.status = "'.$statusFilter.'")';
    }
    $filesClause .= ")";
}

// filter by date range
if($filterUploadedDateRange !== null)
{
    // validate date
    $expDate = explode('|', $filterUploadedDateRange);
    if(COUNT($expDate) == 2)
    {
        $startDate = $expDate[0];
        $endDate = $expDate[1];
    }
    else
    {
        $startDate = $expDate[0];
        $endDate = $expDate[0];
    }

    if((validation::validDate($startDate, 'Y-m-d')) && (validation::validDate($endDate, 'Y-m-d')))
    {
        // dates are valid
        $filesClause .= " AND (UNIX_TIMESTAMP(file.uploadedDate) >= " . coreFunctions::convertDateToTimestamp($startDate, 'Y-m-d') . " AND UNIX_TIMESTAMP(file.uploadedDate) <= " . (coreFunctions::convertDateToTimestamp($endDate, 'Y-m-d') + (60 * 60 * 24) - 1) . ")";
    }
}

if($searchType == 'browserecent')
{
    $filesClause .= " AND (file.userId = " . (int) $Auth->id . " OR file.uploadedUserId = ".(int)$Auth->id;
    if($Auth->loggedIn())
    {
        // clause to add any shared files
        $filesClause .= ' OR ((file.folderId IN (SELECT folder_id FROM file_folder_share WHERE file_folder_share.shared_with_user_id = '.(int)$Auth->id.')) AND file.status = "'.$statusFilter.'")';
    }
    $filesClause .= ")";
}

// get file total for this account and filter
$allStats = $db->getRow('SELECT COUNT(file.id) AS totalFileCount, SUM(file.fileSize) AS totalFileSize FROM file LEFT JOIN file_folder ON file.folderId = file_folder.id ' . $filesClause);
$allStatsFolders = $db->getRow("SELECT COUNT(DISTINCT file_folder.id) AS totalFolderCount, file_folder_share.shared_with_user_id, file_folder_share.share_permission_level, IF(file_folder_share.shared_with_user_id IS NULL, 0, 1) AS shared_access FROM file_folder LEFT JOIN file_folder_share ON file_folder.id = file_folder_share.folder_id ".$foldersClause);

// load folders
$sQL = 'SELECT DISTINCT file_folder.id, file_folder.status, file_folder.userId, '
        . 'file_folder.parentId, file_folder.folderName, file_folder.isPublic, '
        . 'file_folder.coverImageId, file_folder.totalSize, file_folder.date_updated, '
        . 'file_folder.date_added, '
        . '(SELECT COUNT(file.id) AS fileCount FROM file WHERE file.folderId = file_folder.id AND status = "'.$statusFilter.'") AS fileCount, '
        . 'file_folder_share.shared_with_user_id, file_folder_share.share_permission_level, '
        . 'IF(file_folder_share.shared_with_user_id IS NULL, 0, 1) AS shared_access '
        . 'FROM file_folder '
        . 'LEFT JOIN file_folder_share '
        . 'ON file_folder.id = file_folder_share.folder_id '. $foldersClause .' '
        . 'GROUP BY file_folder.id '
        . 'ORDER BY ' . $folderSortColName . ' ' . $sortDir . ' '
        . 'LIMIT '.(($pageStart - 1) * (int)$_SESSION['search']['perPage']).', '.(int)$_SESSION['search']['perPage'];
$folders = $db->getRows($sQL);

// allow for folders in paging
$newStart = floor((($pageStart - 1) * (int) $_SESSION['search']['perPage']) - $allStatsFolders['totalFolderCount']);
if($newStart < 0)
{
    $newStart = 0;
}
$newLimit = $_SESSION['search']['perPage'] - COUNT($folders);
$limit = ' LIMIT ' . $newStart . ',' . $newLimit;

// load limited page filtered
$files = $db->getRows('SELECT file.*, plugin_filepreviewer_meta.width, plugin_filepreviewer_meta.height, users.username '
        . 'FROM file '
        . 'LEFT JOIN file_folder ON file.folderId = file_folder.id '
        . 'LEFT JOIN plugin_filepreviewer_meta ON file.id = plugin_filepreviewer_meta.file_id '
        . 'LEFT JOIN users ON file.uploadedUserId = users.id '
        . $filesClause . ' '
        . 'ORDER BY ' . $fileSortColName . ' ' . $sortDir . ' ' . $limit);

// breadcrumbs
$totalText = '';
if((int) $allStats['totalFileCount'] > 0)
{
    $totalText = ' - ' . (int) $allStats['totalFileCount'] . ' files' . ((int) $allStats['totalFileCount'] > 0 ? (' (' . coreFunctions::formatSize($allStats['totalFileSize']) . ')') : '');
}

$breadcrumbs = array();
if($Auth->loggedIn())
{
    $breadcrumbs[] = '<a href="#" onClick="loadImages(-1, 1); return false;" class="btn btn-white mid-item"><i class="glyphicon glyphicon-home"></i></a>';
}
else
{
    $breadcrumbs[] = '<a href="#" class="btn btn-white mid-item"><i class="glyphicon glyphicon-folder-open"></i></a>';
}

if($statusFilter == 'trash')
{
    $breadcrumbs[] = '<a href="#" onClick="loadImages(\'trash\', 1); return false;" class="btn btn-white">'.t('trash_can', 'Trash Can').'</a>';
}

if($searchType == 'browserecent')
{
    if(strlen($searchTerm))
    {
        $breadcrumbs[] = '<a href="#" onClick="loadBrowsePageRecentImages(\'' . str_replace(array('"', '\'', '\\'), '', $searchTerm) . '\'); return false;" class="btn btn-white mid-item">' . validation::safeOutputToScreen($pageTitle) . $totalText . '</a>';
    }
    else
    {
        $breadcrumbs[] = '<a href="#" onClick="loadBrowsePageRecentImages(); return false;" class="btn btn-white mid-item">' . validation::safeOutputToScreen($pageTitle) . $totalText . '</a>';
    }
}
elseif($searchType == 'folder')
{
    $dropdownMenu = '';
    if((in_array($shareAccessLevel, array('upload_download', 'all')) && ($folder != null)) && ($statusFilter != 'trash'))
    {
        $dropdownMenu .= '<ul role="menu" class="dropdown-menu dropdown-white pull-left">	<li>';
        if(UserPeer::getAllowedToUpload() == true)
        {
            $dropdownMenu .= '	<li><a href="#" onClick="uploadFiles(' . (int) $folder->id . ');"><span class="context-menu-icon"><span class="glyphicon glyphicon-cloud-upload"></span></span>' . t('upload_files', 'Upload Files') . '</a></li>';
            $dropdownMenu .= '	<li class="divider"></li>';
        }
        
        if(in_array($shareAccessLevel, array('all')))
        {
            $dropdownMenu .= '	<li><a href="#" onClick="showAddFolderForm(' . (int) $folder->id . ');"><span class="context-menu-icon"><span class="glyphicon glyphicon-plus"></span></span>' . t('add_sub_folder', 'Add Sub Folder') . '</a></li>';
            $dropdownMenu .= '	<li><a href="#" onClick="showAddFolderForm(null, ' . (int) $folder->id . ');"><span class="context-menu-icon"><span class="glyphicon glyphicon-pencil"></span></span>' . t('edit_folder', 'Edit') . '</a></li>';
            $dropdownMenu .= '	<li><a href="#" onClick="confirmTrashFolder(' . (int) $folder->id . ');"><span class="context-menu-icon"><span class="glyphicon glyphicon-trash"></span></span>' . t('delete_folder', 'Delete') . '</a></li>';
            $dropdownMenu .= '	<li class="divider"></li>';
        }
        
        if(in_array($shareAccessLevel, array('all', 'upload_download')))
        {
            $dropdownMenu .= '	<li><a href="#" onClick="downloadAllFilesFromFolder(' . (int) $folder->id . ');"><span class="context-menu-icon"><span class="glyphicon glyphicon-floppy-save"></span></span>' . t('download_all_files', 'Download All Files (Zip)') . '</a></li>';
        }
        
        if(in_array($shareAccessLevel, array('all')))
        {
            $dropdownMenu .= '	<li class="divider"></li>';
            $dropdownMenu .= '	<li><a href="#" onClick="selectAllItems();"><span class="context-menu-icon"><span class="glyphicon glyphicon-check"></span></span>' . t('account_file_details_select_all_items', 'Select All Items') . '</a></li>';
            $dropdownMenu .= '	<li><a href="#" onClick="sharePublicAlbum(' . (int) $folder->id . ');"><span class="context-menu-icon"><span class="glyphicon glyphicon-share"></span></span>' . t('share_folder', 'Share Folder') . '</a></li>';
        }
        $dropdownMenu .= '</ul>';
    }

    $localFolder = $folder;
    $localBreadcrumbs = array();
    $first = true;
    while($localFolder != false)
    {
        // for non logged in users, don't indicate a drop-down menu on the breadcrumbs
        if(!in_array($shareAccessLevel, array('upload_download', 'all')))
        {
            if(($userOwnsFolder !== true) || ($folder == null))
            {
                $first = false;
            }
        }

        $parentId = $localFolder->parentId;
        $localBreadcrumbs[] = '<a href="#" ' . (($first == true && strlen($dropdownMenu)) ? ' data-toggle="dropdown"' : 'onClick="loadImages(' . (int) $localFolder->id . ', 1); return false;"') . ' class="btn btn-white' . (($first == true && strlen($dropdownMenu)) ? ' mid-item' : '') . '">' . validation::safeOutputToScreen($localFolder->folderName) . (($first == true && strlen($dropdownMenu)) ? ($totalText . '&nbsp;&nbsp;<i class="caret"></i>') : '') . '</a>' . (($first == true && strlen($dropdownMenu)) ? $dropdownMenu : '');
        $first = false;
        $localFolder = fileFolder::loadById($parentId);
    }

    // change direction of breadcrumbs and add globally
    $breadcrumbs = array_merge($breadcrumbs, array_reverse($localBreadcrumbs));

    // add on 'add folder' plus icon
    if(strlen($dropdownMenu)) {
        $breadcrumbs[] = '<a class="add-sub-folder-plus-btn" href="#" onClick="showAddFolderForm(' . (int) $folder->id . '); return false;" title="" data-original-title="' . t('add_sub_folder', 'Add Sub Folder') . '" data-placement="bottom" data-toggle="tooltip"><i class="glyphicon glyphicon-plus-sign"></i></a>';
    }
}
elseif($searchType == 'root')
{
    $dropdownMenu = '<ul role="menu" class="dropdown-menu dropdown-white pull-left">	<li>';
    if(UserPeer::getAllowedToUpload() == true)
    {
        $dropdownMenu .= '<a href="#" onClick="uploadFiles(\'\');"><span class="context-menu-icon"><span class="glyphicon glyphicon-cloud-upload"></span></span>' . t('upload_files', 'Upload Files') . '</a></li>	<li class="divider"></li>	';
    }
    $dropdownMenu .= '<li><a href="#" onClick="showAddFolderForm(-1);"><span class="context-menu-icon"><span class="glyphicon glyphicon-plus"></span></span>' . t('add_folder', 'Add Folder') . '</a></li>	<li class="divider"></li>	<li><a href="#" onClick="selectAllItems();"><span class="context-menu-icon"><span class="glyphicon glyphicon-check"></span></span>' . t('account_file_details_select_all_items', 'Select All Items') . '</a></li></ul>';
    $breadcrumbs[] = '<a href="#" data-toggle="dropdown" class="btn btn-white">Root Folder' . $totalText . '&nbsp;&nbsp;<i class="caret"></i></a>' . $dropdownMenu;

    // add on 'add folder' plus icon
    $breadcrumbs[] = '<a class="add-sub-folder-plus-btn" href="#" onClick="showAddFolderForm(' . (int) $folder->id . '); return false;" title="" data-original-title="' . t('add_sub_folder', 'Add Sub Folder') . '" data-placement="bottom" data-toggle="tooltip"><i class="glyphicon glyphicon-plus-sign"></i></a>';
}
elseif($statusFilter != 'trash')
{
    $breadcrumbs[] = '<a href="#" onClick="' . $pagingJs . '1); return false;" class="btn btn-white">' . validation::safeOutputToScreen($pageTitle) . $totalText . '</a>';
}

$returnJson['html'] .= '<div class="image-browse">';

$returnJson['html'] .= '<div id="fileManager" class="fileManager ' . validation::safeOutputToScreen($_SESSION['browse']['viewType']) . '">';
if(($files) || ($folders))
{
    $returnJson['html'] .= '<div class="toolbar-container">
		<!-- toolbar -->
		<div class="col-md-6 clearfix">
			<!-- breadcrumbs -->
			<div class="row breadcrumbs-container">
				<div class="col-md-12 col-sm-12 clearfix">
					<ol id="folderBreadcrumbs" class="btn-group btn-breadcrumb">' . implode('', $breadcrumbs) . '</ol>
				</div>
			</div></div>';

    $returnJson['html'] .= '
		<div class="col-md-6 clearfix right-toolbar-options">
			<div class="list-inline pull-right">
				<div class="btn-toolbar pull-right" role="toolbar">';

    if(($userOwnsFolder !== true) && ((int) $folder->showDownloadLinks == 1))
    {
        if(!in_array($shareAccessLevel, array('view')))
        {
            $returnJson['html'] .= '<div class="btn-group hidden-xs">';
            $returnJson['html'] .= '	<button class="btn btn-white" type="button" title="" data-original-title="Download All" data-placement="bottom" data-toggle="tooltip" onclick="downloadAllFilesFromFolderShared(' . (int) $folder->id . '); return false;"><i class="glyphicon glyphicon-floppy-save"></i></button>
                                    </div>';
        }
    }
    
    // append empty trash option
    if($statusFilter == 'trash') {
        $returnJson['html'] .= '<div class="btn-group hidden-xs">';
        $returnJson['html'] .= '<button class="btn btn-white" type="button" title="" data-original-title="Empty Trash" data-placement="bottom" data-toggle="tooltip" onclick="confirmEmptyTrash(); return false;">Empty Trash <i class="glyphicon glyphicon-trash"></i></button>';
        $returnJson['html'] .= '</div>';
    }

    if($statusFilter == 'active') {
        $returnJson['html'] .= '<div class="btn-group hidden-xs">';
        if($userOwnsFolder === true)
        {
            $returnJson['html'] .= '<button class="btn btn-white disabled fileActionLinks" type="button" title="" data-original-title="Links" data-placement="bottom" data-toggle="tooltip" onclick="viewFileLinks();
                                                                    return false;"><i class="entypo-link"></i></button>';
            $returnJson['html'] .= '<button class="btn btn-white disabled fileActionLinks" type="button" title="" data-original-title="Delete" data-placement="bottom" data-toggle="tooltip" onclick="trashFiles();
                                                                    return false;"><i class="entypo-cancel"></i></button>';
        }
        $returnJson['html'] .= '<button class="btn btn-white" type="button" title="" data-original-title="List View" data-placement="bottom" data-toggle="tooltip" onclick="toggleViewType();
                                                                    return false;" id="viewTypeText"><i class="entypo-list"></i></button>
                                                    <button class="btn btn-white" type="button" title="" data-original-title="Fullscreen" data-placement="bottom" data-toggle="tooltip" onclick="toggleFullScreenMode();
                                                                    return false;"><i class="entypo-resize-full"></i></button>
                            </div>';
    }

    $returnJson['html'] .= '<div class="btn-group">';
    if($searchType != 'browserecent')
    {
        $returnJson['html'] .= '<div class="btn-group">
                                                                <button id="filterButton" data-toggle="dropdown" class="btn btn-white dropdown-toggle" type="button">
                                                                        ' . validation::safeOutputToScreen($sortingOptions{$_SESSION['search']['filterOrderBy']}) . ' <i class="entypo-arrow-combo"></i>
                                                                </button>
                                                                <ul role="menu" class="dropdown-menu dropdown-white pull-right">
                                                                        <li class="disabled"><a href="#">Sort By</a></li>';
        foreach($sortingOptions AS $k => $v)
        {
            $returnJson['html'] .= '<li><a href="#" onclick="' . $updateSortingJs . '\'' . $k . '\', \'' . $v . '\', this); return false;">' . $v . '</a></li>';
        }
        $returnJson['html'] .= '</ul>
                                                                <input name="filterOrderBy" id="filterOrderBy" value="' . validation::safeOutputToScreen($_SESSION['search']['filterOrderBy']) . '" type="hidden">
                                                        </div>';
    }

    $returnJson['html'] .= '<div class="btn-group">
							<button id="perPageButton" data-toggle="dropdown" class="btn btn-white dropdown-toggle" type="button">
								' . (int) $_SESSION['search']['perPage'] . ' <i class="entypo-arrow-combo"></i>
							</button>
							<ul role="menu" class="dropdown-menu dropdown-white pull-right per-page-menu">
								<li class="disabled"><a href="#">Per Page:</a></li>';
    foreach($perPageOptions AS $perPageOption)
    {
        $returnJson['html'] .= '<li><a href="#" onclick="' . $updatePagingJs . '\'' . (int) $perPageOption . '\', \'' . (int) $perPageOption . '\', this); return false;">' . (int) $perPageOption . '</a></li>';
    }
    $returnJson['html'] .= '</ul>
							<input name="perPageElement" id="perPageElement" value="100" type="hidden">
						</div>
					</div>
				</div>
				<ol id="folderBreadcrumbs2" class="breadcrumb bc-3 pull-right">
					<li class="active">
						<span id="statusText"></span>
					</li>
				</ol>
			</div>
		</div>';

    $returnJson['html'] .= '
		<!-- /.navbar-collapse -->
	</div>';

    $returnJson['html'] .= '<div class="gallery-env"><div class="fileListing" id="fileListing">';

    // some presets
    $thumbnailWidth = 160;
    $thumbnailHeight = 134;
    $counter = 1;

    // output folders
    if($folders)
    {
        foreach($folders AS $folder)
        {
            // skip if user does not own the folder and the folders are not public
            if(($userOwnsFolder === false) && ((int) $folder['isPublic'] == 0))
            {
                // make sure this isn't a shared folder
                if((int)$folder['shared_access'] == 0)
                {
                    continue;
                }
            }

            // hydrate folder
            $folderObj = fileFolder::hydrate($folder);
            $folderLabel = $folder['folderName'];

            // check folder ownership
            $ownedByCurrentUser = false;
            if(($Auth->loggedIn() == true) && ($Auth->id == $folderObj->userId) && ((int) $folderObj->userId > 0))
            {
                $ownedByCurrentUser = true;
            }

            // prepare cover image
            $coverData = $folderObj->getCoverData();
            $coverId = (int) $coverData['file_id'];
            $coverUniqueHash = $coverData['unique_hash'];

            $returnJson['html'] .= '<div id="folderItem' . (int) $folderObj->id . '" data-clipboard-action="copy" data-clipboard-target="#clipboard-placeholder" class="fileItem folderIconLi folderItem' . (int) $folderObj->id . ' ' . ($folder['status'] != 'active' ? 'folderDeletedLi' : '') . ' fileIconLi col-xs-4 image-thumb ' . ($ownedByCurrentUser == true ? 'owned-folder' : 'not-owned-folder') . '" onClick="loadImages(' . (int) $folderObj->id . '); return false;" folderId="' . (int) $folderObj->id . '" sharing-url="' . $folderObj->getFolderUrl() . '">';

            $returnJson['html'] .= '<div class="thumbIcon">';
            $returnJson['html'] .= '<a name="link">';
            if($ownedByCurrentUser == false)
            {
                $returnJson['html'] .= '<img src="' . SITE_IMAGE_PATH . '/folder_share_fm_grid.png" />';
            }
            elseif($folder['fileCount'] == 0 && $folder['isPublic'] == 1)
            {
                $returnJson['html'] .= '<img src="' . SITE_IMAGE_PATH . '/folder_fm_grid.png" />';
            }
            elseif($folder['fileCount'] > 0 && $folder['isPublic'] == 1)
            {
                $returnJson['html'] .= '<img src="' . SITE_IMAGE_PATH . '/folder_full_fm_grid.png" />';
            }
            elseif($folder['fileCount'] >= 0 && $folder['isPublic'] == 0)
            {
                $returnJson['html'] .= '<img src="' . SITE_IMAGE_PATH . '/folder_lock_fm_grid.png" />';
            }
            else
            {
                $returnJson['html'] .= '<img src="' . SITE_IMAGE_PATH . '/folder_full_fm_grid.png" />';
            }
            $returnJson['html'] .= '</a>';
            $returnJson['html'] .= '</div>';

            $returnJson['html'] .= '<span class="filesize">' . validation::safeOutputToScreen(coreFunctions::formatSize($folder['totalSize'])) . '</span>';
            $returnJson['html'] .= '<span class="fileUploadDate">' . validation::safeOutputToScreen(coreFunctions::formatDate($folder['date_updated']!=null?$folder['date_updated']:$folder['date_added'])) . '</span>';
            $returnJson['html'] .= '<span class="fileOwner">' . ($folder['fileCount'] > 0 ? $folder['fileCount'] . " " . ($folder['fileCount'] == 1 ? strtolower(t('file', 'file')) : strtolower(t('files', 'files'))) : "-") . '</span>';
            $returnJson['html'] .= '<span class="thumbList">';
            $returnJson['html'] .= '<a name="link">';
            if($folder['fileCount'] == 0 && $folder['isPublic'] == 1)
            {
                $returnJson['html'] .= '<img src="' . SITE_IMAGE_PATH . '/folder_fm_list.png" />';
            }
            elseif($folder['fileCount'] > 0 && $folder['isPublic'] == 1)
            {
                $returnJson['html'] .= '<img src="' . SITE_IMAGE_PATH . '/folder_full_fm_list.png" />';
            }
            elseif($folder['fileCount'] >= 0 && $folder['isPublic'] == 0)
            {
                $returnJson['html'] .= '<img src="' . SITE_IMAGE_PATH . '/folder_lock_fm_list.png" />';
            }
            else
            {
                $returnJson['html'] .= '<img src="' . SITE_IMAGE_PATH . '/folder_full_fm_list.png" />';
            }
            $returnJson['html'] .= '</a>';
            $returnJson['html'] .= '</span>';

            $returnJson['html'] .= '<span class="filename">' . validation::safeOutputToScreen($folderLabel) . '</span>';

            // menu link
            if($ownedByCurrentUser == true)
            {
                $returnJson['html'] .= '  <div class="fileOptions">';
                $returnJson['html'] .= '      <a class="fileDownload" href="#"><i class="caret"></i></a>';
                $returnJson['html'] .= '  </div>';
            }

            $returnJson['html'] .= '</div>';

            $counter++;
        }
    }

    // output files
    if($files)
    {
        foreach($files AS $file)
        {
            // get file object
            $fileObj = file::hydrate($file);

            // check image ownership
            $ownedByCurrentUser = false;
            if(($Auth->loggedIn() == true) && ($Auth->id == $fileObj->userId || $Auth->id == $fileObj->uploadedUserId) && ((int) $fileObj->userId > 0))
            {
                $ownedByCurrentUser = true;
            }

            $sizingMethod = 'middle';
            if($thumbnailType == 'full')
            {
                $sizingMethod = 'cropped';
            }
            $previewImageUrlLarge = file::getIconPreviewImageUrl($file, false, 48, false, $thumbnailWidth, $thumbnailHeight, $sizingMethod);
            $previewImageUrlMedium = file::getIconPreviewImageUrlMedium($file);

            $extraMenuItems = array();
            $menuItemsStr = '';
            if(COUNT($extraMenuItems))
            {
                $menuItemsStr = json_encode($extraMenuItems);
            }

            $returnJson['html'] .= '<div dttitle="' . validation::safeOutputToScreen($file['originalFilename']) . '" dtsizeraw="' . validation::safeOutputToScreen($file['fileSize']) . '" dtuploaddate="' . validation::safeOutputToScreen(coreFunctions::formatDate($file['uploadedDate'])) . '" dtfullurl="' . validation::safeOutputToScreen($fileObj->getFullShortUrl()) . '" dtfilename="' . validation::safeOutputToScreen($file['originalFilename']) . '" dtstatsurl="' . validation::safeOutputToScreen($fileObj->getStatisticsUrl()) . '" dturlhtmlcode="' . validation::safeOutputToScreen($fileObj->getHtmlLinkCode()) . '" dturlbbcode="' . validation::safeOutputToScreen($fileObj->getForumLinkCode()) . '" dtextramenuitems="' . validation::safeOutputToScreen($menuItemsStr) . '" title="' . validation::safeOutputToScreen($file['originalFilename']) . ' (' . validation::safeOutputToScreen(coreFunctions::formatSize($file['fileSize'])) . ')" fileId="' . $file['id'] . '" class="col-xs-4 image-thumb image-thumb-' . $sizingMethod . ' fileItem' . $file['id'] . ' fileIconLi ' . ($file['status'] != 'active' ? 'fileDeletedLi' : '') . ' ' . ($ownedByCurrentUser == true ? 'owned-image' : 'not-owned-image') . '">';

            $returnJson['html'] .= '<div class="thumbIcon">';
            $returnJson['html'] .= '<a name="link"><img src="' . ((substr($previewImageUrlLarge, 0, 4) == 'http') ? $previewImageUrlLarge : (SITE_IMAGE_PATH . '/trans_1x1.gif')) . '" alt="" class="' . ((substr($previewImageUrlLarge, 0, 4) != 'http') ? $previewImageUrlLarge : '#') . '" style="max-width: 100%; max-height: 100%; min-width: 30px; min-height: 30px;"></a>';
            $returnJson['html'] .= '</div>';

            $returnJson['html'] .= '<span class="filesize">' . validation::safeOutputToScreen(coreFunctions::formatSize($file['fileSize'])) . '</span>';
            $returnJson['html'] .= '<span class="fileUploadDate">' . validation::safeOutputToScreen(coreFunctions::formatDate($file['uploadedDate'])) . '</span>';
            $returnJson['html'] .= '<span class="fileOwner">' . validation::safeOutputToScreen($file['username']) . '</span>';
            $returnJson['html'] .= '<span class="thumbList">';
            $returnJson['html'] .= '<a name="link"><img src="' . $previewImageUrlMedium . '" alt=""></a>';
            $returnJson['html'] .= '</span>';

            $returnJson['html'] .= '<span class="filename">' . validation::safeOutputToScreen($file['originalFilename']) . '</span>';

            // menu link
            if($ownedByCurrentUser == true)
            {
                $returnJson['html'] .= '  <div class="fileOptions">';
                $returnJson['html'] .= '      <a class="fileDownload" href="#"><i class="caret"></i></a>';
                $returnJson['html'] .= '  </div>';
            }

            $returnJson['html'] .= '</div>';
            $counter++;
        }
    }
    $returnJson['html'] .= '</div>';
    $returnJson['html'] .= '</div>';

    // paging
    $currentPage = $pageStart;
    $totalPages = ceil((int) $allStats['totalFileCount'] / (int) $_SESSION['search']['perPage']);
    $returnJson['html'] .= '<div class="paginationRow">';
    $returnJson['html'] .= '	<div id="pagination" class="paginationWrapper col-md-12 responsiveAlign">';
    $returnJson['html'] .= '		<ul class="pagination">';
    $returnJson['html'] .= '			<li class="' . ($currentPage == 1 ? 'disabled' : '') . '"><a href="#" onClick="' . ($currentPage > 1 ? $pagingJs . '1);' : '') . ' return false;"><i class="entypo-to-start"></i><span>' . UCWords(t('first', 'first')) . '</span></a></li>';
    $returnJson['html'] .= '			<li class="' . ($currentPage == 1 ? 'disabled' : '') . '"><a href="#" onClick="' . ($currentPage > 1 ? $pagingJs . '' . ((int) $currentPage - 1) . ');' : '') . ' return false;"><i class="entypo-left-dir"></i> <span>' . UCWords(t('previous', 'previous')) . '</span></a></li>';

    // calculate numbers before and after
    $startPager = $currentPage - 3;
    if($startPager < 1)
    {
        $startPager = 1;
    }

    for($i = 0; $i <= 8; $i++)
    {
        $currentPager = $startPager + $i;
        if($currentPager > $totalPages)
        {
            continue;
        }
        $returnJson['html'] .= '		<li class="' . (($currentPager == $currentPage) ? 'active' : '') . '"><a href="#" onclick="' . $pagingJs . '' . (int) $currentPager . '); return false;">' . (int) $currentPager . '</a></li>';
    }

    $returnJson['html'] .= '			<li class="' . ($currentPage == $totalPages ? 'disabled' : '') . '"><a href="#" onClick="' . ($currentPage != $totalPages ? $pagingJs . '' . ((int) $currentPage + 1) . ');' : '') . ' return false;"><span>' . UCWords(t('next', 'next')) . '</span> <i class="entypo-right-dir"></i></a></li>';
    $returnJson['html'] .= '			<li class="' . ($currentPage == $totalPages ? 'disabled' : '') . '"><a href="#" onClick="' . ($currentPage != $totalPages ? $pagingJs . '' . ((int) $totalPages) . ');' : '') . ' return false;"><span>' . UCWords(t('last', 'last')) . '</span> <i class="entypo-to-end"></i></a></li>';
    $returnJson['html'] .= '		</ul>';
    $returnJson['html'] .= '	</div>';

    $returnJson['html'] .= '</div>';
}
else
{
    $returnJson['html'] .= '<div class="toolbar-container">
		<!-- toolbar -->
		<div class="col-md-6 col-sm-8 clearfix">
			<!-- breadcrumbs -->
			<div class="row breadcrumbs-container">
				<div class="col-md-12 col-sm-12 clearfix">
					<ol id="folderBreadcrumbs" class="btn-group btn-breadcrumb">' . implode('', $breadcrumbs) . '</ol>
				</div>
			</div>
		</div>
	</div>';

    $returnJson['html'] .= '<div class="no-results-wrapper">';
    if(($searchType == 'folder') || ($searchType == 'root'))
    {
        if($Auth->loggedIn())
        {
            $html = '';
            $html .= '<div class="no-files-upload-wrapper" onClick="uploadFiles(' . (int) $folder->id . ', true); return false;">';
            $html .= '<img src="' . SITE_IMAGE_PATH . '/modal_icons/upload-computer-icon.png" class="upload-icon-image"/>';
            $html .= '<div class="clear"><!-- --></div>';
            if(Stats::currentBrowserIsIE()):
                $html .= t('no_files_found_in_this_folder', 'No files found within this folder. Click here to upload');
            else:
                $html .= t('no_files_found_in_this_folder_drag_and_drop', 'Drag & drop files or click here to upload');
            endif;
            $html .= '</div>';
            $returnJson['html'] .= $html;
        }
        else
        {
            $returnJson['html'] .= '<div class="alert alert-warning"><i class="entypo-attention"></i> ' . t('no_files_found_in_folder', 'No files found within this folder.') . '</div>';
        }
    }
    else
    {
        $returnJson['html'] .= '<div class="alert alert-warning"><i class="entypo-attention"></i> ' . t('no_files_found_in_search', 'No files found within folder or search criteria.') . '</div>';
    }
    $returnJson['html'] .= '</div>';
}

// stats
$returnJson['html'] .= '<input id="rspFolderTotalFiles" value="' . (int) $allStats['totalFileCount'] . '" type="hidden"/>';
$returnJson['html'] .= '<input id="rspFolderTotalSize" value="' . $allStats['totalFileSize'] . '" type="hidden"/>';
$returnJson['html'] .= '<input id="rspTotalPerPage" value="' . (int) $_SESSION['search']['perPage'] . '" type="hidden"/>';
$returnJson['html'] .= '<input id="rspTotalResults" value="' . (int) $allStats['totalFileCount'] . '" type="hidden"/>';
$returnJson['html'] .= '<input id="rspCurrentStart" value="' . (int) $pageStart . '" type="hidden"/>';
$returnJson['html'] .= '<input id="rspCurrentPage" value="' . ceil(((int) $pageStart + (int) $_SESSION['search']['perPage']) / (int) $_SESSION['search']['perPage']) . '" type="hidden"/>';
$returnJson['html'] .= '<input id="rspTotalPages" value="' . ceil((int) $allStats['totalFileCount'] / (int) $_SESSION['search']['perPage']) . '" type="hidden"/>';
$returnJson['html'] .= '<input id="rspShareAccessLevel" value="' . $shareAccessLevel . '" type="hidden"/>';

$returnJson['html'] .= '</div>';
$returnJson['html'] .= '</div>';

$returnJson['page_title'] = $pageTitle;
$returnJson['page_url'] = $pageUrl;

// output response
echo json_encode($returnJson);
