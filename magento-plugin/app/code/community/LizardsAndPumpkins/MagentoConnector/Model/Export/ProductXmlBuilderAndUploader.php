<?php

require_once 'LizardsAndPumpkins/src/XmlBuilder/ProductBuilder.php';
require_once 'LizardsAndPumpkins/src/XmlBuilder/CatalogMerge.php';

use LizardsAndPumpkins\MagentoConnector\XmlBuilder\CatalogMerge;
use LizardsAndPumpkins\MagentoConnector\XmlBuilder\ProductBuilder;

class LizardsAndPumpkins_MagentoConnector_Model_Export_ProductXmlBuilderAndUploader
{
    /**
     * @var Mage_Catalog_Model_Resource_Product_Collection
     */
    private $product;

    /**
     * @var CatalogMerge
     */
    private $merge;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_XmlUploader
     */
    private $uploader;


    /**
     * @param Mage_Catalog_Model_Product                            $product
     * @param CatalogMerge                                          $merge
     * @param LizardsAndPumpkins_MagentoConnector_Model_XmlUploader $uploader
     */
    public function __construct(
        Mage_Catalog_Model_Product $product,
        CatalogMerge $merge,
        LizardsAndPumpkins_MagentoConnector_Model_XmlUploader $uploader
    ) {
        $this->product = $product;
        $this->merge = $merge;
        $this->uploader = $uploader;
    }

    /**
     * @return string[]
     */
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
        $xmlString = $productBuilder->getXmlString();
        $this->merge->addProduct($xmlString);
        $partialXmlString = $this->merge->getPartialXmlString() . "\n";
        $this->getUploader()->writePartialXmlString($partialXmlString);
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_XmlUploader
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
        $anySimpleProductIsAvailable = false;
        foreach ($product->getData() as $key => $value) {
            if ($key == 'media_gallery') {
                if (isset($value['images']) && is_array($value['images'])) {
                    foreach ($value['images'] as $image) {
                        $productData['images'][] = [
                            'main'  => $image['file'] == $product->getImage(),
                            'label' => $image['label'],
                            'file'  => basename($image['file']),
                        ];
                    }
                }
            } elseif ($key == 'simple_products') {
                if (is_array($value)) {
                    /** @var Mage_Catalog_Model_Product $simpleProduct */
                    foreach ($value as $simpleProduct) {
                        $anySimpleProductIsAvailable = $anySimpleProductIsAvailable || $simpleProduct->isSalable();
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
            } elseif ($key == 'is_salable') {
                $productData['is_salable'] = $this->getIsSalableFromData($product);
            } elseif ($attribute = $product->getResource()->getAttribute($key)) {
                if ($attribute->getFrontendInput() == 'multiselect') {
                    $productData[$key] = array_map('trim', explode(',', $attribute->getFrontend()->getValue($product)));
                } else {
                    $productData[$key] = $attribute->getFrontend()->getValue($product);
                }
            } else {
                $productData[$key] = $product->getDataUsingMethod($key);
            }
        }
        $productData['is_salable'] = $anySimpleProductIsAvailable && $productData['is_salable'];
        return $productData;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    private function getIsSalableFromData(Mage_Catalog_Model_Product $product)
    {
        $salable = $product->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED;

        if ($salable && $product->hasData('is_salable')) {
            return $product->getData('is_salable');
        }

        return $salable && !$product->isComposite();
    }
}
