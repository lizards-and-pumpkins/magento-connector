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

    public function testSaveProductIdOnSave()
    {
        $action = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE;
        $event = $this->createEventObserver();
        $this->setupEventWith($action);

        $this->observer->catalogProductSaveAfter($event);
    }

    public function testSaveProductIdOnDelete()
    {
        $action = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_DELETE;
        $event = $this->createEventObserver();
        $this->setupEventWith($action);

        $this->observer->catalogProductDeleteAfter($event);
    }

    public function testSaveProductIdOnAttributeMassAction()
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

    public function testSaveProductIdOnAttributeMassDelete()
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
        $this->checkSkuObserver(
            'getEntities',
            'cobbyAfterProductImport',
            Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE
        );
    }

    public function testListenOnMagmiProductEvent()
    {
        $this->checkSkuObserver('getSkus', 'magmiStockWasUpdated');
    }

    public function testListenOnMagemiProdctUpdateEvent()
    {
        $this->checkSkuObserver(
            'getSkus',
            'magmiProductsWereUpdated',
            Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE
        );
    }

    public function testAddToCartFormKeyIsChanged()
    {
        $wrongFormKey = 'wrongFormKey';
        $rightFormKey = 'rightFormKey';

        $coreSessionMock = $this->getModelMock('core/session', ['getFormKey'], false, [], '', false);
        $coreSessionMock->method('getFormKey')->willReturn($rightFormKey);
        $this->replaceByMock('singleton', 'core/session', $coreSessionMock);

        $request = new Mage_Core_Controller_Request_Http();
        $request->setPost('form_key', $wrongFormKey);

        $this->assertNotEquals($rightFormKey, $request->getParam('form_key'));
        $this->assertEquals($wrongFormKey, $request->getParam('form_key'));

        // controller needs to be included by hand because magento doesn't autoload it
        // if it is not included PHPUnit creates the class Mage_Checkout_CartController
        // which fucks up later tests
        require Mage::getBaseDir() . '/app/code/core/Mage/Checkout/controllers/CartController.php';
        $controller = $this->getMock(Mage_Checkout_CartController::class, ['getRequest'], [], '', false);
        $controller->method('getRequest')->willReturn($request);

        $event = new Varien_Object();
        $event->setData(
            ['controller_action' => $controller]
        );
        $observer = new Varien_Event_Observer();
        $observer->setData(['controller_action' => $controller]);

        $this->observer->controllerActionPredispatchCheckoutCartAdd($observer);

        $this->assertEquals($rightFormKey, $request->getParam('form_key'));

        return $observer;
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

    /**
     * @param string $getSkuMethodName
     * @param string $observerMethod
     * @param string $action
     */
    private function checkSkuObserver(
        $getSkuMethodName,
        $observerMethod,
        $action = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_STOCK_UPDATE
    ) {
        $skus = ['a', 'b', 'c', 'd'];
        $observerMock = $this->getMock(Varien_Event_Observer::class, [$getSkuMethodName]);
        /** @var $observerMock PHPUnit_Framework_MockObject_InvocationMocker|Varien_Event_Observer */
        $observerMock->expects($this->any())->method($getSkuMethodName)->willReturn($skus);

        $this->mockProductQueueForSkus(
            $skus,
            $action
        );

        $this->observer->$observerMethod($observerMock);
    }
}
