<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

// pick up the user name and lookup the user id
$import_account = trim($_REQUEST['import_account']);
if (strlen($import_account) == 0) {
    // no user passed
    echo '<select id="import_folder" name="import_folder" class="form-control col-md-7 col-xs-12" disabled="disabled">';
    echo '  <option value="">- please select a user account to load their folder listing -</option>';
    echo '</select>';
    exit;
}

$userAccount = UserPeer::loadUserByUsername($import_account);
if (!$userAccount) {
    echo '<select id="import_folder" name="import_folder" class="form-control col-md-7 col-xs-12" disabled="disabled">';
    echo '  <option value="">- failed loading user \'' . $import_account . '\' -</option>';
    echo '</select>';
    exit;
}

// load the users folders
$userFolders = fileFolder::loadAllActiveForSelect($userAccount->id);

// create and output the select
echo '<select id="import_folder" name="import_folder" class="form-control col-md-7 col-xs-12">';
echo '  <option value="">/</option>';
foreach ($userFolders AS $folderId => $userFolder) {
    echo '  <option value="' . (int) $folderId . '">/' . adminFunctions::makeSafe($userFolder) . '</option>';
}
echo '</select>';
