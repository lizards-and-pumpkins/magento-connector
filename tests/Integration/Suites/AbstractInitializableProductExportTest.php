<?php

abstract class AbstractInitializableProductExportTest
    extends \PHPUnit_Framework_TestCase
    implements InitializableProductExportTest
{
    public function initTestExpectations()
    {
        $productIds = $this->getProductIdsForInitialization();
        $this->exportToFile($this->getExpectationFileName(), is_array($productIds) ? $productIds : [$productIds]);
        $this->saveProductIdsToTest($productIds);
    }

    /**
     * @return string
     */
    protected abstract function getProductIdsFixtureFileName();

    /**
     * @param string $exportFile
     * @param string[] $productIds
     */
    protected function exportToFile($exportFile, array $productIds)
    {
        $this->setTargetExportFile($exportFile);
        /** @var LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter $exporter */
        $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_catalogExporter');
        $exporter->exportProducts($this->createProductCollectorForIds($productIds));
    }

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
        $reader = $this->getMock(LizardsAndPumpkins_MagentoConnector_Helper_ProductsToUpdateQueueReader::class);
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
    protected function saveProductIdsToTest($productIds)
    {
        file_put_contents(
            $this->getProductIdsFixtureFileName(),
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
}
