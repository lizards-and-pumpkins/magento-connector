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
    private $product;

    /**
     * @var ProductMerge
     */
    private $merge;

    /**
     * @var Brera_MagentoConnector_Model_XmlUploader
     */
    private $uploader;


    /**
     * @param Mage_Catalog_Model_Product               $product
     * @param ProductMerge                             $merge
     * @param Brera_MagentoConnector_Model_XmlUploader $uploader
     */
    public function __construct(
        Mage_Catalog_Model_Product $product,
        ProductMerge $merge,
        Brera_MagentoConnector_Model_XmlUploader $uploader
    ) {
        $this->product = $product;
        $this->merge = $merge;
        $this->uploader = $uploader;
    }


    private function getContext()
    {
        return [
            'website' => $this->product->getStore()->getWebsite()->getCode(),
            'locale'  => Mage::getStoreConfig('general/locale/code', $this->product->getStore()),
        ];
    }

    public function process()
    {
        /** @var $product Mage_Catalog_Model_Product */
        $productBuilder = new ProductBuilder(
            $this->transformData($this->product),
            $this->getContext()
        );
        $productContainer = $productBuilder->getProductContainer();
        $this->merge->addProduct($productContainer);
        $partialXmlString = $this->merge->getPartialXmlString() . "\n";
        $this->getUploader()->writePartialXmlString($partialXmlString);
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
        $productData = [];
        foreach ($product->getData() as $key => $value) {
            if ($key == 'media_gallery') {
                if (isset($value['images']) && is_array($value['images'])) {
                    foreach ($value['images'] as $image) {
                        $productData['images'][] = [
                            'main'  => $image['file'] == $product->getImage(),
                            'label' => $image['label'],
                            'file'  => $image['file'],
                        ];
                    }
                }
            } elseif ($key == 'simple_products') {
                if (is_array($value)) {
                    /** @var Mage_Catalog_Model_Product $simpleProduct */
                    foreach ($value as $simpleProduct) {
                        $associatedProduct = [
                            'sku'          => $simpleProduct->getSku(),
                            'type_id'      => $simpleProduct->getTypeId(),
                            'visibility'   => $simpleProduct->getAttributeText('visibility'),
                            'tax_class_id' => $simpleProduct->getAttributeText('tax_class_id'),
                            'stock_qty'    => $simpleProduct->getStockQty(),
                        ];

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
            } elseif ($product->getResource()->getAttribute($key)) {
                $productData[$key] = $product->getResource()->getAttribute($key)->getFrontend()->getValue($product);
            } else {
                $productData[$key] = $product->getDataUsingMethod($key);
            }
        }
        return $productData;
    }
}
