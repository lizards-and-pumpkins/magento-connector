<?php

use LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue as ExportQueueResource;
use LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueueReader as ExportQueueResourceReader;
use LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message_Collection as ExportQueueMessageCollection;

/**
 * @covers LizardsAndPumpkins_MagentoConnector_Model_ExportQueue
 */
class LizardsAndPumpkins_MagentoConnector_Model_ExportQueueTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ExportQueueResource|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockResourceModel;

    /**
     * @var ExportQueueResourceReader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockResourceModelReader;

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_ExportQueue
     */
    private function createExportQueue()
    {
        return new LizardsAndPumpkins_MagentoConnector_Model_ExportQueue(
            $this->mockResourceModel,
            $this->mockResourceModelReader
        );
    }

    protected function setUp()
    {
        $this->mockResourceModel = $this->createMock(ExportQueueResource::class);
        $this->mockResourceModelReader = $this->createMock(ExportQueueResourceReader::class);
    }

    public function testDelegatesAddingAllProductIdsToTheResourceModel()
    {
        $targetDataVersion = 'foo';
        $this->mockResourceModel->expects($this->once())
            ->method('addAllProductIdsToProductUpdateQueue')
            ->with($targetDataVersion);
        
        $this->createExportQueue()->addAllProductIdsToProductUpdateQueue($targetDataVersion);
    }

    public function testDelegatesAddingAllProductsFromWebsitesToTheResourceModel()
    {
        $websiteId = 123;
        $targetDataVersion = 'bar';
        
        /** @var Mage_Core_Model_Website|\PHPUnit_Framework_MockObject_MockObject $stubWebsite */
        $stubWebsite = $this->createMock(Mage_Core_Model_Website::class);
        $stubWebsite->method('getId')->willReturn($websiteId);
        
        $this->mockResourceModel->expects($this->once())
            ->method('addAllProductIdsFromWebsiteToProductUpdateQueue')
            ->with($websiteId, $targetDataVersion);

        $this->createExportQueue()->addAllProductIdsFromWebsiteToProductUpdateQueue($stubWebsite, $targetDataVersion);
    }

    public function testDelegatesAddingSpecificProductIdsToTheUpdateQueueToTheResourceModel()
    {
        $productIds = [123, 42];
        $targetDataVersion = 'baz';
        $this->mockResourceModel->expects($this->once())
            ->method('addProductUpdatesToQueue')
            ->with($productIds, $targetDataVersion);
        
        $this->createExportQueue()->addProductUpdatesToQueue($productIds, $targetDataVersion);
    }

    public function testDelegatesAddingASpecificProductIdToTheUpdateQueueToTheResourceModel()
    {
        $productId = 42;
        $targetDataVersion = 'foo';
        $this->mockResourceModel->expects($this->once())
            ->method('addProductUpdateToQueue')
            ->with($productId, $targetDataVersion);
        
        $this->createExportQueue()->addProductUpdateToQueue($productId, $targetDataVersion);
    }

    public function testDelegatesAddingACategoryToTheQueueToTheResourceModel()
    {
        $categoryId = 11;
        $targetDataVersion = 'bar';
        $this->mockResourceModel->expects($this->once())
            ->method('addCategoryToQueue')
            ->with($categoryId, $targetDataVersion);
        
        $this->createExportQueue()->addCategoryToQueue($categoryId, $targetDataVersion);
    }

    public function testDelegatesAddingAllCategoriesToTheQueueToTheResourceModel()
    {
        $targetDataVersion = 'baz';
        $this->mockResourceModel->expects($this->once())
            ->method('addAllCategoryIdsToCategoryQueue')
            ->with($targetDataVersion);
        
        $this->createExportQueue()->addAllCategoryIdsToCategoryQueue($targetDataVersion);
    }

    public function testDelegatesGettingProductIdsOnUpdateQueueToResourceModel()
    {
        $productsOnQueueCollections = [];
        $this->mockResourceModelReader->method('getQueuedProductUpdatesGroupedByDataVersion')
            ->willReturn($productsOnQueueCollections);

        $result = $this->createExportQueue()->getQueuedProductUpdatesGroupedByDataVersion();
        $this->assertSame($productsOnQueueCollections, $result);
    }

    public function testDelegatesGettingCategoryIdsFromTheQueueToTheResourceModel()
    {
        $categoriesOnQueueCollections = [];
        $this->mockResourceModelReader->method('getQueuedCategoryUpdatesGroupedByDataVersion')
            ->willReturn($categoriesOnQueueCollections);

        $result = $this->createExportQueue()->getQueuedCategoryUpdatesGroupedByDataVersion();
        $this->assertSame($categoriesOnQueueCollections, $result);
    }

    public function testDelegatesGettingTheProductQueueCountToTheReaderResourceModel()
    {
        $this->mockResourceModelReader->method('getProductQueueCount')
            ->willReturn(123);
        $this->assertSame(123, $this->createExportQueue()->getProductQueueCount());
    }

    public function testDelegatesGettingTheCategoryQueueCountToTheReaderResourceModel()
    {
        $this->mockResourceModelReader->method('getCategoryQueueCount')
            ->willReturn(321);
        $this->assertSame(321, $this->createExportQueue()->getCategoryQueueCount());
    }

    public function testRemovesFetchedProductQueueMessages()
    {
        $stubCollection1 = $this->createMock(ExportQueueMessageCollection::class);
        $stubCollection1->method('getAllIds')->willReturn([2, 3]);
        $stubCollection2 = $this->createMock(ExportQueueMessageCollection::class);
        $stubCollection2->method('getAllIds')->willReturn([5, 6]);

        $this->mockResourceModelReader->method('getQueuedProductUpdatesGroupedByDataVersion')
            ->willReturn([$stubCollection1, $stubCollection2]);
        
        $this->mockResourceModel->expects($this->once())
            ->method('removeMessages')
            ->with([2, 3, 5, 6]);
        
        $result = $this->createExportQueue()->popQueuedProductUpdatesGroupedByDataVersion();
        
        $this->assertSame([$stubCollection1, $stubCollection2], $result);
    }

    public function testRemovesFetchedCategoryQueueMessages()
    {
        $stubCollection1 = $this->createMock(ExportQueueMessageCollection::class);
        $stubCollection1->method('getAllIds')->willReturn([3, 4]);
        $stubCollection2 = $this->createMock(ExportQueueMessageCollection::class);
        $stubCollection2->method('getAllIds')->willReturn([6, 7]);

        $this->mockResourceModelReader->method('getQueuedCategoryUpdatesGroupedByDataVersion')
            ->willReturn([$stubCollection1, $stubCollection2]);
        
        $this->mockResourceModel->expects($this->once())
            ->method('removeMessages')
            ->with([3, 4, 6, 7]);
        
        $result = $this->createExportQueue()->popQueuedCategoryUpdatesGroupedByDataVersion();
        
        $this->assertSame([$stubCollection1, $stubCollection2], $result);
    }

    public function testRemovesFetchedExportQueueMessages()
    {
        $stubCollection1 = $this->createMock(ExportQueueMessageCollection::class);
        $stubCollection1->method('getAllIds')->willReturn([3, 4]);
        $stubCollection2 = $this->createMock(ExportQueueMessageCollection::class);
        $stubCollection2->method('getAllIds')->willReturn([6, 7]);

        $this->mockResourceModelReader->method('getQueuedCatalogUpdatesGroupedByDataVersion')
            ->willReturn([$stubCollection1, $stubCollection2]);
        
        $this->mockResourceModel->expects($this->once())
            ->method('removeMessages')
            ->with([3, 4, 6, 7]);
        
        $result = $this->createExportQueue()->popQueuedUpdatesGroupedByDataVersion();
        
        $this->assertSame([$stubCollection1, $stubCollection2], $result);
    }
}
