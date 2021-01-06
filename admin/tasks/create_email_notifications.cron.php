<?php

/*
 * Title: Create Email Notifications Script
 * Author: YetiShare.com
 * Period: Run once a day
 * 
 * Description:
 * Script to create email notifications in user accounts about premium expiry.
 *
 * How To Call:
 * On the command line via PHP, like this:
 * php create_email_notifications.cron.php
 * 
 * Configure as a cron like this:
 * 0 1 * * * php /path/to/yetishare/install/admin/tasks/create_email_notifications.cron.php
 */

// setup environment
define('CLI_MODE', true);
define('ADMIN_IGNORE_LOGIN', true);
define('LOCAL_ADMIN_PATH', dirname(dirname(__FILE__)));

// includes and security
include_once(LOCAL_ADMIN_PATH.'/_local_auth.inc.php');

// background task logging
$task = new backgroundTask();
$task->start();

/*
 * DAILY ACCOUNT EXPIRY NOTIFICATIONS
 */
 
// when to send notifications
$accountDowngradeWarningDays = array(1, 2, 4, 7);

// get all accounts reaching paid expiry period
$expiringPaidAccounts = $db->getRows('SELECT id, username, paidExpiryDate, ((UNIX_TIMESTAMP(paidExpiryDate) - UNIX_TIMESTAMP()) / 86400) AS daysUntil FROM users WHERE level_id IN (SELECT id FROM user_level WHERE level_type = \'paid\') AND status = \'active\' AND paidExpiryDate BETWEEN NOW() AND NOW() + INTERVAL '.(int)max($accountDowngradeWarningDays).' DAY');
if($expiringPaidAccounts)
{
    foreach($expiringPaidAccounts AS $expiringPaidAccount)
    {
        // days until
        $daysUntil = ceil($expiringPaidAccount['daysUntil']);
        
        // if within one of our notification days, add notification
        if(in_array($daysUntil, $accountDowngradeWarningDays))
        {
            $content = t('internal_notification_paid_account_expiring', 'Your paid account is expiring in [[[DAYS]]] days. Your inactive files may removed if you do not renew your membership. Click here for more information.', array('DAYS'=>$daysUntil));
            $link = WEB_ROOT.'/upgrade.html';
            internalNotification::add($expiringPaidAccount['id'], $content, $type = 'entypo-attention', $link);
			
			
			$userObj = UserPeer::loadUserById($expiringPaidAccount['id']);
			
			$subject = t('account_expiry_warning_email_subject', 'Your premium account on [[[SITE_NAME]]] will expire in [[[DAYS]]] day(s)', array('SITE_NAME' => SITE_CONFIG_SITE_NAME, 'DAYS' => $daysUntil));

			$replacements   = array(
				'USERNAME' => $userObj->username,
				'SITE_NAME'  => SITE_CONFIG_SITE_NAME,
				'WEB_ROOT'   => WEB_ROOT,
				'DAYS' => $daysUntil
			);
			$defaultContent = "Dear [[[USERNAME]]],<br/><br/>";
			$defaultContent .= "Your premium account on [[[SITE_NAME]]] is about to expire in [[[DAYS]]] day(s). To avoid the account reverting to non-premium ensure you renew your premium membership. You can do this by logging into your account and choose to 'extend' your account.<br/><br/>";
			$defaultContent .= "<strong>Url:</strong> [[[WEB_ROOT]]]<br/><br/>";
			$defaultContent .= "Note that if you do not renew your premium membership, your account limits may be reduced and some of your inactive files automatically removed.<br/><br/>";
			$defaultContent .= "Feel free to contact us if you need any support with your account.<br/><br/>";
			$defaultContent .= "Regards,<br/>";
			$defaultContent .= "[[[SITE_NAME]]] Admin";
			$htmlMsg        = t('account_expiry_warning_email_content', $defaultContent, $replacements);

			coreFunctions::sendHtmlEmail($userObj->email, $subject, $htmlMsg, SITE_CONFIG_DEFAULT_EMAIL_ADDRESS_FROM, strip_tags(str_replace("<br/>", "\n", $htmlMsg)));
        }
    }
}

// background task logging
$task->end();