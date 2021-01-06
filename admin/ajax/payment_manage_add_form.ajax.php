<?php

// includes and security
include_once('../_local_auth.inc.php');

// get initial data
$users = $db->getRows('SELECT id, username, email FROM users ORDER BY username');
$paymentMethods = array('PayPal', 'Cheque', 'Cash', 'Bank Transfer', 'SMS', 'Other');

// default values
$payment_date = coreFunctions::formatDate(time(), SITE_CONFIG_DATE_TIME_FORMAT);
$description = 'Payment for account upgrade';

// prepare result
$result = array();
$result['error'] = false;
$result['msg'] = '';

$result['html'] .= '
        <form id="editFileFormInner" class="form-horizontal form-label-left input_mask">
            <div class="x_panel">
                <div class="x_content">
                    <div class="x_title">
                        <h2>Log Payment:</h2>
                        <div class="clearfix"></div>
                    </div>';

$result['html'] .= '<p>Use the form below to add an entry into the payments log. Note: This will not upgrade users, you\'ll need to manually do this via the edit user page.</p>';
$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">User:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <select name="user_id" id="user_id" class="form-control">
                                ';
                                $result['html'] .= '<option value="">- select -</option>';
                                foreach($users AS $user)
                                {
                                    $result['html'] .= '<option value="'.$user['id'].'">'.$user['username'].' ('.$user['email'].')</option>';
                                }
                                $result['html'] .= '
                            </select>
                        </div>
                    </div>';
$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Payment Date/Time:</label>
                        <div class="col-md-5 col-sm-5 col-xs-12">
                            <input name="payment_date" id="payment_date" type="text" value="'.$payment_date.'" class="form-control" placeholder="'.$payment_date.'"/>
                        </div>
                    </div>';
$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Payment Amount:</label>
                        <div class="col-md-5 col-sm-5 col-xs-12">
                            <div class="input-group">
                                <span class="input-group-addon">'.SITE_CONFIG_COST_CURRENCY_SYMBOL. '</span>
                                <input name="payment_amount" id="payment_amount" type="text" class="form-control" placeholder="0.00"/>
                            </div>
                        </div>
                    </div>';
$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Description:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <input name="description" id="description" type="text" value="'.$description.'" class="form-control"/>
                        </div>
                    </div>';
$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Payment Method:</label>
                        <div class="col-md-5 col-sm-5 col-xs-12">
                            <select name="payment_method" id="payment_method" class="form-control">
                                ';
                                foreach($paymentMethods AS $paymentMethod)
                                {
                                    $result['html'] .= '<option value="'.validation::safeOutputToScreen($paymentMethod).'">'.validation::safeOutputToScreen($paymentMethod).'</option>';
                                }
                                $result['html'] .= '
                            </select>
                        </div>
                    </div>';
$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Additional Notes:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <textarea name="notes" id="notes" class="form-control"></textarea>
                        </div>
                    </div>';

$result['html'] .= '</div>
                </div>

                <input type="hidden" name="existing_file_id" id="existing_file_id" value="'.(int)$file->id.'"/>
            </form>';

echo json_encode($result);
exit;
