<?php
require 'lib/autoload_brera.php';
require 'app/Mage.php';
Mage::app();

while (true) {
    $exporter = Mage::getModel('brera_magentoconnector/export_productExporter');
    $exporter->exportProductsInQueue();
    usleep(500000);
}
