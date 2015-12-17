#!/usr/bin/env php
<?php

use LizardsAndPumpkins\MagentoConnector\Api\Api;

require __DIR__ . '/vendor/autoload.php';
require 'app/Mage.php';
Mage::app();

do {
    $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_catalogExporter');
    $filename = $exporter->exportProductsInQueue();

    $apiUrl = Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/api_url');
    (new Api($apiUrl))->triggerProductImport($filename);

    usleep(500000);
} while (false);
