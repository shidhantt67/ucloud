<?php
// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// setup database
$db = Database::getDatabase(true);

// load items
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

// validation
$checkedFileIds = $db->getRows('SELECT id '
        . 'FROM file '
        . 'WHERE id IN ('.implode(',', $safeFileIds).') '
        . 'AND (userId = :userId OR uploadedUserId = :uploadedUserId)', array(
            'userId' => $Auth->id,
            'uploadedUserId' => $Auth->id,
        ));
$checkedFolderIds = $db->getRows('SELECT id '
        . 'FROM file_folder '
        . 'WHERE id IN ('.implode(',', $safeFolderIds).') '
        . 'AND userId = :userId', array(
            'userId' => $Auth->id,
        ));

$totalItems = (int)(COUNT($checkedFileIds)+COUNT($checkedFolderIds));

// load folder structure as array
$folderListing = fileFolder::loadAllActiveForSelect($Auth->id);
?>

<form action="<?php echo WEB_ROOT; ?>/ajax/_account_restore_from_trash.process.ajax.php" autocomplete="off">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title"><?php echo t("restore_items", "Restore Items"); ?> (<?php echo $totalItems; ?> <?php echo t("items", "items"); ?>)</h4>
    </div>

    <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="folder" class="control-label"><?php echo UCWords(t("restore_to_folder", "restore to folder")); ?></label>
                        <select class="form-control" name="restoreFolderId" id="restoreFolderId">
                            <option value="">/</option>
                            <?php
                            foreach ($folderListing AS $k => $folderListingItem) {
                                echo '<option value="' . (int) $k . '"';
                                echo '>' . validation::safeOutputToScreen($folderListingItem) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <p><?php echo t("restore_note_file_contents_included", "Note that restoring a folder will also restore any files within it."); ?></p>
                </div>
            </div>
    </div>
    <div class="modal-footer">
        <input type="hidden" name="submitme" id="submitme" value="1"/>
        <?php foreach($checkedFileIds AS $checkedFileId): ?>
        <input type="hidden" value="<?php echo $checkedFileId['id']; ?>" name="fileIds[]"/>
        <?php endforeach; ?>
        <?php foreach($checkedFolderIds AS $checkedFolderId): ?>
        <input type="hidden" value="<?php echo $checkedFolderId['id']; ?>" name="folderIds[]"/>
        <?php endforeach; ?>
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo t("cancel", "cancel"); ?></button>
        <button type="button" class="btn btn-info" onClick="processAjaxForm(this, function () {
                    refreshFileListing();
                    $('.modal').modal('hide');
                });
                return false;"><?php echo UCWords(t("restore", "restore")); ?> <i class="entypo-check"></i></button>
    </div>
</form>