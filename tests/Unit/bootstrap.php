<?php

error_reporting(E_ALL);

require __DIR__ . '/../../vendor/autoload.php';

spl_autoload_register(function ($classname) {
    $classnameReplaced = str_replace(['_', '\\'], '/', $classname);
    $filename = __DIR__ . '/../../magento-plugin/app/code/community/' . $classnameReplaced . '.php';
    if (is_file($filename)) {
        require $filename;
    }
});
