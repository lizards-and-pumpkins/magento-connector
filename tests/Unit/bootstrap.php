<?php
error_reporting(E_ALL);

require __DIR__ . '/../../vendor/autoload.php';

spl_autoload_register(function ($classname) {
    $classnameReplaced = str_replace('_', '/', $classname);
    if (is_file('magento-plugin/app/code/community/' . $classnameReplaced . '.php')) {
        require 'magento-plugin/app/code/community/' . $classnameReplaced . '.php';
    }
});