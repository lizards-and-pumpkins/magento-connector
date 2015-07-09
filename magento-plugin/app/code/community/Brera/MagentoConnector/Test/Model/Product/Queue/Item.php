<?php

/**
 * Class Brera_MagentoConnector_Test_Model_Item
 *
 * @covers  Brera_MagentoConnector_Model_Product_Queue_Item
 * @uses    Brera_MagentoConnector_Model_Resource_Product_Queue_Item
 */
class Brera_MagentoConnector_Test_Model_Product_Queue_Item extends EcomDev_PHPUnit_Test_Case
{
    protected function setUp()
    {
        $collection = Mage::getResourceModel('brera_magentoconnector/product_queue_item_collection');
        $conn = $collection->getConnection();
        $conn->truncateTable($collection->getMainTable());
    }

    /**
     * @loadFixture
     * @medium
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
     * @medium
     */
    public function testSavingOfProductIds($productIds)
    {
        $action = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE;
        $this->setupAndReplaceResourceModel($productIds, $action, 'ids');

        $productQueueItem = Mage::getModel('brera_magentoconnector/product_queue_item');
        $productQueueItem->saveProductIds($productIds, $action);
    }

    /**
     * @param string[] $skus
     * @dataProvider getProductIds
     * @medium
     */
    public function testSavingOfProductSkus($skus)
    {
        $action = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE;

        $this->setupAndReplaceResourceModel($skus, $action, 'skus');

        $productQueueItem = Mage::getModel('brera_magentoconnector/product_queue_item');
        $productQueueItem->saveProductSkus($skus, $action);
    }

    public function getProductIds()
    {
        return [
            [[1]],
            [[1, 3, 4, 5]],
        ];
    }

    /**
     * @param string[]|int[] $identifier
     * @param string $action
     * @param string $for
     */
    private function setupAndReplaceResourceModel($identifier, $action, $for)
    {
        /** @var $resourceMock EcomDev_PHPUnit_Mock_Proxy|Brera_MagentoConnector_Model_Resource_Product_Queue_Item */
        $resourceMock = $this->getResourceModelMock(
            'brera_magentoconnector/product_queue_item',
            ['saveProduct' . ucfirst($for)]
        );
        $resourceMock->expects($this->once())->method('saveProduct' . ucfirst($for))->with($identifier, $action);
        $this->replaceByMock('resource_model', 'brera_magentoconnector/product_queue_item', $resourceMock);
    }
}
