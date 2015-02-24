<?php

class Brera_MagentoConnector_Test_Model_Observer extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @test
     */
    public function saveProductIdOnSave()
    {
        $product = new Varien_Object();
        $productId = 12;
        $action = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE;
        $product->setData(
            array('id' => $productId)
        );
        $event = new Varien_Event_Observer();
        $event->setData(
            array(
                'product' => $product,
            )
        );

        $productQueue = $this->getModelMock('brera_magentoconnector/product_queue_item', array('setProductId', 'setAction', 'save'));
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

        $this->replaceByMock('model', 'brera_magentoconnector/product_queue_item', $productQueue);

        $observer = new Brera_MagentoConnector_Model_Observer();
        $observer->catalogProductSaveAfter($event);
    }

    /**
     * @test
     */
    public function saveProductIdOnDelete()
    {

    }
}
