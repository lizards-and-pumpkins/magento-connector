#!/usr/bin/env php
<?php

require __DIR__ . '/../bootstrap.php';

if (! isset($argv[1])) {
    echo <<< EOM
Specify the product IDs to add to the queue as an arguments to the command.
Example:
  {$argv[0]} 5566 2762

EOM;
    exit(2);
}

$productIds = array_slice($argv, 1);

$factory = Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
$dataVersion = Mage::helper('lizardsAndPumpkins_magentoconnector/dataVersion');
$factory->createExportQueue()->addProductUpdatesToQueue($productIds, $dataVersion->getTargetVersion());

printf("IDs added to the queue \"%s\"\n", implode('", "', $productIds));
