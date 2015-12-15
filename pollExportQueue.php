<?php

use LizardsAndPumpkins\MagentoConnector\Api\Api;

require 'lib/autoload_lizards_and_pumpkins.php';
require 'app/Mage.php';
Mage::app();

while (true) {
    $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_catalogExporter');
    $filename = $exporter->exportProductsInQueue();
    
    $apiUrl = Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/api_url');
    (new Api($apiUrl))->triggerProductImport($filename);
    
    usleep(500000);
}
