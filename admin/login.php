<?php
define('ADMIN_IGNORE_LOGIN', true);
include_once('_local_auth.inc.php');
include_once('_header_login.inc.php');

// check for openssl, required for login
if(!extension_loaded('openssl'))
{
    adminFunctions::setError(t("openssl_not_found", "Openssl functions not found within PHP, please ask support to install and try again."));
}

// if the user is already logged in but not an admin, display an error
if($Auth->loggedIn()) {
    if($Auth->hasAccessLevel(20) === false) {
        adminFunctions::setError(t("admin_account_required", "Admin only users are permitted to access this area, your login attempt has been recorded."));
    }
}

// login user, this is a non-ajax fallback so rarely used
if ((int) $_REQUEST['submitme'])
{
    // clear any expired IPs
    bannedIP::clearExpiredBannedIps();

    // do login
    $loginUsername = trim($_REQUEST['username']);
    $loginPassword = trim($_REQUEST['password']);

    // check user isn't banned from logging in
    $bannedIp = bannedIP::getBannedIPData();
    if ($bannedIp)
    {
        if ($bannedIp['banType'] == 'Login')
        {
            adminFunctions::setError(t("login_ip_banned", "You have been temporarily blocked from logging in due to too many failed login attempts. Please try again [[[EXPIRY_TIME]]].", array('EXPIRY_TIME' => ($bannedIp['banExpiry'] != null ? coreFunctions::formatDate($bannedIp['banExpiry']) : t('later', 'later')))));
        }
    }

    // initial validation
    if (adminFunctions::isErrors() == false)
    {
        if (!strlen($loginUsername))
        {
            // log failure
            Auth::logFailedLoginAttempt(coreFunctions::getUsersIPAddress(), $loginUsername);

            adminFunctions::setError(t("please_enter_your_username", "Please enter your username"));
        }
        elseif (!strlen($loginPassword))
        {
            // log failure
            Auth::logFailedLoginAttempt(coreFunctions::getUsersIPAddress(), $loginUsername);

            adminFunctions::setError(t("please_enter_your_password", "Please enter your password"));
        }
    }
    
    // check captcha
    if ((!adminFunctions::isErrors()) && (SITE_CONFIG_CAPTCHA_LOGIN_SCREEN_ADMIN == 'yes'))
    {
        if (!isset($_REQUEST['g-recaptcha-response']))
        {
            adminFunctions::setError(t("invalid_captcha123", "Captcha confirmation text is invalid."));
        }
        else
        {
            $resp = coreFunctions::captchaCheck($_REQUEST["g-recaptcha-response"]);
            if ($resp == false)
            {
                adminFunctions::setError(t("invalid_captcha", "Captcha confirmation text is invalid."));
            }
        }
    }

    if (adminFunctions::isErrors() == false)
    {
        $rs = $Auth->login($loginUsername, $loginPassword, true);
        if ($rs)
        {
            // successful login
            coreFunctions::redirect(ADMIN_WEB_ROOT.'/index.php');
        }
        else
        {
            // login failed
            adminFunctions::setError(t("username_and_password_is_invalid", "Your username and password are invalid"));
        }
    }
}
?>

<body class="login">
    <div>
        <div class="login_wrapper">
            <div class="animate form login_form">
                <section class="login_content">
                    <form method="POST" action="<?php echo ADMIN_WEB_ROOT; ?>/login.php">
                        <h1><?php echo UCWords(adminFunctions::t("admin_login", "admin login")); ?></h1>

                        <?php
                        if($_REQUEST['error'])
                        {
                            adminFunctions::setError("Incorrect login details or your login has expired, please try again.");
                        }
                        echo adminFunctions::compileErrorHtml();
                        ?>

                        <div>
                            <input name="username" type="text" class="form-control" placeholder="<?php echo adminFunctions::t("username", "username"); ?>" required="" autofocus="" value="<?php echo (_CONFIG_DEMO_MODE == true)?'admin':''; ?>"/>
                        </div>
                        <div>
                            <input name="password" type="password" class="form-control" placeholder="<?php echo adminFunctions::t("password", "password"); ?>" required="" value="<?php echo (_CONFIG_DEMO_MODE == true)?'password':''; ?>"/>
                        </div>
                        <?php if (SITE_CONFIG_CAPTCHA_LOGIN_SCREEN_ADMIN == 'yes'): ?>
                            <div style="padding-left: 21px; padding-bottom: 16px; overflow: hidden;">
                                <?php echo coreFunctions::outputCaptcha(); ?>
                            </div>
                        <?php endif; ?>

                        <div>
                            <button type="submit" value="<?php echo adminFunctions::t("login", "login"); ?>" class="btn btn-default submit"><?php echo adminFunctions::t("login", "login"); ?></button>
                            <a href="<?php echo WEB_ROOT . '/forgot_password.html'; ?>"><?php echo UCWords(t("forgot_password", "forgot password")); ?>?</a>
                        </div>

                        <div class="clearfix"></div>

                        <div class="separator">
                            <p class="change_link"><?php echo adminFunctions::t('admin_login_all_logins_records', 'All logins are recorded. Your IP address: [[[IP_ADDRESS]]].', array('IP_ADDRESS' => adminFunctions::getUsersIPAddress())); ?></p>
                            <div class="clearfix"></div>
                            <div>
                                <p>&copy; <?php echo date('Y'); ?> <?php echo adminFunctions::makeSafe(SITE_CONFIG_SITE_NAME); ?>. <?php echo adminFunctions::t('admin_login_all_rights_reserved', 'All Rights Reserved'); ?>.</p>
                            </div>
                        </div>

                        <input id="submitme" name="submitme" value="1" type="hidden"/>
                        <input id="version" name="version" value="<?php echo adminFunctions::makeSafe(_CONFIG_SCRIPT_VERSION); ?>" type="hidden"/>
                    </form>
                </section>
            </div>
        </div>
    </div>
</body>

<?php
include_once('_footer_login.inc.php');
?>