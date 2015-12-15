<?php

error_reporting(E_ALL);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../../../app/Mage.php';
Mage::setIsDeveloperMode(true);
Mage::app();

$_SESSION = [];

$mageErrorHandler = set_error_handler(function () {
    return false;
});
set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($mageErrorHandler) {
    if (substr($errfile, -19) == 'Varien/Autoload.php') {
        return null;
    }
    return is_callable($mageErrorHandler) ?
        call_user_func_array($mageErrorHandler, func_get_args()) :
        false;
});
