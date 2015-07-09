<?php

/**
 * Class Brera_MagentoConnector_Test_Model_Observer
 *
 * @covers Brera_MagentoConnector_Model_Observer
 */
class Brera_MagentoConnector_Test_Model_Observer extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @var Brera_MagentoConnector_Model_Observer
     */
    private $observer;

    /**
     * @test
     */
    public function saveProductIdOnSave()
    {
        $action = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE;
        $event = $this->createEventObserver();
        $this->setupEventWith($action);

        $this->observer->catalogProductSaveAfter($event);
    }

    /**
     * @test
     */
    public function saveProductIdOnDelete()
    {
        $action = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_DELETE;
        $event = $this->createEventObserver();
        $this->setupEventWith($action);

        $this->observer->catalogProductDeleteAfter($event);
    }

    /**
     * @test
     */
    public function saveProductIdOnAttributeMassAction()
    {
        $productIds = [1, 2, 3, 4, 5, 6];

        $eventObserver = new Varien_Event_Observer();
        $eventObserver->setData(
            [
                'product_ids' => $productIds
            ]
        );

        $productQueue = $this->getModelMock('brera_magentoconnector/product_queue_item', ['saveProductIds']);
        $productQueue->expects($this->once())
            ->method('saveProductIds')
            ->with(
                $this->equalTo($productIds),
                $this->equalTo(Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE)
            );

        $this->replaceByMock('model', 'brera_magentoconnector/product_queue_item', $productQueue);

        $this->observer->catalogProductAttributeUpdateAfter($eventObserver);
    }

    /**
     * @test
     */
    public function saveProductIdOnAttributeMassDelete()
    {
        $action = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_DELETE;
        $event = $this->createEventObserver();
        $this->setupEventWith($action);

        $this->observer->catalogControllerProductDelete($event);
    }

    public function testCataloginventoryStockItemSaveCommitAfterIsLogged()
    {
        $observerMock = $this->setupForItemOnlyTest();

        $this->observer->cataloginventoryStockItemSaveCommitAfter($observerMock);
    }

    public function testSalesModelServiceQuoteSubmitBeforeIsLogged()
    {
        $itemHolder = 'quote';
        $observerMock = $this->setupTestWith($itemHolder);

        $this->observer->salesModelServiceQuoteSubmitBefore($observerMock);
    }

    public function testSalesModelServiceQuoteSubmitFailureIsLogged()
    {
        $itemHolder = 'quote';
        $observerMock = $this->setupTestWith($itemHolder);

        $this->observer->salesModelServiceQuoteSubmitFailure($observerMock);
    }

    public function testSalesOrderItemCancelIsLogged()
    {
        $observerMock = $this->setupForItemOnlyTest();

        $this->observer->salesOrderItemCancel($observerMock);
    }

    public function testSalesOrderCreditmemoSaveAfterIsLogged()
    {
        $itemHolder = 'creditmemo';
        $observerMock = $this->setupTestWith($itemHolder);

        $this->observer->salesOrderCreditmemoSaveAfter($observerMock);
    }

    public function testListenOnCobbyEvent()
    {
        $skus = ['a', 'b', 'c', 'd'];
        $observerMock = $this->getMock(Varien_Event_Observer::class, ['getEntities']);
        /** @var $observerMock PHPUnit_Framework_MockObject_InvocationMocker|Varien_Event_Observer */
        $observerMock->expects($this->any())->method('getEntities')->willReturn($skus);

        $this->mockProductQueueForSkus(
            $skus,
            Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE
        );

        $this->observer->cobbyAfterProductImport($observerMock);
    }

    public function testListenOnMagmiEvent()
    {
        $skus = ['a', 'b', 'c', 'd'];
        $observerMock = $this->getMock(Varien_Event_Observer::class, ['getSkus']);
        /** @var $observerMock PHPUnit_Framework_MockObject_InvocationMocker|Varien_Event_Observer */
        $observerMock->expects($this->any())->method('getSkus')->willReturn($skus);

        $this->mockProductQueueForSkus(
            $skus,
            Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_STOCK_UPDATE
        );

        $this->observer->magmiStockWasUpdated($observerMock);
    }

    protected function setUp()
    {
        $this->observer = new Brera_MagentoConnector_Model_Observer();
    }

    /**
     * @param int[] $ids
     * @return Varien_Object[]
     */
    private function getItemsWithId($ids)
    {
        $items = [];
        foreach ($ids as $id) {
            $mock = $this->getMock(Varien_Object::class, ['getProductId']);
            $mock->expects($this->any())->method('getProductId')->willReturn($id);
            $items[] = $mock;
        }

        return $items;
    }

    /**
     * @param string $action
     * @param int[] $productId
     * @return Varien_Event_Observer
     */
    private function setupEventWith($action, $productId = [12])
    {
        $productQueue = $this->mockProductQueueForIds($productId, $action);
        $this->replaceByMock('singleton', 'brera_magentoconnector/product_queue_item', $productQueue);
    }

    /**
     * @param  int $productId
     * @return Varien_Event_Observer
     */
    private function createEventObserver($productId = 12)
    {
        $product = new Varien_Object();
        $product->setData(
            ['id' => $productId]
        );
        $event = new Varien_Event_Observer();
        $event->setData(['product' => $product]);

        return $event;
    }

    /**
     * @param int[] $productIds
     * @param string $action
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    private function mockProductQueueForIds($productIds, $action)
    {
        return $this->mockProductQueue($productIds, $action, 'ids');
    }

    /**
     * @param int[] $skus
     * @param string $action
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    private function mockProductQueueForSkus($skus, $action)
    {
        $productQueue = $this->mockProductQueue($skus, $action, 'skus');
        $this->replaceByMock('singleton', 'brera_magentoconnector/product_queue_item', $productQueue);
    }

    /**
     * @return EcomDev_PHPUnit_Mock_Proxy|Varien_Event_Observer
     */
    private function setupForItemOnlyTest()
    {
        $itemMock = $this->getModelMock('cataloginventory/stock_item', ['getProductId']);
        $productId = 4;
        $itemMock->expects($this->any())
            ->method('getProductId')
            ->willReturn($productId);
        /** @var $observerMock EcomDev_PHPUnit_Mock_Proxy|Varien_Event_Observer */
        $observerMock = $this->getMock(Varien_Event_Observer::class, ['getItem']);
        $observerMock->expects($this->any())
            ->method('getItem')
            ->willReturn($itemMock);

        $this->setupEventWith(Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_STOCK_UPDATE, [$productId]);

        return $observerMock;
    }

    /**
     * @param string $itemHolder
     * @return PHPUnit_Framework_MockObject_MockObject|Varien_Event_Observer
     */
    private function setupTestWith($itemHolder)
    {
        $productIds = [1, 3, 4];
        $quoteItems = $this->getItemsWithId($productIds);

        $quoteMock = $this->getModelMock("sales/$itemHolder", ['getAllItems'], false, [], '', false);
        $quoteMock->expects($this->any())
            ->method('getAllItems')
            ->willReturn($quoteItems);

        /** @var $observerMock Varien_Event_Observer|PHPUnit_Framework_MockObject_MockObject */
        $observerMock = $this->getMock(Varien_Event_Observer::class, ['get' . ucfirst($itemHolder)]);
        $observerMock->expects($this->any())
            ->method('get' . ucfirst($itemHolder))
            ->willReturn($quoteMock);

        $this->setupEventWith(Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_STOCK_UPDATE, $productIds);

        return $observerMock;
    }

    /**
     * @param int[] $identifier
     * @param string $action
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    private function mockProductQueue($identifier, $action, $for)
    {
        $productQueue = $this->getModelMock(
            'brera_magentoconnector/product_queue_item',
            ['saveProduct' . ucfirst($for)]
        );
        $productQueue->expects($this->once())
            ->method('saveProduct' . ucfirst($for))
            ->with(
                $this->equalTo($identifier),
                $this->equalTo($action)
            );

        return $productQueue;
    }
}
