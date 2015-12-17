#!/usr/bin/env php
<?php

require __DIR__ . '/../bootstrap.php';

$directoryIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../Suites'));
$filteredDirectoryIterator = new RegexIterator($directoryIterator, '/^.+Test\.php$/i',
    RecursiveRegexIterator::GET_MATCH);

$getInitializableProductExportTestClass = function ($testFile) {
    if (preg_match('/^class (\w+)/mi', file_get_contents($testFile), $matches)) {
        require_once $testFile;
        if (in_array(\InitializableProductExportTest::class, class_implements($matches[1]))) {
            return $matches[1];
        }
    }
};

foreach ($filteredDirectoryIterator as $testFile) {
    if ($testClass = $getInitializableProductExportTestClass($testFile[0])) {
        /** @var \InitializableProductExportTest $testToInitialize */
        $testToInitialize = new $testClass();
        $productIds = $testToInitialize->getProductIdsForInitialization();
        printf(
            "Exporting the product(s) %s to the test fixture file %s\n",
            is_array($productIds) ? implode(',', $productIds) : $productIds,
            substr($testToInitialize->getExpectationFileName(), strlen(BP) + 1)
        );

        $testToInitialize->initTestExpectations();
        $testToInitialize->resetFactory();
    }
}

