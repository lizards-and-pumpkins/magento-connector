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

if ('all' === $argv[1]) {
    
    $helper = Mage::helper('lizardsAndPumpkins_magentoconnector/export');
    $helper->addAllProductIdsToProductUpdateQueue();
    echo "Added all product ids to the export queue\n";
    
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

    Mage::helper('lizardsAndPumpkins_magentoconnector/export')->addProductUpdatesToQueue(
        $collection->getConnection()->fetchCol($select)
    );

    echo "Added {$numberOfProducts} product id(s) to the export queue\n";
}
