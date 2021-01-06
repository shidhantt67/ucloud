<?php

// includes and security
include_once('../_local_auth.inc.php');

// load file servers
$fileServers = $db->getRows('SELECT file_server.id, file_server.serverLabel FROM file_server WHERE statusId=2 AND serverType IN (\'local\', \'direct\') ORDER BY serverLabel ASC');
if (COUNT($fileServers) == 0)
{
    $result          = array();
    $result['error'] = false;
    $result['msg']   = '';
    $result['html']  = 'No active servers.';
    echo json_encode($result);
    exit;
}

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

$result['html'] = '';
$result['html'] .= '<form id="moveFilesForm" class="form-horizontal form-label-left input_mask">';
$result['html'] .= '<div class="x_panel">';
$result['html'] .= '    <div class="x_title">';
$result['html'] .= '        <h2>Move Files:</h2>';
$result['html'] .= '        <div class="clearfix"></div>';
$result['html'] .= '    </div>';
$result['html'] .= '    <div class="x_content">';
$result['html'] .= '        <p>Choose which server to move the selected files to. Note that this process adds the item to the file_action queue, so the actual move will happen over the next few minutes. Only \'local\' and \'direct\' servers are currently supported.</p>';
$result['html'] .= '        <p>If you select the current server the file is stored on, it\'ll just be ignored.</p>';

$result['html'] .= '        <div class="form-group top-buffer-20">';
$result['html'] .= '            <label class="control-label col-md-3 col-sm-3 col-xs-12">';
$result['html'] .= '                '.adminFunctions::t("file_server", "File Server").':';
$result['html'] .= '            </label>';
$result['html'] .= '            <div class="col-md-9 col-sm-9 col-xs-12">';
$result['html'] .= '                <select name="server_ids" id="server_ids" class="form-control">';
$result['html'] .= '                    <option value="">- any user type can download this file -</option>';
foreach ($fileServers AS $fileServer)
{
    $result['html'] .= '                <option value="' . $fileServer['id'] . '"';
    $result['html'] .= '                >' . UCWords($fileServer['serverLabel']) . '</option>';
}
$result['html'] .= '            </select>';
$result['html'] .= '        </div>';

$result['html'] .= '    </div>';
$result['html'] .= '</div>';

$result['html'] .= '</form>';

echo json_encode($result);
exit;
