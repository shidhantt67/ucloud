<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

$fileId = (int) $_REQUEST['fileId'];
$statusId = (int) $_REQUEST['statusId'];
$adminNotes = isset($_REQUEST['adminNotes']) ? trim($_REQUEST['adminNotes']) : '';
$blockUploads = (int) $_REQUEST['blockUploads'];

// prepare result
$result = array();
$result['error'] = false;
$result['msg'] = '';

if (_CONFIG_DEMO_MODE == true) {
    $result['error'] = true;
    $result['msg'] = adminFunctions::t("no_changes_in_demo_mode");
}
else {
    // check for removal
    // @TODO - rework this as it's no longer relevant
    if (($statusId == 3) || ($statusId == 4)) {
        // load file
        $file = file::loadById($fileId);
        if (!$file) {
            $result['error'] = true;
            $result['msg'] = 'Could not locate the file.';
            echo json_encode($result);
            exit;
        }

        // remove
        $file->removeBySystem();
    }

    // block file if it's requested
    if ((int) $blockUploads == 1) {
        $file->blockFutureUploads();
    }

    $result['error'] = false;
    $result['msg'] = 'File \'' . $file->originalFilename . '\' removed.';
    $db->query('UPDATE file SET adminNotes = :adminNotes WHERE id = :id', array('adminNotes' => $adminNotes, 'id' => $fileId));
    if ($db->affectedRows() == 1) {
        if ((int) $blockUploads == 1) {
            $result['msg'] .= ' The file content hash was also added to the block list, so the same file can not be re-uploaded.';
        }
    }
}

echo json_encode($result);
exit;
