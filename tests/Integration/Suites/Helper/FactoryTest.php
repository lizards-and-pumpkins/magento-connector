<?php

class FactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \LizardsAndPumpkins_MagentoConnector_Helper_Factory
     */
    private $factoryHelper;

    protected function setUp()
    {
        $this->factoryHelper = Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
    }
    
    public function testReturnsALizardsAndPumpkinsApiInstance()
    {
        $fixtureApiUrl = 'http://example.com';
        Mage::app()->getStore()->setConfig('lizardsAndPumpkins/magentoconnector/api_url', $fixtureApiUrl);
        
        $result = $this->factoryHelper->createLizardsAndPumpkinsApi();
        $this->assertInstanceOf(\LizardsAndPumpkins\MagentoConnector\Api\Api::class, $result);
    }

    public function testReturnsACatalogMergeInstance()
    {
        $result = $this->factoryHelper->createCatalogMerge();
        $this->assertInstanceOf(\LizardsAndPumpkins\MagentoConnector\XmlBuilder\CatalogMerge::class, $result);
    }

    public function testReturnsAListingXmlInstance()
    {
        $result = $this->factoryHelper->createListingXml();
        $this->assertInstanceOf(\LizardsAndPumpkins\MagentoConnector\XmlBuilder\ListingXml::class, $result);
    }

    public function testReturnsAConnectorConfigInstance()
    {
        $result = $this->factoryHelper->getConfig();
        $this->assertInstanceOf(\LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig::class, $result);
    }

    public function testReturnsAProductBuilderInstance()
    {
        $dummyProductData = [];
        $dummyContext = ['locale' => 'de_DE'];
        $result = $this->factoryHelper->createProductBuilder($dummyProductData, $dummyContext);
        $this->assertInstanceOf(\LizardsAndPumpkins\MagentoConnector\XmlBuilder\ProductBuilder::class, $result);
    }

    public function testReturnsAStockBuilderInstance()
    {
        $result = $this->factoryHelper->createStockBuilder();
        $this->assertInstanceOf(\LizardsAndPumpkins\MagentoConnector\XmlBuilder\StockBuilder::class, $result);
    }
}
