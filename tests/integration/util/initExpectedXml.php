#!/usr/bin/env php
<?php

require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../suite/ConfigurableProductExportTest.php';

if (! isset($argv[1])) {
    $argv[1] = ConfigurableProductExportTest::EXPECTED_XML_FILE;
}

$isAbsolutePathToFile = substr($argv[1], 0, 1) === '/';
$exportFile = $isAbsolutePathToFile ?
    $argv[1] :
    sprintf('%s/%s', getcwd(), $argv[1]);

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

printf("Exporting the configurable product %d to the test fixture file %s\n", $configurableProductId, $argv[1]);

$test = new ConfigurableProductExportTest();
$test->exportToFile($exportFile, [$configurableProductId]);

if (ConfigurableProductExportTest::EXPECTED_XML_FILE === $argv[1]) {
    file_put_contents(
        ConfigurableProductExportTest::CONFIGURABLE_PRODUCT_ID_FILE,
        '<?php return ' . var_export($configurableProductId, true) . ';'
    );
}

