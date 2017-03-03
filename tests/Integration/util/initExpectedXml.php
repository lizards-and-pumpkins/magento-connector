#!/usr/bin/env php
<?php
declare(strict_types = 1);

require __DIR__ . '/../bootstrap.php';

$directoryIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../Suites'));
$filteredDirectoryIterator = new RegexIterator(
    $directoryIterator, '/^.+Test\.php$/i',
    RecursiveRegexIterator::GET_MATCH
);

$getInitializableCatalogEntityExportTestClass = function ($testFile) {
    if (preg_match('/^class (\w+)/mi', file_get_contents($testFile), $matches)) {
        require_once $testFile;
        if (in_array(\InitializableCatalogEntityExportTest::class, class_implements($matches[1]))) {
            return $matches[1];
        }
    }
};

$factory = Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
$factory->disableImageExport();
$queueProductCollector = $factory->createProductCollector();

foreach ($filteredDirectoryIterator as $testFile) {
    if ($testClass = $getInitializableCatalogEntityExportTestClass($testFile[0])) {
        /** @var \InitializableCatalogEntityExportTest $testToInitialize */
        $testToInitialize = new $testClass();
        $entityIds = $testToInitialize->getEntityIdsForInitialization();
        printf(
            "Exporting the entities [%s] to the test fixture file %s\n",
            is_array($entityIds) ? implode(',', $entityIds) : $entityIds,
            substr($testToInitialize->getExpectationFileName(), strlen(BP) + 1)
        );

        $testToInitialize->initTestExpectations();
        $testToInitialize->resetFactory();
    }
}
echo "Test data initialized.\n";

