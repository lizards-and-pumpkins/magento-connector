<?php

abstract class AbstractInitializableEntityExportTest
    extends \PHPUnit_Framework_TestCase
    implements InitializableCatalogEntityExportTest
{
    public function initTestExpectations()
    {
        $entityIds = $this->getEntityIdsForInitialization();
        $this->exportToFile($this->getExpectationFileName(), is_array($entityIds) ? $entityIds : [$entityIds]);
        $this->saveEntityIdsToTest($entityIds);
    }

    /**
     * @return int[]
     */
    final protected function getProductVisibleInCatalogValues()
    {
        return [
            \Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
            \Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG,
            \Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH,
        ];
    }

    /**
     * @return string
     */
    protected abstract function getEntityIdsFixtureFileName();

    /**
     * @param string $exportFile
     * @param string[] $entityIds
     */
    protected function exportToFile($exportFile, array $entityIds)
    {
        $this->prepareTestExportDirectory(dirname($exportFile));
        $this->setTargetExportFile($exportFile);
        $this->createDirIfNotExisting($exportFile);

        /** @var LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter $exporter */
        $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_catalogExporter');
        $this->exportEntities($exporter, $entityIds);
    }

    /**
     * @param LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter $exporter
     * @param int[] $entityIds
     * @return mixed
     */
    abstract public function exportEntities(
        LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter $exporter,
        $entityIds
    );

    /**
     * @param string $exportFile
     */
    protected function setTargetExportFile($exportFile)
    {
        $store = Mage::app()->getStore();
        $store->setConfig(
            'lizardsAndPumpkins/magentoconnector/local_path_for_product_export',
            'file://' . dirname($exportFile) . '/'
        );
        $store->setConfig('lizardsAndPumpkins/magentoconnector/local_filename_template', basename($exportFile));
    }

    /**
     * @param string[] $productIds
     * @return LizardsAndPumpkins_MagentoConnector_Helper_ProductsToUpdateQueueReader
     */
    protected function createProductQueueReaderForTestProducts(array $productIds)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $reader */
        $reader = $this->createMock(LizardsAndPumpkins_MagentoConnector_Helper_ProductsToUpdateQueueReader::class);
        $reader->method('getQueuedProductIds')->willReturnOnConsecutiveCalls(
            $productIds,
            []
        );
        return $reader;
    }

    /**
     * @param string[] $productIds
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector
     */
    protected function createProductCollectorForIds(array $productIds)
    {
        return new LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector(
            $this->createProductQueueReaderForTestProducts($productIds)
        );
    }

    /**
     * @param string|string[] $productIds
     */
    protected function saveEntityIdsToTest($productIds)
    {
        file_put_contents(
            $this->getEntityIdsFixtureFileName(),
            '<?php return ' . var_export($productIds, true) . ';'
        );
    }

    /**
     * @param string $directory
     */
    protected function prepareTestExportDirectory($directory)
    {
        if (!file_exists($directory)) {
            mkdir($directory, 0700, true);
        }
    }

    /**
     * @before
     */
    protected function checkTestIsInitialized()
    {
        if (!file_exists($this->getExpectationFileName())) {
            $this->markTestSkipped('Run tests/integration/util/initExpectedXml.php first');
        }
    }

    /**
     * @after
     */
    public function resetFactory()
    {
        $helper = Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
        $helper->reset();
    }

    /**
     * @param string $exportFile
     */
    private function createDirIfNotExisting($exportFile)
    {
        $dirname = dirname($exportFile);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }
    }
}
