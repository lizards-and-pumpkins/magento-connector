<?php

/**
 * Class Brera_MagentoConnector_Test_Model_Xml_Product_Collection
 *
 * @covers Brera_MagentoConnector_Model_Xml_Product_Collection
 */
class Brera_MagentoConnector_Test_Model_Xml_Product_Collection extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @var Brera_MagentoConnector_Model_Xml_Product_Collection
     */
    private $collection;

    protected function setUp()
    {
        $this->collection = new Brera_MagentoConnector_Model_Xml_Product_Collection();
    }


    public function testIsEmptyAfterCreate()
    {
        $this->assertCount(0, $this->collection);
    }

    public function testAddProductRaisesCount()
    {
        $product = $this->getProductWithId(1);

        $this->collection->addProduct($product);
        $this->assertCount(1, $this->collection);
    }

    public function testProductIsOnlyAddedOnce()
    {
        $product = $this->getProductWithId(1);

        $this->collection->addProduct($product);
        $this->collection->addProduct($product);
        $this->assertCount(1, $this->collection);
    }

    public function testTwoDifferentProductsCanBeAdded()
    {
        $this->collection->addProduct($this->getProductWithId(1));
        $this->collection->addProduct($this->getProductWithId(2));
        $this->assertCount(2, $this->collection);
    }

    public function testProductIsInIterator()
    {
        $product1 = $this->getProductWithId(1);
        $this->collection->addProduct($product1);

        $product2 = $this->getProductWithId(2);
        $this->collection->addProduct($product2);
        $productArray[] = $product2;
        $productArray[] = $product1;
        foreach ($this->collection as $product) {
            $this->assertSame(array_pop($productArray), $product);
        }
    }

    /**
     * @param int $id
     * @return EcomDev_PHPUnit_Mock_Proxy|Mage_Catalog_Model_Product
     */
    private function getProductWithId($id)
    {
        $product = $this->getModelMock('catalog/product', ['getId'], false, [], [], false);
        $product->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $product;
    }
}
