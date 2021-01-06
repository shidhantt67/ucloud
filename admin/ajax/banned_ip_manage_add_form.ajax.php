<?php

// includes and security
include_once('../_local_auth.inc.php');

$gConfigId   = (int) $_REQUEST['gConfigId'];

// prepare result
$result = array();
$result['error'] = false;
$result['msg'] = '';

$result['html']  = '<p style="padding-bottom: 4px;">Use the form below to add the banned IP address.</p>';
$result['html'] .= '<form id="addBannedIPForm" class="form-horizontal form-label-left input_mask">';
$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">IP Address:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <input name="ip_address" id="ip_address" type="text" value="" class="form-control"/>
                        </div>
                    </div>';
$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Ban Type:</label>
                        <div class="col-md-5 col-sm-5 col-xs-12">
                            <select name="ban_type" id="ban_type" class="form-control">
                                <option value="Uploading">Uploading</option>
                                <option value="Whole Site">Whole Site</option>
                                <option value="Login">Login</option>
                            </select>
                        </div>
                    </div>';
$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Ban Expiry Date:</label>
                        <div class="col-md-5 col-sm-5 col-xs-12">
                            <input name="ban_expiry_date" id="ban_expiry_date" type="text" value="" class="form-control"/><p class="text-muted">(dd/mm/yyyy, leave empty for never)</p>
                        </div>
                    </div>';
$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Ban Expiry Time:</label>
                        <div class="col-md-2 col-sm-2 col-xs-12">
                            <select name="ban_expiry_hour" id="ban_expiry_hour" class="form-control">';
for($i = 0; $i < 24; $i++)
{
    $result['html'] .= '        <option value="'.str_pad($i, 2, '0', STR_PAD_LEFT).'">'.str_pad($i, 2, '0', STR_PAD_LEFT).'</option>';
}
$result['html'] .= '        </select><p class="text-muted">(hh:mm)</p></div><div class="col-md-2 col-sm-2 col-xs-12"><select name="ban_expiry_minute" id="ban_expiry_minute" class="form-control">';
for($i = 0; $i < 60; $i++)
{
    $result['html'] .= '        <option value="'.str_pad($i, 2, '0', STR_PAD_LEFT).'">'.str_pad($i, 2, '0', STR_PAD_LEFT).'</option>';
}
$result['html'] .= '        </select>
                        </div>
                    </div>';
$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Ban Notes:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <textarea name="ban_notes" id="ban_notes" class="form-control"></textarea>
                        </div>
                    </div>';
$result['html'] .= '</form>';

echo json_encode($result);
exit;
