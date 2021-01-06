<?php

/* setup includes */
require_once('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// prepare result
$result = array();
$result['error'] = false;
$result['msg'] = '';

if (_CONFIG_DEMO_MODE == true) {
    $result['error'] = true;
    $result['msg'] = t("no_changes_in_demo_mode");
}
else {
    // empty the current users trash
    coreFunctions::emptyTrashByUserId($Auth->id);

    $result['error'] = false;
    $result['msg'] = 'Trash emptied.';
}

echo json_encode($result);
exit;
