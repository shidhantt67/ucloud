<?php

// Stick your DBOjbect subclasses in here (to help keep things tidy).
class User extends DBObject
{

    public function __construct($id = null) {
        parent::__construct('users', array(
            'username',
            'password',
            'lastlogindate',
            'level_id',
            'email',
            'paidExpiryDate',
            'firstname',
            'lastname',
            'title',
            'languageId',
            'storageLimitOverride',
            'privateFileStatistics',
            'uploadServerOverride',
            'apikey',
            'profile',
            'isPublic',
            'accountLockStatus',
            'remainingBWDownload'
                ), $id);
    }

    public function deleteUserData() {
        // connect db
        $db = Database::getDatabase(true);

        // remove database file records, this will not delete files, assume this is already done
        if ((int) $this->id > 0) {
            // stats
            $db->query('DELETE FROM stats WHERE file_id IN (SELECT id FROM file WHERE userId = ' . (int) $this->id . ')');

            // files
            $db->query('DELETE FROM file WHERE userId = ' . (int) $this->id);
        }

        // remove api keys
        $db->query('DELETE FROM apiv2_api_key WHERE user_id = ' . (int) $this->id);

        // remove folders
        $db->query('DELETE FROM file_folder WHERE userId = ' . (int) $this->id);

        // remove sessions
        $db->query('DELETE FROM sessions WHERE user_id = ' . (int) $this->id);

        // user record
        $db->query('DELETE FROM users WHERE id = ' . (int) $this->id);

        // append any plugin includes
        pluginHelper::includeAppends('objects_class_user_delete_user_data.inc.php', array('User' => $this));

        return true;
    }

    public function getAccountScreenName() {
        $label = strlen($this->firstname) ? UCWords($this->firstname) : $this->username;
        if (strlen($label) > 12) {
            $label = substr($label, 0, 12) . '...';
        }

        return $label;
    }

    public function getLastLoginFormatted() {
        if (strlen($this->lastlogindate) == 0) {
            return t('never', 'never');
        }

        return coreFunctions::formatDate($this->lastlogindate, 'D jS F y');
    }

    public function getTotalActiveFileCount() {
        // connect db
        $db = Database::getDatabase();

        // count active files
        return $db->getValue('SELECT COUNT(id) FROM file WHERE userId = ' . (int) $this->id . ' AND status = "active"');
    }

    public function getTotalLikesCount() {
        $db = Database::getDatabase();
        return $db->getValue('SELECT SUM(total_likes) AS total FROM file WHERE userId = ' . (int) $this->id);
    }

    public function hasProfileImage() {
        $avatarCachePath = 'user/' . (int) $this->id . '/profile_image/profile_original.jpg';
        if (cache::checkCacheFileExists($avatarCachePath)) {
            return true;
        }

        return false;
    }

    public function getProfileImageUrl() {
        return CACHE_WEB_ROOT . '/user/' . (int) $this->id . '/profile_image/profile_original.jpg';
    }

    public function storeProfileData($profileArr) {
        // connect db
        $db = Database::getDatabase();

        // get existing data
        $profileData = array();
        if (strlen($this->profile)) {
            $profileDataArr = json_decode($this->profile, true);
            if (is_array($profileDataArr)) {
                $profileData = $profileDataArr;
            }
        }

        // overwrite with new data
        foreach ($profileArr AS $k => $profileArrItem) {
            $profileData[$k] = $profileArrItem;
        }

        // save in local object
        $profile = json_encode($profileData);

        // update db
        return $db->query('UPDATE users SET profile=' . $db->quote($profile) . ' WHERE id = ' . (int) $this->id . ' LIMIT 1');
    }

    public function getProfileValue($key) {
        if (strlen($this->profile)) {
            $profileDataArr = json_decode($this->profile, true);
            if (is_array($profileDataArr)) {
                if (isset($profileDataArr[$key])) {
                    return $profileDataArr[$key];
                }
            }
        }

        return false;
    }

    public function getProfileUrl() {
        return UserPeer::buildProfileUrl($this->username);
    }

    public function getLikesUrl() {
        return $this->getProfileUrl() . 'likes/';
    }

    public function getSmallAvatarUrl() {
        return WEB_ROOT . '/page/view_avatar.php?id=' . (int) $this->id . '&width=110&height=110';
    }

    public function addDefaultFolders() {
        $defaultUserFolders = trim(SITE_CONFIG_USER_REGISTER_DEFAULT_FOLDERS);
        if (strlen($defaultUserFolders) == 0) {
            return false;
        }

        // connect db
        $db = Database::getDatabase();

        // get each folder and add as root item
        $folderItems = explode('|', $defaultUserFolders);
        foreach ($folderItems AS $folderItem) {
            $rs = $db->query('INSERT INTO file_folder (folderName, isPublic, userId, parentId, date_added) VALUES (:folderName, :isPublic, :userId, NULL, NOW())', array('folderName' => $folderItem, 'isPublic' => 2, 'userId' => $this->id));
        }
    }

    public function isAdmin() {
        return (int) $this->level_id === 20;
    }

}

class Order extends DBObject
{

    public function __construct($id = null) {
        parent::__construct('premium_order', array('user_id', 'payment_hash', 'days', 'amount', 'order_status', 'upgrade_file_id', 'upgrade_user_id', 'user_level_pricing_id', 'description'), $id);
    }

    public function newSubscription($paymentGateway, $gatewaySubscriptionId) {
        // connect db
        $db = Database::getDatabase();

        // insert subscription
        return $db->query('INSERT INTO payment_subscription (user_id, user_level_pricing_id, payment_gateway, gateway_subscription_id, date_added) VALUES (' . (int) $this->user_id . ', ' . (int) $this->user_level_pricing_id . ', ' . $db->quote($paymentGateway) . ', ' . $db->quote($gatewaySubscriptionId) . ', NOW())');
    }

    public function cancelSubscription($paymentGateway, $gatewaySubscriptionId) {
        // connect db
        $db = Database::getDatabase();

        // cancel active subscription
        return $db->query('UPDATE payment_subscription SET sub_status = "cancelled" WHERE paypal_subscription_id = ' . $db->quote($gatewaySubscriptionId) . ' AND payment_gateway = ' . $db->quote($paymentGateway) . ' AND user_id = ' . (int) $this->user_id . ' LIMIT 1)');
    }

}

class Folder extends DBObject
{

    public function __construct($id = null) {
        parent::__construct('premium_order', array('userId', 'parentId', 'folderName', 'isPublic', 'accessPassword', 'coverImageId', 'date_added', 'date_updated'), $id);
    }

}
