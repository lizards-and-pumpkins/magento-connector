<?php
require 'lib/autoload_lizardsAndPumpkins.php';
require 'app/Mage.php';
Mage::app();

while (true) {
    $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_productExporter');
    $exporter->exportProductsInQueue();
    usleep(500000);
}
