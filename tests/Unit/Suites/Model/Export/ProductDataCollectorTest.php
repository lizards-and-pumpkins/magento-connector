<?php

/**
 * @deprecated without direct replacement 
 * @see \LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_ProductDataCollectionFactoryTest
 * @covers LizardsAndPumpkins_MagentoConnector_Model_Export_ProductDataCollector
 */
class LizardsAndPumpkins_MagentoConnector_Model_Export_ProductDataCollectorTest extends \PHPUnit\Framework\TestCase
{
    public function testIteratesOverAllGivenStores()
    {
        $storesToExport = [
            $this->createMock(Mage_Core_Model_Store::class),
            $this->createMock(Mage_Core_Model_Store::class),
        ];
        $productIdsToExport = [1, 2, 3, 4, 6];

        $productDataCollector = new ProductDataCollectorWithStubCollection($productIdsToExport, $storesToExport);
        
        $productIdsTimesStoresToExport = array_merge($productIdsToExport, $productIdsToExport);
        $this->assertSame($productIdsTimesStoresToExport, iterator_to_array($productDataCollector));
    }
}

class ProductDataCollectorWithStubCollection
    extends LizardsAndPumpkins_MagentoConnector_Model_Export_ProductDataCollector
{
    protected function instantiateNewCollection()
    {
        return new StubProductDataCollectorProductCollection();
    }
}

class StubProductDataCollectorProductCollection
    extends LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection
{
    private $idsToIterate = [];

    public function addIdFilter($productIds, $exclude = false)
    {
        $this->idsToIterate = $productIds;
    }

    public function getData()
    {
        return $this->idsToIterate;
    }

    public function __construct()
    {
    }

    public function load($printQuery = false, $logQuery = false)
    {
    }

    public function addAttributeToSelect($attribute, $joinType = false)
    {
    }

    public function addAttributeToFilter($attribute, $condition = null, $joinType = 'inner')
    {
    }

    public function addWebsiteFilter($websites = null)
    {
    }
}


