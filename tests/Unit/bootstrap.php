<?php

error_reporting(E_ALL);

require __DIR__ . '/../../vendor/autoload.php';

if (isset($_SERVER['MAGENTO_ROOT_PATH'])) {
    $pathToMage = $_SERVER['MAGENTO_ROOT_PATH'] . '/app/Mage.php';
} elseif (file_exists(__DIR__ . '/../../../../magento/app/Mage.php')) {
    // if code is within src/magento-extensions/magento-connector
    $pathToMage = __DIR__ . '/../../../../magento/app/Mage.php';
} elseif (file_exists(__DIR__ . '/../app/Mage.php')) {
// if code is within .modman within the magento base directory
    $pathToMage = __DIR__ . '/../magento/app/Mage.php';
} else {
    $pathToMage = 'app/Mage.php';
}

if (! file_exists($pathToMage)) {
    throw new \RuntimeException(
        sprintf('%s does not exist. Maybe "MAGENTO_ROOT_PATH" is not set in phpunit.xml?', $pathToMage)
    );
}

require $pathToMage;

set_error_handler(function ($errno, $errstr, $errfile) {
    return substr($errfile, -19) === 'Varien/Autoload.php' ? null : false;
});

spl_autoload_register(function ($classname) {
    $classnameReplaced = str_replace(['_', '\\'], '/', $classname);
    $filename = __DIR__ . '/../../magento-plugin/app/code/community/' . $classnameReplaced . '.php';
    if (is_file($filename)) {
        require $filename;
    }
});
