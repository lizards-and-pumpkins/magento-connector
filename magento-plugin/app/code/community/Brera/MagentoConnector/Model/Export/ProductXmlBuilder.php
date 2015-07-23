<?php

use Brera\MagentoConnector\Xml\Product\ProductBuilder;
use Brera\MagentoConnector\Xml\Product\ProductMerge;

class Brera_MagentoConnector_Model_Export_ProductXmlBuilder
{
    /**
     * @var Mage_Catalog_Model_Resource_Product_Collection
     */
    private $collection;
    /**
     * @var Mage_Core_Model_Store
     */
    private $store;
    /**
     * @var ProductMerge
     */
    private $merge;


    public function __construct(
        Mage_Catalog_Model_Resource_Product_Collection $collection,
        Mage_Core_Model_Store $store,
        ProductMerge $merge
    ) {
        $this->collection = $collection;
        $this->store = $store;
        $this->merge = $merge;
    }

    public function getXml()
    {
        /** @var $product Mage_Catalog_Model_Product */
        foreach ($this->collection as $product) {
            $productContainer = (new ProductBuilder(
                $product->getData(),
                $this->getContext()
            ))->getProductContainer();
            $this->merge->addProduct($productContainer);
        }

        return $this->merge->getXmlString();
    }

    private function getContext()
    {
        return [];
    }
}
