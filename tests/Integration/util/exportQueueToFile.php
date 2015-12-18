#!/usr/bin/env php
<?php

require __DIR__ . '/../bootstrap.php';

$exportFile = isset($argv[1]) ?
    $argv[1] :
    'php://stdout';

$isRegularFileSpec = strpos($exportFile, '://') === false;
if ($isRegularFileSpec && substr($exportFile, 0, 1) !== '/') {
    $exportFile = getcwd() . '/' . $exportFile;
}

$dir = $isRegularFileSpec ?
    'file://' . dirname($exportFile) . '/' :
    $exportFile;
$file = $isRegularFileSpec ?
    basename($exportFile) :
    '';

$store = Mage::app()->getStore();
$store->setConfig('lizardsAndPumpkins/magentoconnector/local_path_for_product_export', $dir );
$store->setConfig('lizardsAndPumpkins/magentoconnector/local_filename_template', $file);

$queueProductCollector = Mage::helper('lizardsAndPumpkins_magentoconnector/factory')->createProductCollector();

$exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_catalogExporter');
$exporter->exportProducts($queueProductCollector);

if ($exportFile !== 'php://stdout') {
    echo "Export finished\n";
}
