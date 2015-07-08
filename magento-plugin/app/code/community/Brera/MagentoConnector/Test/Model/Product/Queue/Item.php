<?php

/**
 * Class Brera_MagentoConnector_Test_Model_Item
 *
 * @covers Brera_MagentoConnector_Model_Product_Queue_Item
 */
class Brera_MagentoConnector_Test_Model_Product_Queue_Item extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @loadFixture
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

        $this->assertEquals(1, Mage::getResourceModel('brera_magentoconnector/product_queue_item_collection')->count());
    }

    /**
     * @param int[] $productIds
     * @dataProvider getProductIds
     */
    public function testSavingOfProductIds($productIds)
    {
        $action = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE;


        /** @var $resourceMock EcomDev_PHPUnit_Mock_Proxy|Brera_MagentoConnector_Model_Resource_Product_Queue_Item */
        $resourceMock = $this->getResourceModelMock('brera_magentoconnector/product_queue_item');
        $resourceMock->expects($this->once())->method('saveProductIds')->with($productIds, $action);
        $this->replaceByMock('resource_model', 'brera_magentoconnector/product_queue_item', $resourceMock);

        Mage::getModel('brera_magentoconnector/product_queue_item')
            ->saveProductIds($productIds, $action);
    }

    public function getProductIds()
    {
        return [
            [[1]],
            [[1, 3, 4, 5]],
        ];
    }
}
