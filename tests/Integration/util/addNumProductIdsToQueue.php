#!/usr/bin/env php
<?php

require __DIR__ . '/../bootstrap.php';

if (! isset($argv[1])) {
    echo <<< EOM
Specify the number of products to add to the queue as an argument to the command.
Example for adding 5 products:
  {$argv[0]} 5

EOM;
    exit(2);
}

$factory = Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
$dataVersion = Mage::helper('lizardsAndPumpkins_magentoconnector/dataVersion');

if ('all' === $argv[1]) {
    $factory->createExportQueue()->addAllProductIdsToProductUpdateQueue($dataVersion);
    echo "Added all product IDs to the export queue\n";
    
} else {

    $numberOfProducts = (int) $argv[1];
    if ($numberOfProducts <= 0) {
        echo <<< EOM
Specify a number larger then zero to be added to the queue.

EOM;
        exit(2);
    }
    
    $collection = Mage::getResourceModel('catalog/product_collection');
    $collection->addAttributeToFilter(
        'visibility',
        ['neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE]
    );
    $collection->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);

    $select = $collection->getSelect();
    $select->reset(Zend_Db_Select::COLUMNS);
    $select->columns('entity_id');
    $select->limit($numberOfProducts);

    $productIds = $collection->getConnection()->fetchCol($select);
    $factory->createExportQueue()->addProductUpdatesToQueue($productIds, $dataVersion->getTargetVersion());
    
    echo "Added {$numberOfProducts} product ID(s) to the export queue\n";
}
