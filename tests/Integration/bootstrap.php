<?php

error_reporting(E_ALL);

require __DIR__ . '/../../vendor/autoload.php';
require $_SERVER['MAGENTO_ROOT_PATH'] . '/app/Mage.php';
require __DIR__ . '/util/lib/InitializableCatalogEntityExportTest.php';

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
