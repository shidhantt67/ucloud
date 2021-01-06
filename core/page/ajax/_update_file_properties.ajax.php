<?php

error_reporting(E_ALL | E_STRICT);

// setup includes
require_once('../../../core/includes/master.inc.php');

// receive varables
$fileProperties = $_REQUEST['fileProperties'];

// make sure we have some items
if (COUNT($fileProperties) == 0) {
    exit;
}

// loop items, load from the database and create email content/set password
$fullUrls = array();
foreach ($fileProperties AS $fileProperty) {
    // get delete hash and short url
    $shortUrl = $fileProperty['propertiesShortUrl'];
    $deleteHash = $fileProperty['propertiesDeleteHash'];
    if ((strlen($shortUrl) == 0) || (strlen($deleteHash) == 0)) {
        continue;
    }

    // load file
    $file = file::loadByShortUrl($shortUrl);
    if (!$file) {
        // failed lookup of file
        continue;
    }

    // make sure it matches the delete hash
    if ($file->deleteHash != $deleteHash) {
        continue;
    }

    // load other properties
    $title = trim($fileProperty['propertiesTitle']);
    $description = coreFunctions::cleanTextareaInput($fileProperty['propertiesDescription']);
    $keywords = coreFunctions::cleanTextareaInput($fileProperty['propertiesKeywords']);
    if (strlen($title) == 0) {
        $title = 'file';
    }

    // update file
    $rs = $db->query('UPDATE file SET originalFilename = :originalFilename, description = :description, keywords = :keywords WHERE id = :id', array(
        'originalFilename' => $title . '.' . $file->extension,
        'id' => $file->id,
        'description' => $description,
        'keywords' => $keywords,
    ));
}
