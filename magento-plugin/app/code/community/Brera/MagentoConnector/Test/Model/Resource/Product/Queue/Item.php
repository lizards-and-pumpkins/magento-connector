<?php

/**
 * Class Brera_MagentoConnector_Test_Model_Resource_Product_Queue_Item
 *
 * @covers Brera_MagentoConnector_Model_Resource_Product_Queue_Item
 * @uses   Brera_MagentoConnector_Model_Product_Queue_Item
 */
class Brera_MagentoConnector_Test_Model_Resource_Product_Queue_Item extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @var Brera_MagentoConnector_Model_Resource_Product_Queue_Item
     */
    private $resource;

    protected function setUp()
    {
        $this->resource = Mage::getResourceModel('brera_magentoconnector/product_queue_item');

        $collection = Mage::getResourceModel('brera_magentoconnector/product_queue_item_collection');
        $conn = $collection->getConnection();
        $conn->truncateTable($collection->getMainTable());
    }

    /**
     * @loadFixture products
     * @medium
     */
    public function testSaving()
    {
        $itemMock = new Brera_MagentoConnector_Model_Product_Queue_Item();
        $itemMock->setAction(Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_DELETE)
            ->setProductId(4);

        $this->resource->save($itemMock);
    }

    public function testDeleting()
    {
        $itemMock = new Brera_MagentoConnector_Model_Product_Queue_Item();
        $itemMock->setAction(Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_DELETE)
            ->setProductId(4)
            ->isDeleted(true);

        $this->resource->save($itemMock);
    }

    /**
     * @param int[] $productIds
     * @dataProvider getProductIds
     * @loadFixture products
     * @medium
     */
    public function testSavingOfProductIds($productIds)
    {
        $action = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE;

        Mage::getResourceModel('brera_magentoconnector/product_queue_item')
            ->saveProductIds($productIds, $action);

        $this->assertEquals(
            count($productIds),
            Mage::getResourceModel('brera_magentoconnector/product_queue_item_collection')->count()
        );
    }

    /**
     * @param int[] $skus
     * @dataProvider getProductSkus
     * @loadFixture products
     * @medium
     */
    public function testSavingOfProductSkus($skus)
    {
        $action = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE;

        Mage::getResourceModel('brera_magentoconnector/product_queue_item')
            ->saveProductSkus($skus, $action);

        $this->assertEquals(
            count($skus),
            Mage::getResourceModel('brera_magentoconnector/product_queue_item_collection')->count()
        );
    }

    /**
     * @return int[][][]
     */
    public function getProductIds()
    {
        return [
            [[1]],
            [[1, 2, 3, 4]],
        ];
    }

    /**
     * @return string[][][]
     */
    public function getProductSkus()
    {
        return [
            [['book1']],
            [['book1', 'book2', 'book3', 'book4']],
        ];
    }

}
