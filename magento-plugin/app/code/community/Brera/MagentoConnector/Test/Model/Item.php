<?php

class Brera_MagentoConnector_Test_Model_Item extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @loadFixtures
     */
    public function testAddingAProductTwoTimesWithSameActionDoesntFail()
    {
        Mage::getModel('brera_magentoconnector/product_queue_item')
            ->setProductId(1)
            ->setAction(Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE)
            ->save();

        Mage::getModel('brera_magentoconnector/product_queue_item')
            ->setProductId(1)
            ->setAction(Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE)
            ->save();

        $this->assertCount(1, Mage::getResourceModel('brera_magentoconnector/product_queue_item_collection')->count());
    }
}
