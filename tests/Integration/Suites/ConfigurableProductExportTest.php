<?php

class ConfigurableProductExportTest extends \PHPUnit_Framework_TestCase
{
    const EXPECTED_XML_FILE = __DIR__ . '/expected/configurable-product.xml';
    const CONFIGURABLE_PRODUCT_ID_FILE = __DIR__ . '/expected/configurable-product-id.php';

    /**
     * @var string
     */
    private $testExportFile;

    /**
     * @var string|int
     */
    private $configurableProductId;

    private function prepareTestExportDirectory()
    {
        if (file_exists($this->testExportFile)) {
            unlink($this->testExportFile);
        }
        if (! file_exists(dirname($this->testExportFile))) {
            mkdir(dirname($this->testExportFile), 0700, true);
        }
    }

    /**
     * @param string $exportFile
     */
    private function setTargetExportFile($exportFile)
    {
        $store = Mage::app()->getStore();
        $store->setConfig(
            'lizardsAndPumpkins/magentoconnector/local_path_for_product_export',
            'file://' . dirname($exportFile) . '/'
        );
        $store->setConfig('lizardsAndPumpkins/magentoconnector/local_filename_template', basename($exportFile));
    }

    /**
     * @param int[] $productId
     * @return LizardsAndPumpkins_MagentoConnector_Helper_ProductsToUpdateQueueReader
     */
    private function createProductQueueReaderForTestProduct(array $productIds)
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
     * @param int[] $productIds
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector
     */
    private function createProductCollectorForIds(array $productIds)
    {
        return new LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector(
            $this->createProductQueueReaderForTestProduct($productIds)
        );
    }

    /**
     * @param string $exportFile
     * @param int[]|string[] $productIds
     */
    public function initTestExpectations($exportFile, array $productIds)
    {
        $this->exportToFile($exportFile, $productIds);
        $this->saveProductIdToTest($productIds[0]);
    }

    /**
     * @param int $configurableProductId
     */
    private function saveProductIdToTest($configurableProductId)
    {
        file_put_contents(
            self::CONFIGURABLE_PRODUCT_ID_FILE,
            '<?php return ' . var_export($configurableProductId, true) . ';'
        );
    }

    /**
     * @param string $exportFile
     * @param int[] $productIds
     */
    private function exportToFile($exportFile, array $productIds)
    {
        $this->setTargetExportFile($exportFile);
        /** @var LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter $exporter */
        $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_catalogExporter');
        $exporter->exportProducts($this->createProductCollectorForIds($productIds));
    }

    protected function setUp()
    {
        if (! file_exists(self::EXPECTED_XML_FILE) || ! file_exists(self::CONFIGURABLE_PRODUCT_ID_FILE)) {
            $this->markTestSkipped('Run tests/integration/util/initExpectedXml.php first');
        }
        $this->configurableProductId = require self::CONFIGURABLE_PRODUCT_ID_FILE;
        $this->testExportFile = sys_get_temp_dir() . '/lizards-and-pumpkins/magento-connector/configurable-product-test.xml';
        $this->prepareTestExportDirectory();
    }

    protected function tearDown()
    {
        @unlink($this->testExportFile);
        @rmdir(dirname($this->testExportFile));
    }

    public function testExportConfigurableProduct()
    {
        $this->exportToFile($this->testExportFile, [$this->configurableProductId]);
        
        $this->assertFileEquals(self::EXPECTED_XML_FILE, $this->testExportFile);
    }
}
