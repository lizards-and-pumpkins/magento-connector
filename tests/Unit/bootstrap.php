<?php

declare(strict_types = 1);

error_reporting(E_ALL);

require __DIR__ . '/../../vendor/autoload.php';

$pathToMage = (function () {
    if (isset($_SERVER['MAGENTO_ROOT_PATH'])) {
        return $_SERVER['MAGENTO_ROOT_PATH'] . '/app/Mage.php';
    }
    // if code is within src/magento-extensions/magento-connector
    if (file_exists(__DIR__ . '/../../../../magento/app/Mage.php')) {
        return __DIR__ . '/../../../../magento/app/Mage.php';
    }
    // if code is within .modman within the magento base directory 
    if (file_exists(__DIR__ . '/../app/Mage.php')) {
        return __DIR__ . '/../magento/app/Mage.php';
    }
    return 'app/Mage.php';
})();

if (! file_exists($pathToMage)) {
    throw new \RuntimeException(
        sprintf('%s does not exist. Maybe "MAGENTO_ROOT_PATH" is not set in phpunit.xml?', $pathToMage)
    );
}

require $pathToMage;

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
