<?php

use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_CatalogDataForStoresCollector as CatalogDataForStoresCollector;
use \LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_CatalogDataCollectionFactory as CatalogDataCollectionFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_CatalogDataForStoresCollector
 */
class LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_CatalogDataForStoresCollectorTest extends TestCase
{
    public function testIteratesOverCollectionAndStores()
    {
        $stores = [
            $this->createMock(Mage_Core_Model_Store::class),
            $this->createMock(Mage_Core_Model_Store::class),
        ];

        $entityIdsToExport = [1, 2, 3];

        /** @var CatalogDataCollectionFactory|\PHPUnit_Framework_MockObject_MockObject $collectionFactory */
        $collectionFactory = $this->createMock(CatalogDataCollectionFactory::class);
        $collectionFactory->method('createCollection')->willReturn(new \ArrayIterator($entityIdsToExport));
        $collector = new CatalogDataForStoresCollector($stores);

        $entityIdsTimesStoresCount = array_merge($entityIdsToExport, $entityIdsToExport);

        $this->assertSame(
            $entityIdsTimesStoresCount,
            iterator_to_array($collector->aggregate($entityIdsToExport, $collectionFactory))
        );
    }
}
