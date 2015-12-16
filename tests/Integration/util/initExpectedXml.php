#!/usr/bin/env php
<?php

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../Suites/ConfigurableProductExportTest.php';

$testToInitialize = new ConfigurableProductExportTest();
$productIds = $testToInitialize->getProductIdsForInitialization();

printf(
    "Exporting the product(s) %s to the test fixture file %s\n",
    is_array($productIds) ? implode(',', $productIds) : $productIds,
    substr($testToInitialize->getExpectationFileName(), strlen(BP) + 1)
);

$testToInitialize->initTestExpectations();

