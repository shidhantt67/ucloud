<?php
// initial constants
define('ADMIN_SELECTED_PAGE', 'users');
define('ADMIN_SELECTED_SUB_PAGE', 'user_manage');

// includes and security
include_once('_local_auth.inc.php');

// load user details
$userId = (int) $_REQUEST['id'];
$user = $db->getRow("SELECT * FROM users WHERE id = " . (int) $userId . " LIMIT 1");
if(!$user)
{
    adminFunctions::redirect('user_manage.php?error=' . urlencode('There was a problem loading the user details.'));
}
define('ADMIN_PAGE_TITLE', 'Edit User: \'' . $user['username'] . '\'');

// account types
$accountTypeDetails = $db->getRows('SELECT id, level_id, label FROM user_level WHERE id > 0 ORDER BY level_id ASC');

// account status
$accountStatusDetails = array('active', 'pending', 'disabled', 'suspended');

// user titles
$titleItems = array('Mr', 'Ms', 'Mrs', 'Miss', 'Miss', 'Dr');

// load all file servers
$sQL = "SELECT id, serverLabel FROM file_server ORDER BY serverLabel";
$serverDetails = $db->getRows($sQL);

// prepare variables
$username = $user['username'];
$password = '';
$confirm_password = '';
$account_status = $user['status'];
$account_type = $user['level_id'];
$expiry_date = (strlen($user['paidExpiryDate']) && ($user['paidExpiryDate'] != '0000-00-00 00:00:00')) ? date('d/m/Y', strtotime($user['paidExpiryDate'])) : '';
$title = $user['title'];
$first_name = $user['firstname'];
$last_name = $user['lastname'];
$email_address = $user['email'];
$storage_limit = $user['storageLimitOverride'];
$remainingBWDownload = $user['remainingBWDownload'];
$upload_server_override = $user['uploadServerOverride'];

// setup keys
$key1 = '';
$key2 = '';
$accountAPIKeys = $db->getRow('SELECT key_public, key_secret FROM apiv2_api_key WHERE user_id = :user_id LIMIT 1', array('user_id' => $user['id']));
if($accountAPIKeys)
{
    $key1 = $accountAPIKeys['key_public'];
    $key2 = $accountAPIKeys['key_secret'];
}

// handle page submissions
if(isset($_REQUEST['submitted']))
{
    // get variables
    $user_password = trim($_REQUEST['user_password']);
    $confirm_password = trim($_REQUEST['confirm_password']);
    $account_status = trim($_REQUEST['account_status']);
    $account_type = trim($_REQUEST['account_type']);
    $expiry_date = trim($_REQUEST['expiry_date']);
    $title = trim($_REQUEST['title']);
    $first_name = trim($_REQUEST['first_name']);
    $last_name = trim($_REQUEST['last_name']);
    $email_address = trim(strtolower($_REQUEST['email_address']));
    $storage_limit = trim($_REQUEST['storage_limit']);
    $storage_limit = str_replace(array(',', ' ', '.', '(', ')', '-'), '', $storage_limit);
    $remainingBWDownload = trim($_REQUEST['remainingBWDownload']);
    $remainingBWDownload = str_replace(array(',', ' ', '.', '(', ')', '-'), '', $remainingBWDownload);
    if((int) $remainingBWDownload == 0)
    {
        $remainingBWDownload = null;
    }
    $dbExpiryDate = '';
    $upload_server_override = trim($_REQUEST['upload_server_override']);
    $uploadedAvatar = null;
    if((isset($_FILES['avatar']['tmp_name'])) && (strlen($_FILES['avatar']['tmp_name'])))
    {
        $uploadedAvatar = $_FILES['avatar'];
    }
    $removeAvatar = false;
    if((isset($_REQUEST['removeAvatar'])) && ((int) $_REQUEST['removeAvatar'] == 1))
    {
        $removeAvatar = true;
    }
    
    // pickup api keys
    $key1 = trim($_REQUEST['key1']);
    $key2 = trim($_REQUEST['key2']);

    // validate submission
    if(_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    elseif(strlen($first_name) == 0)
    {
        adminFunctions::setError(adminFunctions::t("enter_first_name"));
    }
    elseif(strlen($last_name) == 0)
    {
        adminFunctions::setError(adminFunctions::t("enter_last_name"));
    }
    elseif(strlen($email_address) == 0)
    {
        adminFunctions::setError(adminFunctions::t("enter_email_address"));
    }
    elseif(validation::validEmail($email_address) == false)
    {
        adminFunctions::setError(adminFunctions::t("entered_email_address_invalid"));
    }
    elseif(strlen($expiry_date))
    {
        // turn into db format
        $exp1 = explode(" ", $expiry_date);
        $exp = explode("/", $exp1[0]);
        if(COUNT($exp) != 3)
        {
            adminFunctions::setError(adminFunctions::t("account_expiry_invalid_dd_mm_yy", "Account expiry date invalid, it should be in the format dd/mm/yyyy"));
        }
        else
        {
            $dbExpiryDate = $exp[2] . '-' . $exp[1] . '-' . $exp[0] . ' 00:00:00';

            // check format
            if(strtotime($dbExpiryDate) == false)
            {
                adminFunctions::setError(adminFunctions::t("account_expiry_invalid_dd_mm_yy", "Account expiry date invalid, it should be in the format dd/mm/yyyy"));
            }
        }
    }

    // check for password
    if(adminFunctions::isErrors() == false)
    {
        if(strlen($user_password))
        {
            if($user_password != $confirm_password)
            {
                adminFunctions::setError(adminFunctions::t("confirmation_password_does_not_match", "Your confirmation password does not match"));
            }
            else
            {
                // check password structure
                $passValid = passwordPolicy::validatePassword($user_password);
                if(is_array($passValid))
                {
                    adminFunctions::setError(implode('<br/>', $passValid));
                }
            }
        }
    }

    if(adminFunctions::isErrors() == false)
    {
        if($uploadedAvatar)
        {
            // check filesize
            $maxAvatarSize = 1024 * 1024 * 10;
            if($uploadedAvatar['size'] > ($maxAvatarSize))
            {
                adminFunctions::setError(adminFunctions::t("account_edit_avatar_is_too_large", "The uploaded image can not be more than [[[MAX_SIZE_FORMATTED]]]", array('MAX_SIZE_FORMATTED' => coreFunctions::formatSize($maxAvatarSize))));
            }
            else
            {
                // make sure it's an image
                $imagesizedata = @getimagesize($uploadedAvatar['tmp_name']);
                if($imagesizedata === FALSE)
                {
                    //not image
                    adminFunctions::setError(adminFunctions::t("account_edit_avatar_is_not_an_image", "Your avatar must be a jpg, png or gif image."));
                }
            }
        }
    }
    
    if(adminFunctions::isErrors() == false)
    {
        if(strlen($key1) || strlen($key2))
        {
            // make sure keys are 64 characters in length
            if((strlen($key1) != 64) || (strlen($key2) != 64))
            {
                adminFunctions::setError(adminFunctions::t("account_api_keys_not_correct_length", "API keys should be 64 characters in length."));
            }
        }
    }

    // add the account
    if(adminFunctions::isErrors() == false)
    {
        // update the user
        $dbUpdate = new DBObject("users", array("level_id", "email", "status", "title", "firstname", "lastname", "paidExpiryDate", "storageLimitOverride", "uploadServerOverride", "remainingBWDownload"), 'id');
        if(strlen($user_password))
        {
            $dbUpdate = new DBObject("users", array("password", "level_id", "email", "status", "title", "firstname", "lastname", "paidExpiryDate", "storageLimitOverride", "uploadServerOverride", "remainingBWDownload"), 'id');
            $dbUpdate->password = Password::createHash($user_password);
        }
        $dbUpdate->level_id = $account_type;
        $dbUpdate->email = $email_address;
        $dbUpdate->status = $account_status;
        $dbUpdate->title = $title;
        $dbUpdate->firstname = $first_name;
        $dbUpdate->lastname = $last_name;
        $dbUpdate->paidExpiryDate = $dbExpiryDate;
        $dbUpdate->storageLimitOverride = strlen($storage_limit) ? $storage_limit : NULL;
        $dbUpdate->uploadServerOverride = (int) $upload_server_override ? (int) $upload_server_override : NULL;
        $dbUpdate->remainingBWDownload = (int) $remainingBWDownload ? (int) $remainingBWDownload : NULL;
        $dbUpdate->id = $userId;
        $dbUpdate->update();

        // save avatar
        $src = null;
        if($uploadedAvatar)
        {
            // convert all images to jpg
            $imgInfo = getimagesize($uploadedAvatar['tmp_name']);
            switch($imgInfo[2])
            {
                case IMAGETYPE_GIF: $src = imagecreatefromgif($uploadedAvatar['tmp_name']);
                    break;
                case IMAGETYPE_JPEG: $src = imagecreatefromjpeg($uploadedAvatar['tmp_name']);
                    break;
                case IMAGETYPE_PNG: $src = imagecreatefrompng($uploadedAvatar['tmp_name']);
                    break;
                default: $src = null;
            }
        }

        // if we've loaded the image store it as jpg
        if(($src) || ($removeAvatar == true))
        {
            ob_start();
            imagejpeg($src, null, 100);
            $imageData = ob_get_contents();
            ob_end_clean();
            $avatarCachePath = 'user/' . (int) $userId . '/profile';

            // delete any existing avatar files including generate cache
            if(file_exists(CACHE_DIRECTORY_ROOT . '/' . $avatarCachePath))
            {
                $files = coreFunctions::getDirectoryListing(CACHE_DIRECTORY_ROOT . '/' . $avatarCachePath);
                if(COUNT($files))
                {
                    foreach($files AS $file)
                    {
                        @unlink($file);
                    }
                }
            }

            if($src)
            {
                // save new file
                cache::saveCacheToFile($avatarCachePath . '/avatar_original.jpg', $imageData);
            }
        }
        
        // update api keys
        $keepSame = $db->getValue('SELECT COUNT(id) AS total FROM apiv2_api_key WHERE key_public = :key_public AND key_secret = :key_secret AND user_id = :user_id LIMIT 1', array(
            'user_id' => (int)$userId,
            'key_public' => $key1,
            'key_secret' => $key2,
        ));
        if(!$keepSame)
        {
            // delete any existing keys for the user
            $db->query('DELETE FROM apiv2_api_key WHERE user_id = :user_id LIMIT 1', array(
                'user_id' => (int)$userId,
            ));

            // add the new keys
            if(strlen($key1) && strlen($key2))
            {
                $db->query('INSERT INTO apiv2_api_key (key_public, key_secret, user_id, date_created) VALUES (:key_public, :key_secret, :user_id, NOW())', array(
                    'user_id' => (int)$userId,
                    'key_public' => $key1,
                    'key_secret' => $key2,
                ));
            }
        }

        // append any plugin includes
        pluginHelper::includeAppends('admin_user_edit.inc.php');
        
        // redirect
        adminFunctions::redirect('user_manage.php?se=1');
    }
}

// check for existing avatar
$hasAvatar = false;
$avatarCachePath = 'user/' . (int) $userId . '/profile/avatar_original.jpg';
if(cache::checkCacheFileExists($avatarCachePath))
{
    $hasAvatar = true;
}

// page header
include_once('_header.inc.php');
?>

<script>
    $(function () {
        $('#expiry_date').daterangepicker({
            singleDatePicker: true,
            calender_style: "picker_1",
            autoUpdateInput: false,
            locale: {
                format: 'DD/MM/YYYY'
            }
        }, function (chosen_date) {
            $('#expiry_date').val(chosen_date.format('DD/MM/YYYY'));
        });
    });

    function checkExpiryDate()
    {
        userType = $('#account_type option:selected').val();
        if (userType > 1)
        {
            // default to 1 year
            $('#expiry_date').val('<?php echo date('d/m/Y', strtotime('+1 year')); ?>');
        }
    }
    
    function createRandomKey(eleId)
    {
        var text = "";
        var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

        for( var i=0; i < 64; i++ )
            text += possible.charAt(Math.floor(Math.random() * possible.length));

        $('#'+eleId).val(text);
    }
</script>

<div class="right_col" role="main">
    <div class="">
        <div class="page-title">
            <div class="title_left">
                <h3>User Details</h3>
            </div>
        </div>
        <div class="clearfix"></div>

<?php echo adminFunctions::compileNotifications(); ?>

        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <form action="user_edit.php" method="POST" class="form-horizontal form-label-left" enctype="multipart/form-data">
                    <div class="x_panel">
                        <div class="x_title">
                            <h2>Account Details</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <p>Information about the account.</p>
                            <br/>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="username">Username:
                                </label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <input id="username" name="username" class="form-control" required="required" type="text" value="<?php echo adminFunctions::makeSafe($username); ?>" READONLY/>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="account_status">Account Status:
                                </label>
                                <div class="col-md-4 col-sm-4 col-xs-12">
                                    <select name="account_status" id="account_status" class="form-control">
                                        <?php
                                        foreach($accountStatusDetails AS $accountStatusDetail)
                                        {
                                            echo '<option value="' . $accountStatusDetail . '"';
                                            if(($account_status) && ($account_status == $accountStatusDetail))
                                            {
                                                echo ' SELECTED';
                                            }
                                            echo '>' . UCWords($accountStatusDetail) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="account_type">Account Type:
                                </label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <select name="account_type" id="account_type" class="form-control" onChange="checkExpiryDate();">
                                        <?php
                                        foreach($accountTypeDetails AS $accountTypeDetail)
                                        {
                                            echo '<option value="' . $accountTypeDetail['id'] . '"';
                                            if(($account_type) && ($account_type == $accountTypeDetail['id']))
                                            {
                                                echo ' SELECTED';
                                            }
                                            echo '>' . UCWords($accountTypeDetail['label']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group paid_account_expiry">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="expiry_date">Paid Expiry Date:
                                </label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <input id="expiry_date" name="expiry_date" type="text" class="form-control" value="<?php echo adminFunctions::makeSafe($expiry_date); ?>"/>
                                    <span class="text-muted">(dd/mm/yyyy, maximum 19th January 2038)</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="storage_limit">Filesize Storage Limit:
                                </label>
                                <div class="col-md-5 col-sm-5 col-xs-12">
                                    <div class="input-group">
                                        <input id="storage_limit" name="storage_limit" placeholder="i.e. 1073741824" type="text" class="form-control" value="<?php echo adminFunctions::makeSafe($storage_limit); ?>"/>
                                        <span class="input-group-addon">bytes</span>
                                    </div>
                                    <span class="text-muted">Optional in bytes. Overrides account type limits. 1073741824 = 1GB. Use zero for unlimited.</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="remainingBWDownload">Download Allowance:
                                </label>
                                <div class="col-md-5 col-sm-5 col-xs-12">
                                    <div class="input-group">
                                        <input id="remainingBWDownload" name="remainingBWDownload" placeholder="i.e. 1073741824" type="text" class="form-control" value="<?php echo adminFunctions::makeSafe($remainingBWDownload); ?>"/>
                                        <span class="input-group-addon">bytes</span>
                                    </div>
                                    <span class="text-muted">Optional in bytes. Generally left blank. Use zero for unlimited.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="x_panel">
                        <div class="x_title">
                            <h2>User Details</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <p>Details about the user.</p>
                            <br/>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="title">Title:
                                </label>
                                <div class="col-md-4 col-sm-4 col-xs-12">
                                    <select name="title" id="title" class="form-control">
                                        <?php
                                        foreach($titleItems AS $titleItem)
                                        {
                                            echo '<option value="' . $titleItem . '"';
                                            if(($title) && ($title == $titleItem))
                                            {
                                                echo ' SELECTED';
                                            }
                                            echo '>' . UCWords($titleItem) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first_name">First Name:
                                </label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <input id="first_name" name="first_name" type="text" class="form-control" value="<?php echo adminFunctions::makeSafe($first_name); ?>"/>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="last_name">Last Name:
                                </label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <input id="last_name" name="last_name" type="text" class="form-control" value="<?php echo adminFunctions::makeSafe($last_name); ?>"/>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="email_address">Email Address:
                                </label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <input id="email_address" name="email_address" type="text" class="form-control" value="<?php echo adminFunctions::makeSafe($email_address); ?>"/>
                                </div>
                            </div>                            
                        </div>
                    </div>

                    <div class="x_panel">
                        <div class="x_title">
                            <h2>Reset Password</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <p>Reset the user password. Leave blank to keep the existing.</p>
                            <br/>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="password">Password:
                                </label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <input id="password" name="user_password" class="form-control" type="password" value="<?php echo adminFunctions::makeSafe($user_password); ?>"/>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="confirm_password">Confirm Password:
                                </label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <input id="confirm_password" name="confirm_password" class="form-control" type="password" value="<?php echo adminFunctions::makeSafe($confirm_password); ?>"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="x_panel">
                        <div class="x_title">
                            <h2>Account Avatar</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <p>If an account avatar is supported on the site theme, update it here.</p>
                            <br/>

                            <div class="form-group">
                                <label for="avatar" class="control-label col-md-3 col-sm-3 col-xs-12">Select File (jpg, png or gif)</label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <input type="file" class="form-control" id="avatar" name="avatar" placeholder="Select File (jpg, png or gif)">
<?php if($hasAvatar == true): ?>
                                        <br/>
                                        <img class="img-square settings-avatar pull-right" src="<?php echo ADMIN_WEB_ROOT; ?>/ajax/account_view_avatar.ajax.php?userId=<?php echo $userId; ?>&width=70&amp;height=70">
                                        <div class="checkbox pull-left">
                                            <label>
                                                <input type="checkbox" id="removeAvatar" name="removeAvatar" value="1"/>Remove avatar
                                            </label>
                                        </div>
<?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="x_panel">
                        <div class="x_title">
                            <h2>File Upload API Keys</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <p>Set or generate API keys.</p>
                            <br/>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="key1">Key 1:
                                </label>
                                <div class="col-md-5 col-sm-5 col-xs-12">
                                    <div class="input-group">
                                        <input id="key1" name="key1" class="form-control" type="text" value="<?php echo adminFunctions::makeSafe($key1); ?>"/>
                                        <span class="input-group-btn">
                                            <button class="btn btn-secondary" type="button" onClick="createRandomKey('key1'); return false;">Generate</button>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="key2">Key 2:
                                </label>
                                <div class="col-md-5 col-sm-5 col-xs-12">
                                    <div class="input-group">
                                        <input id="key2" name="key2" class="form-control" type="text" value="<?php echo adminFunctions::makeSafe($key2); ?>"/>
                                        <span class="input-group-btn">
                                            <button class="btn btn-secondary" type="button" onClick="createRandomKey('key2'); return false;">Generate</button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="x_panel">
                        <div class="x_title">
                            <h2>Other Options</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <p>Server upload override.</p>
                            <br/>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="upload_server_override">Upload Server Override:
                                </label>
                                <div class="col-md-4 col-sm-4 col-xs-12">
                                    <select name="upload_server_override" id="upload_server_override" class="form-control">
                                        <option value="">- none - (default)</option>
                                        <?php
                                        foreach($serverDetails AS $serverDetail)
                                        {
                                            echo '<option value="' . $serverDetail['id'] . '"';
                                            if(($upload_server_override) && ($upload_server_override == $serverDetail['id']))
                                            {
                                                echo ' SELECTED';
                                            }
                                            echo '>' . $serverDetail['serverLabel'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <span class="text-muted">Useful for testing new servers for a specific user. Leave as 'none' to use the global settings.</span>
                                </div>
                            </div>

                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                    <button type="button" class="btn btn-default" onClick="window.location = 'user_manage.php';">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Update User</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input name="submitted" type="hidden" value="1"/>
                    <input name="id" type="hidden" value="<?php echo $userId; ?>"/>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>