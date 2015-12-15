<?php

class ConfigurableProductExportTest extends \PHPUnit_Framework_TestCase
{
    const EXPECTED_XML_FILE = __DIR__ . '/expected/configurable-product.xml';
    const CONFIGURABLE_PRODUCT_ID_FILE = __DIR__ . '/expected/configurable-product-id.php';

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
     * @param $exportFile
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

    private function addConfigurableProductToQueue()
    {
        /** @var LizardsAndPumpkins_MagentoConnector_Helper_Export $helper */
        $helper = Mage::helper('lizardsAndPumpkins_magentoconnector/export');
        $helper->addProductUpdatesToQueue([$this->configurableProductId]);
    }

    private function exportProductsInQueue()
    {
        /** @var LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter $exporter */
        $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_catalogExporter');
        $exporter->exportProductsInQueue();
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
        $testExportFile = $this->testExportFile;
        $this->setTargetExportFile($testExportFile);
        
        $this->addConfigurableProductToQueue();
        $this->exportProductsInQueue();
        
        $this->assertFileEquals(self::EXPECTED_XML_FILE, $testExportFile);
    }
}
