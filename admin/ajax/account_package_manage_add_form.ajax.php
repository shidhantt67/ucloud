<?php

// includes and security
include_once('../_local_auth.inc.php');

// prepare variables
$label = '';
$can_upload = 0;
$wait_between_downloads = '';
$download_speed = '';
$max_storage_bytes = '';
$show_site_adverts = '';
$show_upgrade_screen = '';
$days_to_keep_inactive_files = '';
$concurrent_uploads = '';
$concurrent_downloads = '';
$downloads_per_24_hours = '';
$max_download_filesize_allowed = '';
$can_remote_download = 0;
$max_remote_download_urls = '';
$max_upload_size = '';
$level_type = 'paid';
$on_upgrade_page = 0;
$max_uploads_per_day = 0;
$accepted_file_types = '';
$blocked_file_types = '';
$days_to_keep_trashed_files = 0;

// is this an edit?
$gEditUserLevelId = null;
$formType = 'add the';
$formName = 'addUserPackageForm';
if(isset($_REQUEST['gEditUserLevelId']))
{
    $gEditUserLevelId = (int) $_REQUEST['gEditUserLevelId'];
    $sQL = "SELECT * FROM user_level WHERE id=" . (int) $gEditUserLevelId;
    $packageDetails = $db->getRow($sQL);
    if($packageDetails)
    {
        $label = $packageDetails['label'];
        $can_upload = $packageDetails['can_upload'];
        $wait_between_downloads = $packageDetails['wait_between_downloads'];
        $download_speed = $packageDetails['download_speed'];
        $max_storage_bytes = $packageDetails['max_storage_bytes'];
        $show_site_adverts = $packageDetails['show_site_adverts'];
        $show_upgrade_screen = $packageDetails['show_upgrade_screen'];
        $days_to_keep_inactive_files = $packageDetails['days_to_keep_inactive_files'];
        $concurrent_uploads = $packageDetails['concurrent_uploads'];
        $concurrent_downloads = $packageDetails['concurrent_downloads'];
        $downloads_per_24_hours = $packageDetails['downloads_per_24_hours'];
        $max_download_filesize_allowed = $packageDetails['max_download_filesize_allowed'];
        $can_remote_download = (int)$packageDetails['can_remote_download'];
        $max_remote_download_urls = $packageDetails['max_remote_download_urls'];
        $max_upload_size = $packageDetails['max_upload_size'];
        $level_type = $packageDetails['level_type'];
        $on_upgrade_page = $packageDetails['on_upgrade_page'];
        $max_uploads_per_day = (int)$packageDetails['max_uploads_per_day'];
        $accepted_file_types = trim($packageDetails['accepted_file_types']);
        $blocked_file_types = trim($packageDetails['blocked_file_types']);
        $days_to_keep_trashed_files = (int)$packageDetails['days_to_keep_trashed_files'];

        $formType = 'update the';
        $formName = 'editUserPackageForm';
    }
}

// prepare result
$result = array();
$result['error'] = false;
$result['msg'] = '';

$result['html'] = '<p>Use the form below to ' . $formType . ' user package details.</p>';
$result['html'] .= '<form id="' . $formName . '" class="user_package_form form-horizontal form-label-left input_mask">';

$result['html'] .= '<div class="form">';
$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("label", "label")) . ':</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <input name="label" id="label" type="text" value="' . adminFunctions::makeSafe($label) . '" class="form-control"/>
                        </div>
                    </div>';
$result['html'] .= '</div><br/>';

$result['html'] .= '<div class="" role="tabpanel" data-example-id="togglable-tabs">';
$result['html'] .= '<ul id="myTab" class="nav nav-tabs bar_tabs" role="tablist">';
$result['html'] .= '    <li role="presentation" class="active"><a href="#tab_content1" id="home-tab" role="tab" data-toggle="tab" aria-expanded="true">Upload Settings</a>';
$result['html'] .= '    </li>';
$result['html'] .= '    <li role="presentation" class=""><a href="#tab_content2" role="tab" id="profile-tab" data-toggle="tab" aria-expanded="false">Download Settings</a>';
$result['html'] .= '    </li>';
$result['html'] .= '    <li role="presentation" class=""><a href="#tab_content3" role="tab" id="profile-tab" data-toggle="tab" aria-expanded="false">Site Options</a>';
$result['html'] .= '    </li>';
$result['html'] .= '</ul>';

$result['html'] .= '<div id="myTabContent" class="tab-content">';
$result['html'] .= '<div role="tabpanel" class="tab-pane fade active in" id="tab_content1" aria-labelledby="home-tab">';
$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Users Can Upload:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-3 col-sm-5 col-xs-12">
                            <select name="can_upload" id="can_upload" class="form-control" onChange="toggleElements(this); return false;">';
$options = array(0 => 'No', 1 => 'Yes');
foreach($options AS $k => $option)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if($can_upload == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option) . '</option>';
}
$result['html'] .= '        </select>
                            </div>
                            <p>
                                Allow users to upload.
                            </p>
                        </div>
                    </div>';

$result['html'] .= '<div class="form-group can_upload">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Max Uploads Per Day:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-3 col-sm-5 col-xs-12">
                                <input name="max_uploads_per_day" id="max_uploads_per_day" type="text" value="' . adminFunctions::makeSafe($max_uploads_per_day) . '" class="form-control"/>
                                <span class="input-group-addon">files</span>
                            </div>
                            <p>
                                Spam protect: Max files a user IP address or account can upload per day. Leave blank for unlimited.
                            </p>
                        </div>
                    </div>';

$result['html'] .= '<div class="form-group can_upload">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Concurrent Uploads:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-3 col-sm-5 col-xs-12">
                                <input name="concurrent_uploads" id="concurrent_uploads" type="text" value="' . adminFunctions::makeSafe($concurrent_uploads) . '" class="form-control" '.($can_upload==0?'disabled':'').'/>
                                <span class="input-group-addon">files</span>
                            </div>
                            <p>
                                The maximum amount of files that can be uploaded at the same time for users.
                            </p>
                        </div>
                    </div>';

$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Max Upload Size:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-5 col-sm-7 col-xs-12">
                                <input name="max_upload_size" id="max_upload_size" type="text" value="' . adminFunctions::makeSafe($max_upload_size) . '" class="form-control"/>
                                <span class="input-group-addon">bytes</span>
                            </div>
                            <p>
                                The max upload filesize for users (in bytes)
                            </p>
                        </div>
                    </div>';

$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Accepted Upload File Types:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-12 col-sm-12 col-xs-12">
                                <input name="accepted_file_types" id="accepted_file_types" type="text" value="' . adminFunctions::makeSafe($accepted_file_types) . '" class="form-control"/>
                            </div>
                            <p>
                                The file extensions which are permitted. Leave blank for all. Separate by semi-colon. i.e. .jpg;.gif;.doc;
                            </p>
                        </div>
                    </div>';

$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Blocked Upload File Types:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-12 col-sm-12 col-xs-12">
                                <input name="blocked_file_types" id="blocked_file_types" type="text" value="' . adminFunctions::makeSafe($blocked_file_types) . '" class="form-control"/>
                            </div>
                            <p>
                                The file extensions which are NOT permitted. Leave blank to allow all file types. Separate by semi-colon. i.e. .jpg;.gif;.doc;
                            </p>
                        </div>
                    </div>';

$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Enable Url Downloading:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-3 col-sm-5 col-xs-12">
                            <select name="can_remote_download" id="can_remote_download" class="form-control" onChange="toggleElements(this); return false;">';
$options = array(0 => 'No', 1 => 'Yes');
foreach($options AS $k => $option)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if($can_remote_download == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option) . '</option>';
}
$result['html'] .= '        </select>
                            </div>
                            <p>
                                Allow users to use the remote url download feature.
                            </p>
                        </div>
                    </div>';

$result['html'] .= '<div class="form-group can_remote_download">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Max Remote Urls:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-3 col-sm-5 col-xs-12">
                                <input name="max_remote_download_urls" id="max_remote_download_urls" type="text" value="' . adminFunctions::makeSafe($max_remote_download_urls) . '" class="form-control" '.($can_remote_download==0?'disabled':'').'/>
                                <span class="input-group-addon">urls</span>
                            </div>
                            <p>
                                The maximum remote urls a user can download at once.
                            </p>
                        </div>
                    </div>';

$result['html'] .= '<div class="form-group alt-highlight">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Storage Allowance:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-5 col-sm-7 col-xs-12">
                                <input name="max_storage_bytes" id="max_storage_bytes" type="text" value="' . adminFunctions::makeSafe($max_storage_bytes) . '" class="form-control"/>
                                <span class="input-group-addon">bytes</span>
                            </div>
                            <p>
                                Maximum storage permitted for users, in bytes. Use 0 (zero) for no limits.
                            </p>
                        </div>
                    </div>';
$result['html'] .= '</div>';

$result['html'] .= '<div role="tabpanel" class="tab-pane fade" id="tab_content2" aria-labelledby="profile-tab">';
$result['html'] .= '<div class="form-group wait_between_downloads">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Wait Between Downloads:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-3 col-sm-5 col-xs-12">
                                <input name="wait_between_downloads" id="wait_between_downloads" type="text" value="' . adminFunctions::makeSafe($wait_between_downloads) . '" class="form-control"/>
                                <span class="input-group-addon">seconds</span>
                            </div>
                            <p>
                                How long a user must wait between downloads, in seconds. Set to 0 (zero) to disable. Note: Ensure the \'downloads_track_current_downloads\' is also set to \'yes\' in site settings to enable this.
                            </p>
                        </div>
                    </div>';

$result['html'] .= '<div class="form-group download_speed">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Download Speed:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-5 col-sm-7 col-xs-12">
                                <input name="download_speed" id="download_speed" type="text" value="' . adminFunctions::makeSafe($download_speed) . '" class="form-control"/>
                                <span class="input-group-addon">bytes</span>
                            </div>
                            <p>
                                Maximum download speed for users, in bytes per second. i.e. 50000. Use 0 for unlimited.
                            </p>
                        </div>
                    </div>';

$result['html'] .= '<div class="form-group concurrent_downloads">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Concurrent Downloads:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-3 col-sm-5 col-xs-12">
                                <input name="concurrent_downloads" id="concurrent_downloads" type="text" value="' . adminFunctions::makeSafe($concurrent_downloads) . '" class="form-control"/>
                                <span class="input-group-addon">files</span>
                            </div>
                            <p>
                                The maximum concurrent downloads a user can do at once. Set to 0 (zero) for no limit. Note: Ensure the \'downloads_track_current_downloads\' is also set to \'yes\' in site settings to enable this.
                            </p>
                        </div>
                    </div>';

$result['html'] .= '<div class="form-group downloads_per_day">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Downloads Per Day:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-3 col-sm-5 col-xs-12">
                                <input name="downloads_per_24_hours" id="downloads_per_24_hours" type="text" value="' . adminFunctions::makeSafe($downloads_per_24_hours) . '" class="form-control"/>
                                <span class="input-group-addon">files</span>
                            </div>
                            <p>
                                The maximum files a user can download in a 24 hour period. Set to 0 (zero) to disable.
                            </p>
                        </div>
                    </div>';

$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Max Download Size:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-5 col-sm-7 col-xs-12">
                                <input name="max_download_filesize_allowed" id="max_download_filesize_allowed" type="text" value="' . adminFunctions::makeSafe($max_download_filesize_allowed) . '" class="form-control"/>
                                <span class="input-group-addon">bytes</span>
                            </div>
                            <p>
                                The maximum filesize a user can download (in bytes). Set to 0 (zero) to ignore.
                            </p>
                        </div>
                    </div>';
$result['html'] .= '</div>';

$result['html'] .= '<div role="tabpanel" class="tab-pane fade" id="tab_content3" aria-labelledby="profile-tab">';
$result['html'] .= '<div class="form-group nav_account_packages">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Show Adverts:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-3 col-sm-5 col-xs-12">
                            <select name="show_site_adverts" id="show_site_adverts" class="form-control">';
$options = array(0 => 'No', 1 => 'Yes');
foreach($options AS $k => $option)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if($show_site_adverts == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option) . '</option>';
}
$result['html'] .= '        </select>
                            </div>
                            <p>
                                Show adverts for users across the site.
                            </p>
                        </div>
                    </div>';

$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Show Upgrade Page:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-3 col-sm-5 col-xs-12">
                            <select name="show_upgrade_screen" id="show_upgrade_screen" class="form-control">';
$options = array(0 => 'No', 1 => 'Yes');
foreach($options AS $k => $option)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if($show_upgrade_screen == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option) . '</option>';
}
$result['html'] .= '        </select>
                            </div>
                            <p>
                                Show the premium account upgrade page for users.
                            </p>
                        </div>
                    </div>';

$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Inactive Files Days:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-3 col-sm-5 col-xs-12">
                                <input name="days_to_keep_inactive_files" id="days_to_keep_inactive_files" type="text" value="' . adminFunctions::makeSafe($days_to_keep_inactive_files) . '" class="form-control"/>
                                <span class="input-group-addon">days</span>
                            </div>
                            <p>
                                The amount of days after non-active files are removed for users. Non-active = time since last download. Use 0 for unlimited.
                            </p>
                        </div>
                    </div>';

$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Trash Delete Days:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-3 col-sm-5 col-xs-12">
                                <input name="days_to_keep_trashed_files" id="days_to_keep_trashed_files" type="text" value="' . adminFunctions::makeSafe($days_to_keep_trashed_files) . '" class="form-control"/>
                                <span class="input-group-addon">days</span>
                            </div>
                            <p>
                                File are kept in the users trash for this period, then automatically removed. Use 0 for unlimited.
                            </p>
                        </div>
                    </div>';

$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Package Type:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-12 col-sm-12 col-xs-12">
                            <select name="level_type" id="level_type" class="form-control">';
$options = array('free' => 'Free', 'paid' => 'Paid', 'moderator' => 'Moderator', 'admin' => 'Admin', 'nonuser' => 'Non User (do not use - system use only)');
if(themeHelper::getCurrentProductType() == 'cloudable') {
    $options = array('free' => 'User', 'admin' => 'Admin', 'nonuser' => 'Non User (do not use - system use only)');
}
foreach($options AS $k => $option)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if($level_type == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option) . '</option>';
}
$result['html'] .= '        </select>
                            </div>
                            <p>
                                The type of account. Note that Moderator &amp; Admin have access to the admin area.
                            </p>
                        </div>
                    </div>';
$result['html'] .= '<div class="form-group nav_account_packages">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">On Upgrade Page:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group col-md-3 col-sm-5 col-xs-12">
                            <select name="on_upgrade_page" id="on_upgrade_page" class="form-control">';
$options = array(0 => 'No', 1 => 'Yes');
foreach($options AS $k => $option)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if($on_upgrade_page == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option) . '</option>';
}
$result['html'] .= '        </select>
                            </div>
                            <p>
                                Whether to show this package on the upgrade page.
                            </p>
                        </div>
                    </div>';
$result['html'] .= '</div><br/>';

$result['html'] .= '</div>';
$result['html'] .= '</div>';

$result['html'] .= pluginHelper::getPluginAdminPackageSettingsFormV2($gEditUserLevelId);

$result['html'] .= '</form>';

echo json_encode($result);
exit;
