<?php
require 'lib/autoload_lizards_and_pumpkins.php';
require 'app/Mage.php';
Mage::app();

while (true) {
    $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_catalogExporter');
    $exporter->exportProductsInQueue();
    usleep(500000);
}
