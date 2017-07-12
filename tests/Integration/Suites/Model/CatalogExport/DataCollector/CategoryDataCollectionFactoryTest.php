<?php

use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_CategoryDataCollectionFactory as CategoryDataCollectionFactory;
use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_CatalogDataCollectionFactory as CatalogDataCollectionFactory;
use Mage_Catalog_Model_Resource_Category_Collection as CategoryCollection;

/**
 * @covers LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_CategoryDataCollectionFactory
 */
class LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_CategoryDataCollectionFactoryTest
    extends \PHPUnit\Framework\TestCase
{
    private $testCategoryUrlSuffix = '.foo';
    
    public function testImplementsDataCollecionFactoryInterface()
    {
        $factory = new CategoryDataCollectionFactory($this->testCategoryUrlSuffix);
        $this->assertInstanceOf(CatalogDataCollectionFactory::class, $factory);
    }

    public function testReturnsACategoryCollection()
    {
        $productIdsToExport = [1, 2, 3];

        /** @var Mage_Core_Model_Store|\PHPUnit_Framework_MockObject_MockObject $stubStore */
        $stubStore = $this->createMock(Mage_Core_Model_Store::class);

        $collectionFactory = new CategoryDataCollectionFactory($this->testCategoryUrlSuffix);
        $collection = $collectionFactory->createCollection($stubStore, $productIdsToExport);

        $this->assertInstanceOf(CategoryCollection::class, $collection);
    }
}
