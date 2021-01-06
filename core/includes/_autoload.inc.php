<?php

// autoloader, included within the /core/includes/master.inc.php file
spl_autoload_register(function($className) {
    if (is_file(CORE_ROOT . '/includes/' . lcfirst($className) . '.class.php')) {
        require_once(CORE_ROOT . '/includes/' . lcfirst($className) . '.class.php');
    }
});

// composer
include_once(__DIR__ . '/vendor/autoload.php');
