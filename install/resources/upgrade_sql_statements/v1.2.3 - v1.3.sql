INSERT INTO site_config VALUES (NULL, 'user_register_default_folders', '', 'Default folders for new accounts. Theese are automatically created when users register on the site. Leave blank to ignore. Pipe separated list. i.e. Documents|Images|Videos', '', 'string', 'File Manager');

ALTER TABLE `file_folder` ADD `totalSize` bigint(15) NULL AFTER `folderName`;
ALTER TABLE `file_folder` CHANGE `totalSize` `totalSize` bigint(15) NULL DEFAULT '0' AFTER `folderName`;

ALTER TABLE `file` ADD `uploadedUserId` int(11) NULL AFTER `userId`;
ALTER TABLE `file` ADD INDEX `uploadedUserId` (`uploadedUserId`);
UPDATE `file` SET `uploadedUserId` = `userId`;
