<?php

// includes and security
include_once('../_local_auth.inc.php');

// load all flysystem storage containers
$flySystemContainers = $db->getRows('SELECT * FROM file_server_container WHERE is_enabled = 1 ORDER BY label');

// prepare variables
$server_label = '';
$status_id = '';
$server_type = '';
$ftp_host = '';
$ftp_port = 21;
$ftp_username = '';
$ftp_password = '';
$storage_path = 'files/';
$formType = 'set the new';
$file_server_domain_name = '';
$script_root_path = '';
$script_path = '/';
$max_storage_space = 0;
$server_priority = 0;
$route_via_main_site = 1;
$download_accelerator = 0;
$file_server_direct_ip_address = '';
$file_server_direct_ssh_port = 22;
$file_server_direct_ssh_username = '';
$file_server_direct_ssh_password = '';
$server_config_array = array();
$file_server_download_proto = _CONFIG_SITE_PROTOCOL;
$cdn_url = '';

// server config variables
$ftp_server_type = 'linux';
$ftp_passive_mode = 'no';

// is this an edit?
$fileServerId = null;

if(isset($_REQUEST['gEditFileServerId']))
{
    $fileServerId = (int) $_REQUEST['gEditFileServerId'];
    if($fileServerId)
    {
        $sQL = "SELECT * FROM file_server WHERE id=" . $fileServerId;
        $serverDetails = $db->getRow($sQL);
        if($serverDetails)
        {
            $server_label = $serverDetails['serverLabel'];
            $status_id = $serverDetails['statusId'];
            $server_type = $serverDetails['serverType'];
            $ftp_host = $serverDetails['ipAddress'];
            $ftp_port = $serverDetails['ftpPort'];
            $ftp_username = $serverDetails['ftpUsername'];
            $ftp_password = $serverDetails['ftpPassword'];
            $storage_path = $serverDetails['storagePath'];
            $formType = 'update the';
            $file_server_domain_name = $serverDetails['fileServerDomainName'];
            $script_root_path = (strlen($serverDetails['scriptRootPath'])?$serverDetails['scriptRootPath']:($server_type==='local'?DOC_ROOT:$script_root_path));
            $script_path = $serverDetails['scriptPath'];
            $max_storage_space = strlen($serverDetails['maximumStorageBytes']) ? $serverDetails['maximumStorageBytes'] : 0;
            $server_priority = (int) $serverDetails['priority'];
            $route_via_main_site = (int) $serverDetails['routeViaMainSite'];
            $download_accelerator = (int) $serverDetails['dlAccelerator'];

            // @TODO - later move the above settings into here
            $server_config = $serverDetails['serverConfig'];
            if(strlen($server_config))
            {
                $server_config_array = json_decode($server_config, true);
                if(is_array($server_config_array))
                {
                    foreach($server_config_array AS $k => $v)
                    {
                        // make available as local variables
                        $$k = $v;
                    }
                    
                    // if we have the path in the serverConfig, store it in the script_root_path
                    if((isset($server_config_array['file_server_direct_server_path_to_storage']) && (strlen($server_config_array['file_server_direct_server_path_to_storage'])))) {
                        $script_root_path = $server_config_array['file_server_direct_server_path_to_storage'];
                    }
                }
            }

            // server login data
            $server_access = $serverDetails['serverAccess'];
            if(strlen($server_access))
            {
                $server_access = coreFunctions::decryptValue($server_access);
                $server_access_array = json_decode($server_access, true);
                if(is_array($server_access_array))
                {
                    foreach($server_access_array AS $k => $v)
                    {
                        // make available as local variables
                        $$k = $v;
                    }
                }
            }
        }
    }
}

// load all server statuses
$sQL = "SELECT id, label FROM file_server_status ORDER BY label";
$statusDetails = $db->getRows($sQL);

// prepare whether we should disable local server or not
$isDefaultServer = false;
if($server_label == 'Local Default')
{
    $isDefaultServer = true;
}

// prepare result
$result = array();
$result['error'] = false;
$result['msg'] = '';

$result['html'] .= '<form id="addFileServerForm" class="form-horizontal form-label-left input_mask">
                        <div class="x_panel">
                            <div class="x_content">
                                <div class="" role="tabpanel" data-example-id="togglable-tabs">
                                    <ul id="myTab" class="nav nav-tabs bar_tabs" role="tablist">
                                        <li role="presentation" class="active"><a href="#tab_content1" id="home-tab" role="tab" data-toggle="tab" aria-expanded="true">Server Details</a>
                                        </li>
                                        <li role="presentation" class=""><a href="#tab_content2" role="tab" id="profile-tab" data-toggle="tab" aria-expanded="false">CDN Support</a>
                                        </li>
                                        <li role="presentation" class=""><a href="#tab_content3" role="tab" id="profile-tab" data-toggle="tab" aria-expanded="false">Storage Options</a>
                                        </li>
                                    </ul>
                                    <div id="myTabContent" class="tab-content">
                                        <div role="tabpanel" class="tab-pane fade active in" id="tab_content1" aria-labelledby="home-tab">
                                            <div class="x_title">
                                                <h2>Server Details:</h2>
                                                <div class="clearfix"></div>
                                            </div>';

$result['html'] .= '                        <p>Use the form below to ' . $formType . ' file server details.<br/><br/></p>';

$result['html'] .= '                        <div class="form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("server_label", "server label")) . ':</label>
                                                <div class="col-md-5 col-sm-5 col-xs-12">
                                                    <input name="server_label" id="server_label" placeholder="i.e. File Server 1" type="text" class="form-control" value="' . adminFunctions::makeSafe($server_label) . '" class="xlarge" ' . ($isDefaultServer ? 'DISABLED' : '') . '/>
                                                    <p class="text-muted">For your own internal reference only.</p>
                                                </div>
                                            </div>';
$result['html'] .= '                        <div class="form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("status", "status")) . ':</label>
                                                <div class="col-md-5 col-sm-5 col-xs-12">
                                                    <select name="status_id" id="status_id" class="form-control">';
foreach($statusDetails AS $statusDetail)
{
    $result['html'] .= '        <option value="' . $statusDetail['id'] . '"';
    if($status_id == $statusDetail['id'])
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($statusDetail['label']) . '</option>';
}
$result['html'] .= '        </select>
                                                </div>
                                            </div>';

$result['html'] .= '                        <div class="form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("server_type", "server type")) . ':</label>
                                                <div class="col-md-9 col-sm-9 col-xs-12">
                                                    <select name="server_type" id="server_type" class="form-control" onChange="showHideFTPElements(); return false;" ' . ($isDefaultServer ? 'DISABLED' : '') . '>
                                                        <optgroup label="Storage Options">
                                                        <option value="local"' . ($server_type == 'local' ? ' SELECTED' : '') . '>Local (storage located on the same server as your site - if you don\'t need external storage)</option>
                                                        <option value="direct"' . ($server_type == 'direct' ? ' SELECTED' : '') . '>Remote Direct (files are upload and download directly with the remote file server - for large filesizes and busy sites)</option>
                                                        <option value="ftp"' . ($server_type == 'ftp' ? ' SELECTED' : '') . '>FTP (uses FTP via PHP to upload files into storage - for smaller filesizes or personal sites)</option>';

$params = pluginHelper::includeAppends('admin_server_manage_add_form_type_select.inc.php', array('html' => '', 'server_type' => $server_type));
if(isset($params['html']))
{
    $result['html'] .= $params['html'];
}

$result['html'] .= '</optgroup>';

// add any flysystem containers
if(COUNT($flySystemContainers))
{
    $result['html'] .= '<optgroup label="Flysystem Adapters - Experimental - Requires Min PHP v5.6">';
    foreach($flySystemContainers AS $flySystemContainer)
    {
        $dataFields = $flySystemContainer['expected_config_json'];
        if($server_type == $flySystemContainer['entrypoint'])
        {
            $dataFields = populateDataFields($dataFields, $server_config_array);
        }
        $result['html'] .= '<option data-fields="'.adminFunctions::makeSafe($dataFields).'" value="'.adminFunctions::makeSafe($flySystemContainer['entrypoint']).'"' . ($server_type == $flySystemContainer['entrypoint'] ? ' SELECTED' : '') . '>'.adminFunctions::makeSafe($flySystemContainer['label']).'</option>';
    }
}   $result['html'] .= '</optgroup>';

function populateDataFields($dataFields, $populateData)
{
    $dataFieldsArr = json_decode($dataFields, true);
    if(COUNT($dataFieldsArr))
    {
        foreach($dataFieldsArr AS $fieldName=>$dataFieldsArrItem)
        {
            if(isset($populateData[$fieldName]))
            {
                $dataFieldsArr[$fieldName]['default'] = $populateData[$fieldName];
            }
        }
    }
    
    return json_encode($dataFieldsArr);
}

$result['html'] .= '        </select>
                                                </div>
                                            </div>';

$result['html'] .= '                        <span class="localElements" style="display: none;">';
$result['html'] .= '                        <div class="form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("file_server_direct_server_path_to_install", "server path to install")) . ':</label>
                                                <div class="col-md-9 col-sm-9 col-xs-12">
                                                    <input name="script_root_path" id="local_script_root_path" placeholder="/home/admin/public_html" type="text" value="' . adminFunctions::makeSafe($script_root_path) . '" class="form-control" DISABLED/>
                                                   <p class="text-muted">The full server path to your install. If you\'re unsure, leave empty and it\'ll be auto generated.</p>
                                                </div>
                                            </div>';
$result['html'] .= '                        <div class="form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("file_storage_path", "file storage path")) . ':</label>
                                                <div class="col-md-9 col-sm-9 col-xs-12">
                                                    <input name="storage_path" id="local_storage_path" type="text" value="' . adminFunctions::makeSafe($storage_path) . '" class="form-control" ' . ($isDefaultServer ? 'DISABLED' : '') . '/>
                                                    <p class="text-muted">Which folder to store files in on the server, relating to the script root. Normally files/</p>
                                                </div>
                                            </div>';
$result['html'] .= '                        <div class="form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("improved_download_management", "Improved Downloads")) . ':</label>
                                                <div class="col-md-9 col-sm-9 col-xs-12">
                                                    <select name="dlAccelerator" id="dlAccelerator1" class="form-control">';
$options = array(2 => 'XSendFile (Apache Only)', 1 => 'X-Accel-Redirect (Nginx Only)', 0 => 'Disabled');
foreach($options AS $k => $option)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if($download_accelerator == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option) . '</option>';
}
$result['html'] .= '
                                                    </select>
                                                    <p>This dramatically increases server performance for busy sites by handing the process away from PHP to Apache or Nginx. <strong>Important: </strong>You must make the server changes listed in the relevant link below for this to work.</p>
                                                    <ul>
                                                        <li><a href="https://support.mfscripts.com/public/kb_view/1/" target="_blank" style="text-decoration: underline;">Enable XSendFile for Apache</a>.</li>
                                                        <li><a href="https://support.mfscripts.com/public/kb_view/2/" target="_blank" style="text-decoration: underline;">Enable X-Accel-Redirect for Nginx</a>.</li>
                                                    </ul>
                                                </div>
                                            </div>';
$result['html'] .= '                    </span>';

$result['html'] .= '                    <span class="ftpElements" style="display: none;">';
$result['html'] .= '                        <div class="form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("ftp_host", "ftp host")) . ':</label>
                                                <div class="col-md-5 col-sm-5 col-xs-12">
                                                    <input name="ftp_host" id="ftp_host" type="text" class="form-control" value="' . adminFunctions::makeSafe($ftp_host) . '"/>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("ftp_port", "ftp port")) . ':</label>
                                                <div class="col-md-5 col-sm-5 col-xs-12">
                                                    <input name="ftp_port" id="ftp_port" type="text" class="form-control" value="' . adminFunctions::makeSafe($ftp_port) . '" class="small"/>
                                                </div>
                                            </div>';
$result['html'] .= '                        <div class="form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("ftp_username", "ftp username")) . ':</label>
                                                <div class="col-md-5 col-sm-5 col-xs-12">
                                                    <input name="ftp_username" id="ftp_username" type="text" class="form-control" value="' . adminFunctions::makeSafe($ftp_username) . '"/>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("ftp_password", "ftp password")) . ':</label>
                                                <div class="col-md-5 col-sm-5 col-xs-12">
                                                    <input name="ftp_password" id="ftp_password" type="password" class="form-control" value="' . adminFunctions::makeSafe($ftp_password) . '"/>
                                                </div>
                                            </div>';
$result['html'] .= '                        <div class="form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("file_storage_path", "file storage path")) . ':</label>
                                                <div class="col-md-9 col-sm-9 col-xs-12">
                                                    <input name="storage_path" id="ftp_storage_path" type="text" class="form-control" value="' . adminFunctions::makeSafe($storage_path) . '" class="large"/>
                                                    <p class="text-muted">As the FTP user would see it. Login with this FTP user using an FTP client to confirm<br/>the path to use.</p>
                                                </div>
                                            </div>';
$result['html'] .= '                        <div class="form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("ftp_server_type", "ftp server type")) . ':</label>
                                                <div class="col-md-5 col-sm-5 col-xs-12">
                                                    <select name="ftp_server_type" id="ftp_server_type" class="form-control">';
    $serverTypes = array('linux' => 'Linux (for most)', 'windows' => 'Windows', 'windows_alt' => 'Windows Alternative');
    foreach($serverTypes AS $k => $serverType)
    {
        $result['html'] .= '        <option value="' . $k . '"';
        if($ftp_server_type == $k)
        {
            $result['html'] .= '        SELECTED';
        }
        $result['html'] .= '        >' . $serverType . '</option>';
    }
    $result['html'] .= '        </select>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("ftp_enable_passive_mode", "enable passive mode")) . ':</label>
                                                <div class="col-md-3 col-sm-3 col-xs-12">
                                                    <select name="ftp_passive_mode" id="ftp_passive_mode" class="form-control">';
    $serverPassiveOptions = array('no' => 'No (default)', 'yes' => 'Yes');
    foreach($serverPassiveOptions AS $k => $serverPassiveOption)
    {
        $result['html'] .= '        <option value="' . $k . '"';
        if($ftp_passive_mode == $k)
        {
            $result['html'] .= '        SELECTED';
        }
        $result['html'] .= '        >' . $serverPassiveOption . '</option>';
    }
$result['html'] .= '        </select>
                                                </div>
                                            </div>';
$result['html'] .= '                    </span>';

$result['html'] .= '                    <span class="directElements" style="display: none;">';
$result['html'] .= '                    <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("file_server_domain_name", "file server domain name")) . ':</label>
                                            <div class="col-md-9 col-sm-9 col-xs-12">
                                                <div class="input-group">
                                                    <span class="input-group-addon">' . _CONFIG_SITE_PROTOCOL . '://</span>
                                                    <input name="file_server_domain_name" id="file_server_domain_name" class="form-control" placeholder="i.e. fs1.' . _CONFIG_SITE_HOST_URL . '" type="text" value="' . adminFunctions::makeSafe($file_server_domain_name) . '" onKeyUp="updateUrlParams();" class="large"/>
                                                </div>
                                                <p class="text-muted">Uploads must use the same protocol as this site (' . _CONFIG_SITE_PROTOCOL . ') due to browser security restrictions.</p>
                                            </div>
                                        </div>';
$result['html'] .= '                    <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("file_server_direct_server_path_to_install", "server path to install")) . ':</label>
                                            <div class="col-md-9 col-sm-9 col-xs-12">
                                                <input name="script_root_path" id="direct_script_root_path" placeholder="/home/admin/public_html" type="text" value="' . adminFunctions::makeSafe($script_root_path) . '" class="form-control"/>
                                                <p class="text-muted">The full server path to the script install on your file server. If you\'re unsure, leave empty and it\'ll be auto generated.</p>
                                            </div>
                                        </div>';
$result['html'] .= '                    <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("file_storage_path", "file storage path")) . ':</label>
                                            <div class="col-md-9 col-sm-9 col-xs-12">
                                                <input name="storage_path" id="direct_storage_path" type="text" value="' . adminFunctions::makeSafe($storage_path) . '" class="form-control"/>
                                                <p class="text-muted">Which folder to store files in on the file server, relating to the script root. Normally files/</p>
                                            </div>
                                        </div>';
$result['html'] .= '                    <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("url_path", "url path")) . ':</label>
                                            <div class="col-md-9 col-sm-9 col-xs-12">
                                                <input name="script_path" id="script_path" type="text" placeholder="/ - root, unless you installed into a sub-folder" value="' . adminFunctions::makeSafe($script_path) . '" class="form-control" onKeyUp="updateUrlParams();"/>
                                                <p class="text-muted">Use /, unless you\'ve installed into a sub-folder on the file server domain above.</p>
                                            </div>
                                        </div>';
$result['html'] .= '                    <div class="clearfix alt-highlight" style="display: none;">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("use_main_site_url", "use main site url")) . ':</label>
                                            <div class="col-md-9 col-sm-9 col-xs-12">
                                                <select name="route_via_main_site" id="route_via_main_site" class="form-control">';
$options = array(1 => 'yes (recommended)', 0 => 'no');
foreach($options AS $k => $option)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if($route_via_main_site == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option) . '</option>';
}
$result['html'] .= '
                                                </select>
                                                <p class="text-muted">If \'yes\' ' . _CONFIG_SITE_HOST_URL . ' will be used for all download urls generated on the site. Otherwise the above \'File Server Domain Name\' will be used. Changing this will not impact any existing download urls.</p>
                                            </div>
                                        </div>';

$result['html'] .= '                    <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("improved_download_management", "Improved Downloads")) . ':</label>
                                            <div class="col-md-9 col-sm-9 col-xs-12">
                                                <select name="dlAccelerator" id="dlAccelerator2" class="form-control">';
$options = array(2 => 'XSendFile (Apache Only)', 1 => 'X-Accel-Redirect (Nginx Only)', 0 => 'Disabled');
foreach($options AS $k => $option)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if($download_accelerator == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option) . '</option>';
}
$result['html'] .= '
                                                </select>
                                                <p>This dramatically increases server performance for busy sites by handing the process away from PHP to Apache or Nginx. <strong>Important: </strong>You must make the server changes listed in the relevant link below for this to work.</p>
                                                <ul>
                                                    <li><a href="https://support.mfscripts.com/public/kb_view/1/" target="_blank" style="text-decoration: underline;">Enable XSendFile for Apache</a>.</li>
                                                    <li><a href="https://support.mfscripts.com/public/kb_view/2/" target="_blank" style="text-decoration: underline;">Enable X-Accel-Redirect for Nginx</a>.</li>
                                                </ul>
                                            </div>
                                        </div>';
$result['html'] .= '                    <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("file_server_download_proto", "file server download protocol")) . ':</label>
                                            <div class="col-md-9 col-sm-9 col-xs-12">
                                                    <select name="file_server_download_proto" id="file_server_download_proto" class="form-control">';
    $serverPassiveOptions = array('http' => 'http', 'https' => 'https');
    foreach($serverPassiveOptions AS $k => $serverPassiveOption)
    {
        $result['html'] .= '        <option value="' . $k . '"';
        if($file_server_download_proto == $k)
        {
            $result['html'] .= '        SELECTED';
        }
        $result['html'] .= '        >' . $serverPassiveOption . '</option>';
    }
$result['html'] .= '        </select>
                                                <p class="text-muted">Generally use the same as this site (' . _CONFIG_SITE_PROTOCOL . '). Note that this is only for download urls.</p>
                                            </div>
                                        </div>';
$result['html'] .= '                    </span>';



$result['html'] .= '                    <span class="localElements serverAccessWrapper" style="display: none;">';
$result['html'] .= '                    <div class="x_title">
                                            <h2>' . UCWords(adminFunctions::t("local_server_ssh_details_this_server", "local server SSH details (This Server)")) . ':</h2>
                                            <div class="clearfix"></div>
                                        </div>';
$result['html'] .= '                    <p>The following information should be filled in if you\'re using the media converter plugin or archive manager. If you have openssl_encrypt() functions available within your server PHP setup, these details will be encrypted in your database using AES256. In a future release we\'ll be able to use these details to automatically update your site.<br/><br/></p>';
$result['html'] .= '                    <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("local_server_direct_ip_address", "local server ip address")) . ':</label>
                                            <div class="col-md-5 col-sm-5 col-xs-12">
                                                <input name="file_server_direct_ip_address" id="file_server_direct_ip_address_2" placeholder="i.e. 124.194.125.34" type="text" value="' . adminFunctions::makeSafe($file_server_direct_ip_address) . '" class="form-control"/>
                                            </div>
                                        </div>';
$result['html'] .= '                    <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("local_server_direct_ssh_port", "local SSH port")) . ':</label>
                                            <div class="col-md-3 col-sm-3 col-xs-12">
                                                <input name="file_server_direct_ssh_port" id="file_server_direct_ssh_port_2" type="text" placeholder="22" value="' . adminFunctions::makeSafe($file_server_direct_ssh_port) . '" class="form-control"/>
                                                    <p class="text-muted">Normally port 22.</p>
                                            </div>
                                        </div>';
$result['html'] .= '                    <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("local_server_direct_ssh_username", "local SSH username")) . ':</label>
                                            <div class="col-md-5 col-sm-5 col-xs-12">
                                                <input name="file_server_direct_ssh_username" id="file_server_direct_ssh_username_2" placeholder="user" type="text" value="' . adminFunctions::makeSafe($file_server_direct_ssh_username) . '" class="form-control"/>
                                                <p class="text-muted">Root equivalent user.</p>
                                            </div>
                                        </div>';
$result['html'] .= '                    <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("local_server_direct_ssh_password", "local SSH password")) . ':</label>
                                            <div class="col-md-5 col-sm-5 col-xs-12">
                                                <input name="file_server_direct_ssh_password" id="file_server_direct_ssh_password_2" type="password" value="" class="form-control"/>
                                                <p class="text-muted">Leave blank to keep existing value, if updating.</p>
                                            </div>
                                        </div>';
$result['html'] .= '                    </span>';



$result['html'] .= '                    <span class="directElements serverAccessWrapper" style="display: none;">';
$result['html'] .= '                    <div class="x_title">
                                            <h2>' . UCWords(adminFunctions::t("file_server_ssh_details", "file server SSH details")) . ':</h2>
                                            <div class="clearfix"></div>
                                        </div>';
$result['html'] .= '                    <p>The following information should be filled in if you\'re using the media converter plugin or archive manager. If you have openssl_encrypt() functions available within your server PHP setup, these details will be encrypted in your database using AES256. In a future release we\'ll be able to use these details to automatically create and upgrade your file servers.</p>';
$result['html'] .= '                    <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("file_server_direct_ip_address", "file server ip address")) . ':</label>
                                            <div class="col-md-5 col-sm-5 col-xs-12">
                                                <input name="file_server_direct_ip_address" id="file_server_direct_ip_address" placeholder="i.e. 124.194.125.34" type="text" value="' . adminFunctions::makeSafe($file_server_direct_ip_address) . '" class="form-control"/>
                                            </div>
                                        </div>';
$result['html'] .= '                    <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("file_server_direct_ssh_port", "server SSH port")) . ':</label>
                                            <div class="col-md-3 col-sm-3 col-xs-12">
                                                <input name="file_server_direct_ssh_port" id="file_server_direct_ssh_port" type="text" placeholder="22" value="' . adminFunctions::makeSafe($file_server_direct_ssh_port) . '" class="form-control"/>
                                                <p class="text-muted">Normally port 22.</p>
                                            </div>
                                        </div>';
$result['html'] .= '                    <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("file_server_direct_ssh_username", "server SSH username")) . ':</label>
                                            <div class="col-md-5 col-sm-5 col-xs-12">
                                                <input name="file_server_direct_ssh_username" id="file_server_direct_ssh_username" placeholder="user" type="text" value="' . adminFunctions::makeSafe($file_server_direct_ssh_username) . '" class="form-control"/>
                                                <p class="text-muted">Root equivalent user.</p>
                                            </div>
                                        </div>';
$result['html'] .= '                    <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("file_server_direct_ssh_password", "server SSH password")) . ':</label>
                                            <div class="col-md-5 col-sm-5 col-xs-12">
                                                <input name="file_server_direct_ssh_password" id="file_server_direct_ssh_password" type="password" value="" class="form-control"/>
                                                <p class="text-muted">Leave blank to keep existing value, if updating.</p>
                                            </div>
                                        </div>';
$result['html'] .= '                    </span>';

$result['html'] .= '                    <span class="directElements" style="display: none;">';
$result['html'] .= '                    <div class="x_title">
                                            <h2>' . UCWords(adminFunctions::t("file_server_direct_install", "Direct File Server Install")) . ':</h2>
                                            <div class="clearfix"></div>
                                        </div>';
$result['html'] .= '                    <p>Direct file servers require additional setup on either a vps or dedicated server.</p>';
$result['html'] .= '                    <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("file_server_setup", "file server setup")) . ':</label>
                                            <div class="col-md-9 col-sm-9 col-xs-12">
                                                Direct file server requirements: PHP5.6+, Apache Mod Rewrite or Nginx, remote access to your MySQL database.<br/><br/>
                                                So that your direct file server can receive the uploads and process downloads, it needs a copy of the full codebase installed. Upload all the files from your main site (' . _CONFIG_SITE_HOST_URL . ') to your new file server. This includes any plugin files within the plugin folder.<br/><br/>
                                                Once uploaded, replace the /_config.inc.php file on the new file server with the one listed below. Set your database password in the file (_CONFIG_DB_PASS). We\'ve removed it for security.<br/><br/>
                                                <ul class="adminList"><li><a id="configLink" href="server_manage_direct_get_config_file.php?fileName=_config.inc.php" style="text-decoration: underline;">_config.inc.php</a></li></ul><br/>
                                                In addition, if you\'re using Apache, replace the \'.htaccess\' on the file server with the one listed below.<br/><br/>
                                                <ul class="adminList"><li><a id="htaccessLink" href="server_manage_direct_get_config_file.php?fileName=.htaccess&REWRITE_BASE=/" style="text-decoration: underline;">.htaccess</a></li></ul><br/>
                                                For Nginx users, set your rules to the same as the main server. See /___NGINX_RULES.txt for details.<br/><br/>
                                                Ensure the following folders are CHMOD 755 (or 777 depending on your host) on this file server:<br/><br/>
                                                <ul class="adminList">
                                                    <li>/files/</li>
                                                    <li>/core/cache/</li>
                                                    <li>/core/logs/</li>
                                                    <li>/plugins/</li>
                                                </ul>
                                            </div>
                                        </div>';
$result['html'] .= '                    </span>';

$result['html'] .= '                    <span class="flysystemWrapper" style="display: none;">';
$result['html'] .= '                    </span>';
$result['html'] .= '                </div>';

$result['html'] .= '                <div role="tabpanel" class="tab-pane fade" id="tab_content2" aria-labelledby="profile-tab">
                                        <div class="x_title">
                                            <h2>CDN Support:</h2>
                                            <div class="clearfix"></div>
                                        </div>';
$result['html'] .= '                    <p>You can use Content Delivery Networks (CDNs) such as Stackpath or Akamai to handle image previews, file icons and other static assets. File downloads can not be handled by CDNs however users will see a big performance improvement using the file manager if this is set.</p>';
$result['html'] .= '                    <p>CDNs work by caching a copy of the requested file on their own servers, then sending that to the user from a server closer to their physical location.<br/><br/></p>';
$result['html'] .= '                    <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("cdn_url", "cdn url")) . ':</label>
                                            <div class="col-md-9 col-sm-9 col-xs-12">
                                                <input name="cdn_url" id="cdn_url" type="text" value="' . adminFunctions::makeSafe($cdn_url) . '" class="form-control"/>Ensure you\'ve pointed the CDN url at this file server url. Exclude the https or http in the url. For Flysystem type servers this should be the main domain name of your site. Leave blank to disable.
                                            </div>
                                        </div>';
$result['html'] .= '                </div>';

$result['html'] .= '                <div role="tabpanel" class="tab-pane fade" id="tab_content3" aria-labelledby="profile-tab">
                                        <div class="x_title">
                                            <h2>Storage Options:</h2>
                                            <div class="clearfix"></div>
                                        </div>';
$result['html'] .= '                    <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("max_storage_bytes", "max storage (bytes)")) . ':</label>
                                            <div class="col-md-9 col-sm-9 col-xs-12">
                                                <input name="max_storage_space" id="max_storage_space" type="text" value="' . adminFunctions::makeSafe($max_storage_space) . '" class="form-control" placeholder="2199023255552 = 2TB"/>&nbsp;bytes. Use zero for unlimited.
                                            </div>
                                        </div>';
$result['html'] .= '                    <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">' . UCWords(adminFunctions::t("server_priority", "server priority")) . ':</label>
                                            <div class="col-md-9 col-sm-9 col-xs-12">
                                                <input name="server_priority" id="server_priority" type="text" value="' . adminFunctions::makeSafe($server_priority) . '" class="form-control"/>&nbsp;A number. In order from lowest. 0 to ignore.<br/><br/>- Use for multiple servers when others are full. So when server with priority of 1 is full, server<br/>with priority of 2 will be used next for new uploads. 3 next and so on. "Server selection method"<br/>must be set to "Until Full" to enable this functionality.
                                            </div>
                                        </div>';
$result['html'] .= '                </div>'
        . '                     </div>
                            </div>
                        </div>
                    </div>';

$result['html'] .= '</form>';

echo json_encode($result);
exit;
