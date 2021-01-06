<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Add New User');
define('ADMIN_SELECTED_PAGE', 'users');
define('ADMIN_SELECTED_SUB_PAGE', 'user_add');

// includes and security
include_once('_local_auth.inc.php');

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
$username = '';
$password = '';
$confirm_password = '';
$account_status = 'active';
$account_type = 1;
$expiry_date = '';
$title = 'Mr';
$first_name = '';
$last_name = '';
$email_address = '';
$storage_limit = '';
$remainingBWDownload = '';
$upload_server_override = '';

// handle page submissions
if(isset($_REQUEST['submitted']))
{
    // get variables
    $username = trim(strtolower($_REQUEST['username']));
    $password = trim($_REQUEST['password']);
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

    // validate submission
    if(_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    elseif((strlen($username) < 6) || (strlen($username) > 20))
    {
        adminFunctions::setError(adminFunctions::t("username_length_invalid"));
    }
    elseif($password != $confirm_password)
    {
        adminFunctions::setError(adminFunctions::t("confirmation_password_does_not_match", "Your confirmation password does not match"));
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

    // check password structure
    if(adminFunctions::isErrors() == false)
    {
        $passValid = passwordPolicy::validatePassword($password);
        if(is_array($passValid))
        {
            adminFunctions::setError(implode('<br/>', $passValid));
        }
    }

    // check email/username doesn't already exist
    if(adminFunctions::isErrors() == false)
    {
        $checkEmail = UserPeer::loadUserByEmailAddress($email_address);
        if($checkEmail)
        {
            // email exists
            adminFunctions::setError(adminFunctions::t("email_address_already_exists", "Email address already exists on another account"));
        }
        else
        {
            $checkUser = UserPeer::loadUserByUsername($username);
            if($checkUser)
            {
                // username exists
                adminFunctions::setError(adminFunctions::t("username_already_exists", "Username already exists on another account"));
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

    // add the account
    if(adminFunctions::isErrors() == false)
    {
        // create the intial record
        $dbInsert = new DBObject("users", array("username", "password", "level_id", "email", "status", "title", "firstname", "lastname", "paidExpiryDate", "storageLimitOverride", "uploadServerOverride", "remainingBWDownload"));
        $dbInsert->username = $username;
        $dbInsert->password = Password::createHash($password);
        $dbInsert->level_id = $account_type;
        $dbInsert->email = $email_address;
        $dbInsert->status = $account_status;
        $dbInsert->title = $title;
        $dbInsert->firstname = $first_name;
        $dbInsert->lastname = $last_name;
        $dbInsert->paidExpiryDate = $dbExpiryDate;
        $dbInsert->storageLimitOverride = strlen($storage_limit) ? $storage_limit : NULL;
        $dbInsert->uploadServerOverride = (int) $upload_server_override ? (int) $upload_server_override : NULL;
        $dbInsert->remainingBWDownload = (int) $remainingBWDownload ? (int) $remainingBWDownload : NULL;
        if(!$dbInsert->insert())
        {
            adminFunctions::setError(adminFunctions::t("error_problem_record"));
        }
        else
        {
            // create default folders
            $defaultUserFolders = trim(SITE_CONFIG_USER_REGISTER_DEFAULT_FOLDERS);
            if(strlen($defaultUserFolders))
            {
                $user = UserPeer::loadUserById($dbInsert->id);
                if($user)
                {
                    $user->addDefaultFolders();
                }
            }

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
            if($src)
            {
                ob_start();
                imagejpeg($src, null, 100);
                $imageData = ob_get_contents();
                ob_end_clean();
                $avatarCachePath = 'user/' . (int) $user->id . '/profile';

                if($src)
                {
                    // save new file
                    cache::saveCacheToFile($avatarCachePath . '/avatar_original.jpg', $imageData);
                }
            }

            adminFunctions::redirect('user_manage.php?sa=1');
        }
    }
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
                <form action="user_add.php" method="POST" class="form-horizontal form-label-left">
                    <div class="x_panel">
                        <div class="x_title">
                            <h2>Login Details</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <p>Enter the details that the user will use to access the site.</p>
                            <br/>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="username">Username:
                                </label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <input id="username" name="username" class="form-control" required="required" type="text" value="<?php echo adminFunctions::makeSafe($username); ?>"/>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="password">Password:
                                </label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <input id="password" name="password" class="form-control" required="required" type="password" value="<?php echo adminFunctions::makeSafe($password); ?>"/>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="confirm_password">Confirm Password:
                                </label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <input id="confirm_password" name="confirm_password" class="form-control" required="required" type="password" value="<?php echo adminFunctions::makeSafe($confirm_password); ?>"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="x_panel">
                        <div class="x_title">
                            <h2>Account Details</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <p>Information about the account.</p>
                            <br/>

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
                            <h2>Account Avatar</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <p>If an account avatar is supported on the site theme, set it here.</p>
                            <br/>

                            <div class="form-group">
                                <label for="avatar" class="control-label col-md-3 col-sm-3 col-xs-12">Select File (jpg, png or gif)</label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <input type="file" class="form-control" id="avatar" name="avatar" placeholder="Select File (jpg, png or gif)">
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
                                    <button type="submit" class="btn btn-primary">Add User</button>
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