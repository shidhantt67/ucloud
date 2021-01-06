<?php

class pluginFileimport extends Plugin
{
    public $config = null;

    public function __construct()
    {
        // get the plugin config
        include('_plugin_config.inc.php');

        // load config into the object
        $this->config = $pluginConfig;
    }

    public function getPluginDetails()
    {
        return $this->config;
    }

    public function getLowestWritableBasePath()
    {
        // find the lowest folder this setup has access to
        // loop path until we can not read any more
        $failsafe++;
        $baseFolder = DOC_ROOT;
        $lastBaseFolder = $baseFolder;
        while(is_readable($baseFolder) && $failsafe < 20)
        {
            $lastBaseFolder = $baseFolder;
            $baseFolder = dirname($baseFolder);
            if(strlen(trim($baseFolder)) == 0)
            {
                $failsafe = 20;
            }
            $failsafe++;
        }

        // make sure it ends with a forward slash
        if(substr($lastBaseFolder, strlen($lastBaseFolder) - 1, 1) != DIRECTORY_SEPARATOR)
        {
            $lastBaseFolder .= DIRECTORY_SEPARATOR;
        }

        return $lastBaseFolder;
    }

    // local functions
    public function importFiles($localPath, $userId, $folderId)
    {
        // setup database
        $db = Database::getDatabase();

        // get items
        $items = coreFunctions::getDirectoryListing($localPath);
        if(COUNT($items))
        {
            foreach($items AS $item)
            {
                if(is_dir($item))
                {
                    // directory, first make sure we have the folder
                    $folderName = basename($item);
                    $childFolderId = (int) $db->getValue('SELECT id FROM file_folder WHERE userId = ' . (int) $userId . ' AND folderName = ' . $db->quote($folderName) . ' AND parentId ' . ($folderId > 0 ? ' IS NULL' : ('=' . (int) $folderId)) . ' LIMIT 1');
                    if(!$childFolderId)
                    {
                        $db->query('INSERT INTO file_folder (folderName, isPublic, userId, parentId) VALUES (:folderName, :isPublic, :userId, :parentId)', array('folderName' => $folderName, 'isPublic' => 1, 'userId' => $userId, 'parentId' => $folderId));
                        $childFolderId = $db->insertId();
                    }

                    // loop again for files within
                    $this->importFiles($item, $userId, $childFolderId);
                }
                else
                {
                    // file
                    $rs = $this->importFile($item, $userId, $folderId);
                    if($rs)
                    {
                        $this->output('Imported ' . $item);
                    }
                    else
                    {
                        $this->output('Error: File above failed import (' . $item . ')');
                    }
                }
            }
        }
    }

    public function importFile($filePath, $userId, $folderId)
    {
        // setup uploader
        $uploadHandler = new uploader(array(
            'folder_id' => $folderId,
            'user_id' => $userId,
            'upload_source'=>'fileimport',
        ));

        // get original filename
        $pathParts = pathinfo($filePath);
        $filename = $pathParts['filename'];
        if(strlen($pathParts['extension']))
        {
            $filename .= '.' . $pathParts['extension'];
        }

        // get mime type
        $mimeType = file::estimateMimeTypeFromExtension($filename, 'application/octet-stream');
        if(($mimeType == 'application/octet-stream') && (class_exists('finfo', false)))
        {
            $finfo = new finfo;
            $mimeType = $finfo->file($filePath, FILEINFO_MIME);
        }

        $fileUpload = new stdClass();
        $fileUpload->name = stripslashes($filename);
        $fileUpload->size = filesize($filePath);
        $fileUpload->type = $mimeType;
        $fileUpload->error = null;
        $fileUpload = $uploadHandler->moveIntoStorage($fileUpload, $filePath, true); // keeps the original
        if(strlen($fileUpload->error))
        {
            $this->output('Error: ' . $fileUpload->error);
            return false;
        }

        return true;
    }

    public function output($msg = '', $exit = false)
    {
        echo $msg;
        if(defined('CLI_MODE') && CLI_MODE == true)
        {
            echo "\n";
        }
        else
        {
            echo "<br/>";
            ob_start();
            ob_end_flush();
        }
        if($exit == true)
        {
            die();
        }
    }
}
