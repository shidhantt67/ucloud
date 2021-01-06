<?php

// validation
$shortUrl = trim($_REQUEST['s']);
$f        = 0;
if (isset($_REQUEST['f']))
{
    $f = trim($_REQUEST['f']);
}

// try to load the file object
$file = null;
if ($shortUrl)
{
    $file = file::loadByShortUrl($shortUrl);
}

// load file details
if (!$file)
{
    // no file found
    coreFunctions::output404();
}

// file must be active
if ($file->status != 'active')
{
    coreFunctions::output404();
}

// check if file needs a password
$album = null;
if($Auth->id != $file->userId)
{
	if($file->folderId !== NULL)
	{
		$album = $file->getFolderData();
	}
	if(($album) && (strlen($album->accessPassword) > 0))
	{
		// see if we have it in the session already
		$askPassword = true;
		if(!isset($_SESSION['folderPassword']))
		{
			$_SESSION['folderPassword'] = array();
		}
		elseif(isset($_SESSION['folderPassword'][$album->id]))
		{
			if($_SESSION['folderPassword'][$album->id] == $album->accessPassword)
			{
				$askPassword = false;
			}
		}
		
		if($askPassword)
		{
			// redirect to main page which requests for a password
			coreFunctions::redirect(file::getFileUrl($file->id));
		}
	}
}

// check file permissions, allow owners, non user uploads and admin/mods
if($file->userId != null)
{
	if((($file->userId != $Auth->id) && ($Auth->level_id < 10)))
	{
		// if this is a private file
		if(coreFunctions::getOverallPublicStatus($file->userId, $file->folderId, $file->id) == false)
		{
			$errorMsg = t("error_file_is_not_publicly_shared", "File is not publicly available.");
			coreFunctions::redirect(coreFunctions::getCoreSitePath() . "/error." . SITE_CONFIG_PAGE_EXTENSION . "?e=" . urlencode($errorMsg));
		}
	}
}

// get download token and force download
header('location: '.$file->generateDirectDownloadUrlForMedia());
exit;