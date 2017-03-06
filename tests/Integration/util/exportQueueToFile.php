#!/usr/bin/env php
<?php

declare(strict_types = 1);

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

$factory = Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
$factory->disableImageExport();
$queueProductCollector = $factory->createProductCollector();

$exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_catalogExporter');
$exporter->setShowProgress(true);
$exporter->exportProducts($queueProductCollector);

if ($exportFile !== 'php://stderr') {
    $f = fopen('php://stderr', 'a');
    fwrite($f, "Export finished\n");
    fclose($f);
}
