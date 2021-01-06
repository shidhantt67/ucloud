<?php

$ext = array('jpg', 'jpeg', 'png', 'gif');

$file = $params['file'];
if (in_array(strtolower($file->extension), $ext)) {
    // load plugin details
    $pluginObj = pluginHelper::getInstance('filepreviewer');

    // queue cache for delete
    $pluginObj->deleteImageCache($file->id);
}