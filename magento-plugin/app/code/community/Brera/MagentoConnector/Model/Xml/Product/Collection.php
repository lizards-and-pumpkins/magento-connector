<?php

class Brera_MagentoConnector_Model_Xml_Product_Collection implements IteratorAggregate, Countable
{

    /**
     * @var Mage_Catalog_Model_Product[]
     */
    private $products;

    public function getIterator()
    {
        return new ArrayIterator($this->products);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->products);
    }

    public function addProduct(Mage_Catalog_Model_Product $product)
    {
        $this->products[$product->getId()] = $product;
    }
}
