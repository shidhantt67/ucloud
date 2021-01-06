<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'API Testing Tool');
define('ADMIN_SELECTED_PAGE', 'api');
define('ADMIN_SELECTED_SUB_PAGE', 'api_test_framework');

// allow some time to run
set_time_limit(60 * 60);

// includes and security
include_once('_local_auth.inc.php');

// list of actions and params
$actions = array();
if(SITE_CONFIG_API_AUTHENTICATION_METHOD == 'Account Access Details')
{
    $actions['/authorize'] = array('username' => $Auth->username, 'password' => '');
}
else
{
    $actions['/authorize'] = array('key1' => '', 'key2' => '');
}
$actions['/disable_access_token'] = array('access_token' => '', 'account_id' => '');
$actions['/account/info'] = array('access_token' => '', 'account_id' => '');
$actions['/account/package'] = array('access_token' => '', 'account_id' => '');
$actions['/account/create'] = array('access_token' => '', 'username' => '', 'password' => '', 'email' => '', 'package_id' => '', 'status' => '', 'title' => '', 'firstname' => '', 'lastname' => '', 'paid_expiry_date' => '');
$actions['/account/edit'] = array('access_token' => '', 'account_id' => '', 'password' => '', 'email' => '', 'package_id' => '', 'status' => '', 'title' => '', 'firstname' => '', 'lastname' => '', 'paid_expiry_date' => '');
$actions['/account/delete'] = array('access_token' => '', 'account_id' => '');
$actions['/file/upload'] = array('access_token' => '', 'account_id' => '', 'upload_file' => '', 'folder_id' => '');
$actions['/file/download'] = array('access_token' => '', 'account_id' => '', 'file_id' => '');
$actions['/file/info'] = array('access_token' => '', 'account_id' => '', 'file_id' => '');
$actions['/file/edit'] = array('access_token' => '', 'account_id' => '', 'file_id' => '', 'filename' => '', 'fileType' => '', 'folder_id' => '');
$actions['/file/delete'] = array('access_token' => '', 'account_id' => '', 'file_id' => '');
$actions['/file/move'] = array('access_token' => '', 'account_id' => '', 'file_id' => '', 'new_parent_folder_id' => '');
$actions['/file/copy'] = array('access_token' => '', 'account_id' => '', 'file_id' => '', 'copy_to_folder_id' => '');
$actions['/folder/create'] = array('access_token' => '', 'account_id' => '', 'folder_name' => '', 'parent_id' => '', 'is_public' => '', 'access_password' => '');
$actions['/folder/listing'] = array('access_token' => '', 'account_id' => '', 'parent_folder_id' => '');
$actions['/folder/info'] = array('access_token' => '', 'account_id' => '', 'folder_id' => '');
$actions['/folder/edit'] = array('access_token' => '', 'account_id' => '', 'folder_id' => '', 'folder_name' => '', 'parent_id' => '', 'is_public' => '', 'access_password' => '');
$actions['/folder/delete'] = array('access_token' => '', 'account_id' => '', 'folder_id' => '');
$actions['/folder/move'] = array('access_token' => '', 'account_id' => '', 'folder_id' => '', 'new_parent_folder_id' => '');
$actions['/package/listing'] = array('access_token' => '');

// page header
include_once('_header.inc.php');
?>

<script>
$(document).ready(function(){
    rebuildApiForm();
});

var replacementAccessToken = '';
var replacementAccountId = '';
var lastUrl = '';
var lastAction = '';
function rebuildApiForm()
{
    params = jQuery.parseJSON($('#api_action option:selected').attr('data-params'));
    newForm = '';
    for(i in params)
    {
        newForm += '<div class="form-group">';
        newForm += '    <label class="control-label col-md-3 col-sm-3 col-xs-12" for="confirm_password" style="text-transform: none;">'+i+':</span>';
        newForm += '    </label>';
        newForm += '    <div class="col-md-3 col-sm-3 col-xs-12">';
        
        newForm += '        <input id="'+i+'" name="'+i+'" class="form-control api_params" type="';
        if(i == 'password')
        {
            newForm += 'password';
        }
        else if(i == 'upload_file')
        {
            newForm += 'file';
        }
        else
        {
            newForm += 'text';
        }
        newForm += '" value="'+replaceParam(i, params[i])+'"/>';
        
        
        newForm += '    </div>';
        newForm += '</div>';
    }
    
    $('#appendedForm').html(newForm);
}

function replaceParam(paramName, initialValue)
{
    if(paramName == 'access_token')
    {
        return replacementAccessToken;
    }
    else if(paramName == 'account_id')
    {
        return replacementAccountId;
    }
    
    return initialValue;
}

function submitApiRequest()
{
    apiUrl = "<?php echo apiv2::getApiUrl(); ?>";
    apiUrl += $('#api_action option:selected').val().substring(1);
    lastUrl = apiUrl;
    lastAction = $('#api_action option:selected').val();
    
    apiParams = {};
    $('.api_params').each(function(){
        apiParams[$(this).attr('id')] = $(this).val();
    });
    
    showRequest(apiUrl, apiParams);
    setResponseLoading();
    
    // find form
    var form = $('#testForm');
    
    // send request
    $.ajax({
        method: "POST",
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        url: apiUrl,
        data: new FormData($(form)[0])
    })
    .done(function(msg) {
        setSuccessResponse(msg);
        // store the access_token and account_id
        if(typeof(msg['data']['access_token']) != 'undefined')
        {
            replacementAccessToken = msg['data']['access_token'];
            replacementAccountId = msg['data']['account_id'];
        }
    })
    .fail(function(msg) {
        setErrorResponse(msg.responseText);
    });
}

function showRequest(apiUrl, apiParams)
{
    $('.request-content').show();
    $('.request-content .literal-block').html(apiUrl+"\n\t?"+jQuery.param(apiParams, null));
}

function hideResponseBoxes()
{
    $('.response-error').hide();
    $('.response-success').hide();
}

function setResponseLoading()
{
    hideResponseBoxes();
    $('.response-success .literal-block').html('Loading...');
    $('.response-success').show();
}

function setSuccessResponse(jsonText)
{
    hideResponseBoxes();
    $('.response-success .literal-block').html(htmlEncode(JSON.stringify(jsonText, null, '\t')));
    $('.response-success').show();
}

function setErrorResponse(responseText)
{
    hideResponseBoxes();
    $('.response-error .x_content').html("<div class='alert alert-danger'>Error: Failed finding url: "+lastUrl+" "+htmlEncode(responseText)+"</div>");
    $('.response-error').show();
}

function htmlEncode(value)
{
    return $('<div/>').text(value).html();
}
</script>

<div class="right_col" role="main">
    <div class="">
        <div class="page-title">
            <div class="title_left">
                <h3>API Testing Tool</h3>
            </div>
        </div>
        <div class="clearfix"></div>

        <?php
        if(COUNT($deleteRs))
        {
            echo "<div class='row'><div class='col-md-12 col-sm-12 col-xs-12'><div class='x_panel'>";
            echo "<div class='x_title'><h2>Result</h2><div class='clearfix'></div></div>";
            echo "<div class='x_content'>" . implode("<br/>", $deleteRs) . '</div>';
            echo "</div></div></div>";
        }
        ?>
        <?php echo adminFunctions::compileNotifications(); ?>
        
        <div class="row request-content" style="display: none;">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>Request</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <pre class="literal-block"></pre>
                    </div>
                </div>
            </div>
        </div>

        <div class="row response-success" style="display: none;">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>JSON Response</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <pre class="literal-block"></pre>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row response-error" style="display: none;">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>Error 404</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <form action="#" method="POST" id="testForm" class="form-horizontal form-label-left" enctype="multipart/form-data" onSubmit="submitApiRequest(); return false;">
                    <div class="x_panel">
                        <div class="x_title">
                            <h2>Select Action</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <p>Select the API action below. Ensure you've initially generated an access key for your request by submitting '/authorize' below.</p>
                            <br/>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="api_action">Action:</span>
                                </label>
                                <div class="col-md-3 col-sm-3 col-xs-12">
                                    <select id="api_action" name="api_action" class="form-control" required="required" onChange="rebuildApiForm(); return false;">
                                        <?php foreach($actions AS $action => $params): ?>
                                        <option value="<?php echo adminFunctions::makeSafe($action); ?>" data-params="<?php echo adminFunctions::makeSafe(json_encode($params, true)); ?>"><?php echo adminFunctions::makeSafe($action); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <span id="appendedForm"></span>
                            
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                    <button type="submit" class="btn btn-primary">Submit API Request</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input name="submitted" type="hidden" value="1"/>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>