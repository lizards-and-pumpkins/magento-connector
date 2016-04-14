<?php

error_reporting(E_ALL);

require __DIR__ . '/../../vendor/autoload.php';
require $_SERVER['MAGENTO_ROOT_PATH'] . '/app/Mage.php';

set_error_handler(function ($errno, $errstr, $errfile) {
    return substr($errfile, -19) == 'Varien/Autoload.php' ? null : false;
});

spl_autoload_register(function ($classname) {
    $classnameReplaced = str_replace(['_', '\\'], '/', $classname);
    $filename = __DIR__ . '/../../magento-plugin/app/code/community/' . $classnameReplaced . '.php';
    if (is_file($filename)) {
        require $filename;
    }
});
