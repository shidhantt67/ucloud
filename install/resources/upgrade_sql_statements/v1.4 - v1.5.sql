SET NAMES utf8mb4;

ALTER TABLE `user_level` ADD `can_remote_download` int(1) NOT NULL DEFAULT '1' AFTER `max_download_filesize_allowed`;

INSERT INTO `site_config` (`config_key`, `label`, `config_value`, `config_description`, `availableValues`, `config_type`, `config_group`, `display_order`) VALUES ('captcha_login_screen_normal', 'Show User Login Screen', 'no', 'Show the captcha on the standard login screen.', '[\"yes\",\"no\"]', 'select', 'Captcha', 6);
INSERT INTO `site_config` (`config_key`, `label`, `config_value`, `config_description`, `availableValues`, `config_type`, `config_group`, `display_order`) VALUES ('captcha_login_screen_admin', 'Show Admin Login Screen', 'no', 'Show the captcha on the admin login screen.', '[\"yes\",\"no\"]', 'select', 'Captcha', 7);
UPDATE `site_config` SET label = 'File Download - Non User' WHERE config_key = 'non_user_show_captcha';
UPDATE `site_config` SET label = 'File Download - Free User' WHERE config_key = 'free_user_show_captcha';

ALTER TABLE `download_token` ADD `file_transfer` int(1) NOT NULL DEFAULT '1';

ALTER TABLE `file_action` ADD `is_uploaded_file` int(11) NOT NULL DEFAULT '0' AFTER `file_path`;

UPDATE file_server SET storagePath = 'files/' WHERE (storagePath = '' OR storagePath IS NULL) AND serverType = 'local';

ALTER TABLE `file_server` DROP `lastFileActionQueueProcess`;
ALTER TABLE `file_server` ADD `lastFileActionQueueProcess` datetime NULL AFTER `routeViaMainSite`;
ALTER TABLE `file_server` ADD `scriptRootPath` varchar(255) NULL AFTER `statusId`;

UPDATE file_folder_share SET last_accessed = '2038-01-01 00:00:00' WHERE CAST(last_accessed AS CHAR(20)) = '0000-00-00 00:00:00';
ALTER TABLE `file_folder_share` CHANGE `last_accessed` `last_accessed` datetime NULL AFTER `date_created`;
UPDATE file_folder_share SET last_accessed = NULL WHERE last_accessed = '2038-01-01 00:00:00';

UPDATE plugin SET date_installed = '2038-01-01 00:00:00' WHERE CAST(date_installed AS CHAR(20)) = '0000-00-00 00:00:00';
ALTER TABLE `plugin` CHANGE `date_installed` `date_installed` datetime NULL AFTER `is_installed`;
UPDATE plugin SET date_installed = NULL WHERE date_installed = '2038-01-01 00:00:00';

ALTER TABLE `file` DROP INDEX `keywords`;
ALTER TABLE `file` ADD INDEX `keywords` (`keywords`);
ALTER TABLE `stats` CHANGE `download_date` `download_date` datetime NULL AFTER `id`;

ALTER TABLE `apiv2_access_token` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `apiv2_api_key` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `background_task` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `background_task_log` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `banned_ips` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `country_info` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `cross_site_action` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `download_page` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `download_token` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `download_tracker` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `file` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `file_action` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `file_block_hash` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `file_folder` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `file_folder_share` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `file_report` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `file_server` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `file_server_container` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `file_server_status` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `file_status` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `internal_notification` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `language` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `language_content` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `language_key` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `login_failure` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `login_success` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `payment_log` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `plugin` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `premium_order` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `remote_url_download_queue` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `sessions` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `site_config` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `stats` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `theme` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `users` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `user_level` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';
ALTER TABLE `user_level_pricing` ENGINE= InnoDB COLLATE 'utf8mb4_general_ci';

ALTER TABLE `file_folder` ADD `status` enum('active','trash','deleted') COLLATE 'utf8_general_ci' NULL DEFAULT 'active' AFTER `urlHash`;
ALTER TABLE `file` ADD `status` enum('active','trash','deleted') NULL DEFAULT 'active' AFTER `statusId`;
UPDATE `file` SET status = 'deleted' WHERE statusId != 1;
ALTER TABLE `file` ADD `date_updated` datetime NULL;

ALTER TABLE `file` ADD INDEX `status` (`status`);
ALTER TABLE `file_folder` ADD INDEX `status` (`status`);

ALTER TABLE `user_level` ADD `max_uploads_per_day` bigint(18) NOT NULL DEFAULT '0' AFTER `max_upload_size`;
UPDATE `user_level` SET `max_uploads_per_day` = (SELECT config_value FROM site_config WHERE config_key = 'max_files_per_day' LIMIT 1);
DELETE FROM `site_config` WHERE config_key = 'max_files_per_day' LIMIT 1;

ALTER TABLE `file` ADD INDEX `uploadedIP` (`uploadedIP`);

ALTER TABLE `user_level` ADD `accepted_file_types` varchar(255) NOT NULL DEFAULT '' AFTER `max_uploads_per_day`;
UPDATE `user_level` SET `accepted_file_types` = (SELECT config_value FROM site_config WHERE config_key = 'accepted_upload_file_types' LIMIT 1);
DELETE FROM `site_config` WHERE config_key = 'accepted_upload_file_types' LIMIT 1;

ALTER TABLE `user_level` ADD `blocked_file_types` varchar(255) NOT NULL DEFAULT '' AFTER `accepted_file_types`;
UPDATE `user_level` SET `blocked_file_types` = (SELECT config_value FROM site_config WHERE config_key = 'blocked_upload_file_types' LIMIT 1);
DELETE FROM `site_config` WHERE config_key = 'blocked_upload_file_types' LIMIT 1;

ALTER TABLE `user_level` ADD `days_to_keep_trashed_files` int(11) NOT NULL DEFAULT '0' AFTER `days_to_keep_inactive_files`;
