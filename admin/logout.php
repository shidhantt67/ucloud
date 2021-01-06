<?php
define('ADMIN_IGNORE_LOGIN', true);
require_once('_local_auth.inc.php');
$admin = False;
if($Auth->hasAccessLevel(20)){
    $admin = True;
}
$Auth->logout();
if($admin){
    coreFunctions::redirect(WEB_ROOT.'/admin/login.php');
}else{
    coreFunctions::redirect(WEB_ROOT.'/users/login.php');
}
exit;