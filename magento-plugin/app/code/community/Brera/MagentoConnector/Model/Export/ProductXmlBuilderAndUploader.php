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
    )
    {
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
                $this->transformData($product),
                $this->getContext()
            ))->getProductContainer();
            $this->merge->addProduct($productContainer);
            $partialXmlString = $this->merge->getPartialXmlString() . "\n";
            $this->getUploader()->writePartialString($partialXmlString);
        }
    }

    /**
     * @return Brera_MagentoConnector_Model_XmlUploader
     */
    private function getUploader()
    {
        return $this->uploader;
    }

    /**
     * @param $product
     * @return string[]
     */
    private function transformData(Mage_Catalog_Model_Product $product)
    {
        $productData = array();
        foreach ($product->getData() as $key => $value) {
            if ($this->isCastableToString($value)) {
                $productData[$key] = $value;
            }
            if ($key == 'media_gallery') {
                if (isset($value['images']) && is_array($value['images'])) {
                    foreach ($value['images'] as $image) {
                        $productData['images'][] = array(
                            'main' => $image['file'] == $product->getImage(),
                            'label' => $image['label'],
                            'file' => $image['file'],
                        );
                    }
                }
            } elseif ($key == 'simple_products') {
                if (is_array($value)) {
                    /** @var Mage_Catalog_Model_Product $simpleProduct */
                    foreach ($value as $simpleProduct) {
                        $associatedProduct = array(
                            'sku' => $simpleProduct->getSku(),
                            'type' => $simpleProduct->getTypeId(),
                            'visible' => $simpleProduct->getVisibility(),
                            'tax_class_id' => $simpleProduct->getTaxClassId(),
                            'stock_qty' => $simpleProduct->getStockQty(),
                        );

                        foreach ($product->getConfigurableAttributes() as $attribute) {
                            $associatedProduct['attributes'][$attribute] = $simpleProduct->getAttributeText($attribute);
                        }
                        $productData['associated_products'][] = $associatedProduct;
                    }
                }
            } elseif ($key == 'configurable_attributes') {
                if (is_array($value)) {
                    $productData['variations'] = $value;
                }
            }
        }

        return $productData;
    }

    private function isCastableToString($value)
    {
        return is_string($value) || is_float($value) || is_bool($value) || is_int($value) || is_double($value);
    }
}
