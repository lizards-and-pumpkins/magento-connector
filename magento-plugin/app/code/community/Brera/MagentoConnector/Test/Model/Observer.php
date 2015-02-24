<?php

class Brera_MagentoConnector_Test_Model_Observer extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @test
     */
    public function saveProductIdOnSave()
    {
        $action = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE;
        $productId = 12;
        $event = $this->createEventObserver($productId);

        $productQueue = $this->mockProductQueue($productId, $action);

        $this->replaceByMock('model', 'brera_magentoconnector/product_queue_item', $productQueue);

        $observer = new Brera_MagentoConnector_Model_Observer();
        $observer->catalogProductSaveAfter($event);
    }

    /**
     * @test
     */
    public function saveProductIdOnDelete()
    {
        $action = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_DELETE;
        $productId = 12;
        $event = $this->createEventObserver($productId);

        $productQueue = $this->mockProductQueue($productId, $action);

        $this->replaceByMock('model', 'brera_magentoconnector/product_queue_item', $productQueue);

        $observer = new Brera_MagentoConnector_Model_Observer();
        $observer->catalogProductDeleteAfter($event);
    }

    /**
     * @test
     */
    public function saveProductIdOnAttributeMassAction()
    {
        $productIds = array(1, 2, 3, 4, 5, 6);

        $eventObserver = new Varien_Event_Observer();
        $eventObserver->setData(
            array(
                'product_ids' => $productIds
            )
        );

        $productQueue = $this->getModelMock('brera_magentoconnector/product_queue_item', array('saveProductIds'));
        $productQueue->expects($this->once())
            ->method('saveProductIds')
            ->with(
                $this->equalTo($productIds),
                $this->equalTo(Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE)
            );

        $this->replaceByMock('model', 'brera_magentoconnector/product_queue_item', $productQueue);

        Mage::getModel('brera_magentoconnector/observer')
            ->catalogProductAttributeUpdateAfter($eventObserver);
    }


    /**
     * @test
     */
    public function saveProductIdOnAttributeMassDelete()
    {
        $action = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_DELETE;
        $productId = 12;
        $event = $this->createEventObserver($productId);

        $productQueue = $this->mockProductQueue($productId, $action);

        $this->replaceByMock('model', 'brera_magentoconnector/product_queue_item', $productQueue);

        Mage::getModel('brera_magentoconnector/observer')
            ->catalogControllerProductDelete($event);
    }

    /**
     * @param $productId
     * @param $action
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    private function mockProductQueue($productId, $action)
    {
        $productQueue = $this->getModelMock('brera_magentoconnector/product_queue_item',
            array('setProductId', 'setAction', 'save'));
        $productQueue->expects($this->once())
            ->method('setProductId')
            ->with($this->equalTo($productId))
            ->will($this->returnSelf());
        $productQueue->expects($this->once())
            ->method('setAction')
            ->with($this->equalTo($action))
            ->will($this->returnSelf());
        $productQueue->expects($this->once())
            ->method('save');

        return $productQueue;
    }

    /**
     * @param $productId
     * @return Varien_Event_Observer
     */
    private function createEventObserver($productId)
    {
        $product = new Varien_Object();
        $product->setData(
            array('id' => $productId)
        );
        $event = new Varien_Event_Observer();
        $event->setData(
            array(
                'product' => $product,
            )
        );

        return $event;
    }
}
