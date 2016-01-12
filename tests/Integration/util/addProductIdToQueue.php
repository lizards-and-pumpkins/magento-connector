#!/usr/bin/env php
<?php

require __DIR__ . '/../bootstrap.php';

if (! isset($argv[1])) {
    echo <<< EOM
Specify the product ids to add to the queue as an arguments to the command.
Example:
  {$argv[0]} 5566 2762

EOM;
    exit(2);
}

$productIds = array_slice($argv, 1);

Mage::helper('lizardsAndPumpkins_magentoconnector/export')->addProductUpdatesToQueue($productIds);

printf('IDs added to the queue "%s"', implode('", "', $productIds));
