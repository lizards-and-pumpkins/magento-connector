<?php

class Brera_MagentoConnector_Test_Model_Resource_Product_Queue_Item_Collection extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @var Brera_MagentoConnector_Model_Resource_Product_Queue_Item_Collection
     */
    private $collection;

    protected function setUp()
    {
        $this->collection = Mage::getResourceModel('brera_magentoconnector/product_queue_item_collection');
    }


    public function testCollectionIsItemCollection()
    {
        $this->assertInstanceOf(
            Brera_MagentoConnector_Model_Resource_Product_Queue_Item_Collection::class,
            $this->collection
        );
    }
}
