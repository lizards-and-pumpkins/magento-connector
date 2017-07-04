<?php

use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_CatalogDataCollectionFactory as CatalogDataCollectionFactory;
use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_ProductDataCollectionFactory as ProductDataCollectionFactory;
use LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection as ProductDataCollection;

/**
 * @covers LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_ProductDataCollectionFactory
 */
class LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_ProductDataCollectionFactoryTest
    extends \PHPUnit\Framework\TestCase
{
    public function testImplementsDataCollecionFactoryInterface()
    {
        $this->assertInstanceOf(CatalogDataCollectionFactory::class, new ProductDataCollectionFactory());
    }

    public function testReturnsProductDataCollection()
    {
        $productIdsToExport = [1, 2, 3];
        $store= Mage::app()->getStore();
        
        $collectionFactory = new ProductDataCollectionFactory();
        $collection = $collectionFactory->createCollection($store, $productIdsToExport);
        
        $this->assertInstanceOf(ProductDataCollection::class, $collection);
    }
}
