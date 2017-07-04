<?php

use LizardsAndPumpkins\MagentoConnector\Api\InsecurePhpStreamHttpApiClient;
use LizardsAndPumpkins\MagentoConnector\Api\PhpStreamHttpApiClient;

/**
 * @covers LizardsAndPumpkins_MagentoConnector_Helper_Factory
 */
class LizardsAndPumpkins_MagentoConnector_Helper_FactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \LizardsAndPumpkins_MagentoConnector_Helper_Factory
     */
    private $factory;

    protected function setUp()
    {
        $this->factory = Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
    }

    public function testReturnsALizardsAndPumpkinsApiInstance()
    {
        $fixtureApiUrl = 'http://example.com';
        Mage::app()->getStore()->setConfig('lizardsAndPumpkins/magentoconnector/api_url', $fixtureApiUrl);

        $result = $this->factory->createLizardsAndPumpkinsApi();
        $this->assertInstanceOf(\LizardsAndPumpkins\MagentoConnector\Api\Api::class, $result);
    }

    public function testReturnsHttpApiClient()
    {
        $result = $this->factory->createHttpApiClient();
        $this->assertInstanceOf(PhpStreamHttpApiClient::class, $result);
        $this->assertNotInstanceOf(InsecurePhpStreamHttpApiClient::class, $result);
    }

    public function testReturnsinsecureHttpApiClientIfRequestedInSystemConfig()
    {
        Mage::app()->getStore()->setConfig('lizardsAndPumpkins/magentoconnector/disable_tls_peer_verification', '1');
        $result = $this->factory->createHttpApiClient();
        $this->assertInstanceOf(InsecurePhpStreamHttpApiClient::class, $result);
    }

    public function testReturnsACatalogMergeInstance()
    {
        $result = $this->factory->createCatalogMerge();
        $this->assertInstanceOf(\LizardsAndPumpkins\MagentoConnector\XmlBuilder\CatalogMerge::class, $result);
    }

    public function testReturnsAListingXmlInstance()
    {
        $result = $this->factory->createListingXml();
        $this->assertInstanceOf(\LizardsAndPumpkins\MagentoConnector\XmlBuilder\ListingXml::class, $result);
    }

    public function testReturnsAConnectorConfigInstance()
    {
        $result = $this->factory->getConfig();
        $this->assertInstanceOf(\LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig::class, $result);
    }

    public function testReturnsAProductBuilderInstance()
    {
        $dummyProductData = [];
        $dummyContext = ['locale' => 'de_DE'];
        $result = $this->factory->createProductBuilder($dummyProductData, $dummyContext);
        $this->assertInstanceOf(\LizardsAndPumpkins\MagentoConnector\XmlBuilder\ProductBuilder::class, $result);
    }

    public function testReturnsAStockBuilderInstance()
    {
        $result = $this->factory->createStockBuilder();
        $this->assertInstanceOf(\LizardsAndPumpkins\MagentoConnector\XmlBuilder\StockBuilder::class, $result);
    }

    public function testReturnsExportQueueInstance()
    {
        $result = $this->factory->createExportQueue();
        $this->assertInstanceOf(LizardsAndPumpkins_MagentoConnector_Model_ExportQueue::class, $result);
    }

    public function testReturnsExportFileWriterInstance()
    {
        $result = $this->factory->createExportFileWriter();
        $this->assertInstanceOf(
            LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFileWriter::class,
            $result
        );
    }

    public function testReturnsCatalogDataForStoresCollector()
    {
        $result = $this->factory->createCatalogDataforStoresCollector();
        $this->assertInstanceOf(
            LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_CatalogDataForStoresCollector::class,
            $result
        );
    }

    public function testReturnsCategoryCollectionFactory()
    {
        $result = $this->factory->createCategoryDataCollectionFactory();
        $this->assertInstanceOf(
            LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_CategoryDataCollectionFactory::class,
            $result
        );
    }

    public function testReturnsProductDataCollectionFactory()
    {
        $result = $this->factory->createProductDataCollectionFactory();
        $this->assertInstanceOf(
            LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_ProductDataCollectionFactory::class,
            $result
        );
    }
}
