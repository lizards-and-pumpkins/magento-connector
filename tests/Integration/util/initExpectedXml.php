#!/usr/bin/env php
<?php

require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Suites/ConfigurableProductExportTest.php';

// todo: move getting the product ids to export into test method
/** @var Mage_Catalog_Model_Resource_Product_Collection $configurableProductCollection */
$configurableProductCollection = Mage::getResourceModel('catalog/product_collection');
$configurableProductCollection
    ->addAttributeToFilter('type_id', \Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE)
    ->addAttributeToFilter('is_saleable', 1)
    ->setPageSize(1);

$select = $configurableProductCollection->getSelect();
$select->reset(Zend_Db_Select::COLUMNS);
$select->columns(['entity_id']);
$configurableProductId = Mage::getSingleton('core/resource')->getConnection('default_read')->fetchOne($select);

// todo: move getting the filename to export to into test method
$file = ConfigurableProductExportTest::EXPECTED_XML_FILE;

$isAbsolutePathToFile = substr($file, 0, 1) === '/';
$exportFile = $isAbsolutePathToFile ?
    $file :
    sprintf('%s/%s', getcwd(), $file);

printf("Exporting the configurable product %d to the test fixture file %s\n", $configurableProductId, $file);

(new ConfigurableProductExportTest())->initTestExpectations($exportFile, [$configurableProductId]);

