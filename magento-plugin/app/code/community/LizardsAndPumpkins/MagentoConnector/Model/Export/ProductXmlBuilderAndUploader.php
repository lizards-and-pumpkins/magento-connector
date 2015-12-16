<?php

use LizardsAndPumpkins\MagentoConnector\XmlBuilder\CatalogMerge;
use LizardsAndPumpkins\MagentoConnector\XmlBuilder\ProductBuilder;

class LizardsAndPumpkins_MagentoConnector_Model_Export_ProductXmlBuilderAndUploader
{
    /**
     * @var CatalogMerge
     */
    private $merge;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_XmlUploader
     */
    private $uploader;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Export_SourceTableDataProvider
     */
    private $sourceTableDataProvider;

    public function __construct(
        CatalogMerge $merge,
        LizardsAndPumpkins_MagentoConnector_Model_XmlUploader $uploader,
        LizardsAndPumpkins_MagentoConnector_Model_Export_SourceTableDataProvider $sourceTableDataProvider
    ) {
        $this->merge = $merge;
        $this->uploader = $uploader;
        $this->sourceTableDataProvider = $sourceTableDataProvider;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return string[]
     */
    private function getContext(Mage_Catalog_Model_Product $product)
    {
        return [
            'website' => $product->getStore()->getWebsite()->getCode(),
            'locale'  => Mage::getStoreConfig('general/locale/code', $product->getStore()),
        ];
    }

    /**
     * @param mixed[] $product
     */
    public function process(array $product)
    {
        $productBuilder = new ProductBuilder(
            $this->transformData($product),
            $this->getContext($product)
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
     * @param mixed[] $product
     * @return string[]
     */
    private function transformData(array $product)
    {
        $productData = [];
        $anySimpleProductIsAvailable = false;
        foreach ($product as $key => $value) {
            if (!$product[$key]) {
                $productData[$key] = $product[$key];
            } elseif ($key == 'media_gallery') {
                if (isset($value['images']) && is_array($value['images'])) {
                    foreach ($value['images'] as $image) {
                        $productData['images'][] = [
                            'main'  => $image['file'] == $product['image'],
                            'label' => $image['label'],
                            'file'  => basename($image['file']),
                        ];
                    }
                }
            } elseif ($key == 'associated_products') {
                if (is_array($value)) {
                    foreach ($value as $simpleProduct) {
                        $anySimpleProductIsAvailable = true;
                        $associatedProduct = [
                            'sku'          => $simpleProduct['sku'],
                            'type_id'      => $simpleProduct['type_id'],
                            'tax_class_id' => $simpleProduct['tax_class_id'],
                            'stock_qty'    => $simpleProduct['stock_qty'],
                        ];

                        foreach ($product['configurable_attributes'] as $attribute) {
                            $associatedProduct['attributes'][$attribute] = $simpleProduct[$attribute];
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
            } elseif (($attribute = $product->getResource()->getAttribute($key))
                && $this->isAttributeSelectOrMultiselect($attribute)
            ) {
                if ($attribute->getData('source_model') == 'eav/entity_attribute_source_table') {
                    $productData[$key] = array_map(
                        function ($valueId) use ($product, $key) {
                            return $this->sourceTableDataProvider->getValue($product->getStoreId(), $key, $valueId);
                        }, explode(',', $product->getData($key)));
                } else {
                    $productData[$key] = array_map('trim', explode(',', $attribute->getFrontend()->getValue($product)));
                }
            } else {
                $productData[$key] = $product->getDataUsingMethod($key);
            }

            if (isset($productData[$key]) && is_array($productData[$key]) && count($productData[$key]) == 1) {
                $productData[$key] = reset($productData[$key]);
            }
        }
        $productData['is_salable'] = $anySimpleProductIsAvailable && $productData['is_salable'];
        return $productData;
    }

    /**
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @return bool
     */
    private function isAttributeSelectOrMultiselect(Mage_Catalog_Model_Resource_Eav_Attribute $attribute)
    {
        return in_array($attribute->getData('frontend_input'), ['multiselect', 'select']);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    private function getIsSalableFromData(Mage_Catalog_Model_Product $product)
    {
        $salable = $product->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED;

        if ($salable && $product->hasData('is_salable')) {
            return (bool) $product->getData('is_salable');
        }

        return $salable && !$product->isComposite();
    }
}
