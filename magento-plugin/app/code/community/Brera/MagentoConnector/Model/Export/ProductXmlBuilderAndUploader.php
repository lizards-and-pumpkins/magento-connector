<?php

require_once 'Brera/src/XmlBuilder/ProductBuilder.php';
require_once 'Brera/src/XmlBuilder/ProductMerge.php';

use Brera\MagentoConnector\XmlBuilder\ProductBuilder;
use Brera\MagentoConnector\XmlBuilder\ProductMerge;

class Brera_MagentoConnector_Model_Export_ProductXmlBuilderAndUploader
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

    /**
     * @var Brera_MagentoConnector_Model_XmlUploader
     */
    private $uploader;


    /**
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     * @param Mage_Core_Model_Store $store
     * @param ProductMerge $merge
     * @param Brera_MagentoConnector_Model_XmlUploader $uploader
     */
    public function __construct(
        Mage_Catalog_Model_Resource_Product_Collection $collection,
        Mage_Core_Model_Store $store,
        ProductMerge $merge,
        Brera_MagentoConnector_Model_XmlUploader $uploader
    ) {
        $this->collection = $collection;
        $this->store = $store;
        $this->merge = $merge;
        $this->uploader = $uploader;
    }


    public function getXml()
    {
        $this->process();
    }

    private function getContext()
    {
        return array(
            'website' => $this->store->getWebsite()->getCode(),
            'language' => Mage::getStoreConfig('general/locale/code', $this->store),
        );
    }

    public function process()
    {
        /** @var $product Mage_Catalog_Model_Product */
        foreach ($this->collection as $product) {
            $productContainer = (new ProductBuilder(
                $product->getData(),
                $this->getContext()
            ))->getProductContainer();
            $this->merge->addProduct($productContainer);
            $partialXmlString = $this->merge->getPartialXmlString() . "\n";
            $this->getUploader()->writePartialString($partialXmlString);
        }
    }

    private function getUploader()
    {
        return $this->uploader;
    }
}
