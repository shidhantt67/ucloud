<?php
// allow some time to run
set_time_limit(60 * 60 * 4);

// set max allowed total filesize, 1GB
define('MAX_PERMITTED_ZIP_FILE_BYTES', 1024 * 1024 * 1024 * 1);

// setup includes
require_once('../../../core/includes/master.inc.php');

// allow 1.2GB of memory to run
ini_set('memory_limit', '1200M');

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// some initial headers
header("HTTP/1.0 200 OK");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Pragma: no-cache");
?>
<style>
    html, body
    {
        margin:			0;
        padding:		5px;
    }
</style>
<link rel="stylesheet" href="<?php echo SITE_CSS_PATH; ?>/bootstrap.css" type="text/css" charset="utf-8" />
<?php
// setup initial params
$folderId = (int) $_REQUEST['folderId'];

// setup database
$db = Database::getDatabase(true);

// block root folder
if ($folderId == '-1') {
    echo t('account_home_can_not_download_root', 'Error: Can not download root folder as zip file, please select a sub folder.');
    exit;
}

// make sure user owns folder or has permissions to download from it
$folderData = $db->getRow('SELECT * FROM file_folder WHERE id = :folder_id AND (userId = :user_id OR id IN (SELECT folder_id FROM file_folder_share WHERE folder_id = :folder_id AND shared_with_user_id = :user_id AND share_permission_level IN ("all", "upload_download"))) LIMIT 1', array(
    'folder_id' => $folderId,
    'user_id' => $Auth->id,
        ));
if (!$folderData) {
    echo t('account_home_can_not_locate_folder', 'Error: Can not locate folder.');
    exit;
}

// check for zip class
if (!class_exists('ZipArchive')) {
    echo t('account_home_ziparchive_class_not_exists', 'Error: The ZipArchive class was not found within PHP. Please enable it within php.ini and try again.');
    exit;
}

// build folder and file tree
$fileData = zipFile::getFolderStructureAsArray($folderId, $folderId, $Auth->id);
$totalFileCount = zipFile::getTotalFileCount($fileData[$folderData{'folderName'}]);
$totalFilesize = zipFile::getTotalFileSize($fileData[$folderData{'folderName'}]);
$zipFilename = md5(serialize($fileData));

// error if no files
if ($totalFileCount == 0) {
    echo t('account_home_no_active_files_in_folder', 'Error: No active files in folder.');
    exit;
}

// check total filesize
if ($totalFilesize > MAX_PERMITTED_ZIP_FILE_BYTES) {
    echo t('account_home_too_many_files_size', 'Error: Selected files are greater than [[[MAX_FILESIZE]]] (total [[[TOTAL_SIZE_FORMATTED]]]). Can not create zip.', array('MAX_FILESIZE' => coreFunctions::formatSize(MAX_PERMITTED_ZIP_FILE_BYTES), 'TOTAL_SIZE_FORMATTED' => coreFunctions::formatSize($totalFilesize)));
    exit;
}

// setup output buffering
zipFile::outputInitialBuffer();

// create blank zip file
$zip = new zipFile($zipFilename);

// remove any old zip files
zipFile::cleanOldBatchDownloadZipFiles();

// output progress
zipFile::outputBufferToScreen('Found ' . $totalFileCount . ' file' . ($totalFileCount != 1 ? 's' : '') . '.');

// loop all files and download locally
foreach ($fileData AS $fileDataItem) {
    // add files
    $zip->addFilesTopZip($fileDataItem);

    // do folders
    if (COUNT($fileDataItem['folders'])) {
        print_r($fileDataItem);
    }
}

// output progress
zipFile::outputBufferToScreen('Saving zip file...', null, ' ');

// close zip
$zip->close();

// get path for later
$fullZipPathAndFilename = $zip->fullZipPathAndFilename;

// output progress
zipFile::outputBufferToScreen('Done!', 'green');
echo '<br/>';

// output link to zip file
$downloadZipName = $folderData['folderName'];
$downloadZipName = str_replace(' ', '_', $downloadZipName);
$downloadZipName = validation::removeInvalidCharacters($downloadZipName, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890_-0');

echo '<a class="btn btn-info" href="' . CORE_AJAX_WEB_ROOT . '/_account_home_v2_download_all_folder_files_zip.ajax.php?t=' . str_replace('.zip', '', $zipFilename) . '&n=' . urlencode($downloadZipName) . '" target="_parent">' . t('account_home_download_zip_file', 'Download Zip File') . '&nbsp;&nbsp;(' . coreFunctions::formatSize(filesize($fullZipPathAndFilename)) . ')</a>';
zipFile::scrollIframe();

echo '<br/><br/>';
zipFile::scrollIframe();
