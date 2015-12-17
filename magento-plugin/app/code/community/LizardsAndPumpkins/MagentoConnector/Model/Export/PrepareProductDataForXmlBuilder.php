<?php

use LizardsAndPumpkins\MagentoConnector\XmlBuilder\CatalogMerge;
use LizardsAndPumpkins\MagentoConnector\XmlBuilder\ProductBuilder;

class LizardsAndPumpkins_MagentoConnector_Model_Export_PrepareProductDataForXmlBuilder
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
     * @param mixed[] $productData
     * @return string[]
     */
    private function getContextData(array $productData)
    {
        return [
            'website' => $productData['website'],
            'locale'  => $productData['locale'],
        ];
    }

    /**
     * @param mixed[] $productData
     */
    public function process(array $productData)
    {
        $productBuilder = new ProductBuilder(
            $this->transformData($productData),
            $this->getContextData($productData)
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
     * @param mixed[] $productData
     * @return mixed[]
     */
    private function transformData(array $productData)
    {
        $preparedData = [];
        foreach ($productData as $key => $value) {
            switch ($key) {
                case 'media_gallery':
                    $preparedData['images'] = $this->prepareImagesData($value, $productData['image']);
                    break;

                case 'associated_products':
                    $configurableAttributes = $productData['configurable_attributes'];
                    $associatedProducts = $this->prepareAssociatedProductsData($value, $configurableAttributes);
                    $preparedData['associated_products'] = $associatedProducts;
                    break;

                case 'configurable_attributes':
                    if (is_array($value) && count($value) > 0) {
                        $preparedData['variations'] = $value;
                    }
                    break;

                case 'tax_class_id':
                    $preparedData['tax_class'] = $value;
                    break;

                case 'type_id':
                case 'sku':
                    $preparedData[$key] = $value;
                    break;
                
                case 'website':
                case 'locale':
                    break;

                default:
                    $preparedData['attributes'][$key] = $value;
                    break;
            }
        }
        return $preparedData;
    }

//    /**
//     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
//     * @return bool
//     */
//    private function isAttributeSelectOrMultiselect(Mage_Catalog_Model_Resource_Eav_Attribute $attribute)
//    {
//        return in_array($attribute->getData('frontend_input'), ['multiselect', 'select']);
//    }
//
//    /**
//     * @param mixed[] $productData
//     * @return bool
//     */
//    private function getIsSalableFromData(array $productData)
//    {
//        $salable = $productData['status'] == Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
//
//        if ($salable && $productData['is_salable']) {
//            return (bool) $productData['is_salable'];
//        }
//
//        return $salable && count($productData['associated_products']) === 0;
//    }

    /**
     * @param array[] $mediaGalleryData
     * @param string $mainProductImage
     * @return array[]
     */
    private function prepareImagesData(array $mediaGalleryData, $mainProductImage)
    {
        $preparedImages = [];
        if (isset($mediaGalleryData['images']) && is_array($mediaGalleryData['images'])) {
            foreach ($mediaGalleryData['images'] as $image) {
                $preparedImages[] = [
                    'main'  => $image['file'] === $mainProductImage,
                    'label' => $image['label'],
                    'file'  => basename($image['file']),
                ];
            }
        }
        return $preparedImages;
    }

    /**
     * @param array[] $associatedProductsData
     * @param string[] $configurableAttributes
     * @return array[]
     */
    private function prepareAssociatedProductsData(array $associatedProductsData, array $configurableAttributes)
    {
        $preparedAssociatedProductsData = [];
        foreach ($associatedProductsData as $associatedProductData) {
            $associatedProduct = [
                'sku'       => $associatedProductData['sku'],
                'type_id'   => $associatedProductData['type_id'],
                'tax_class' => $associatedProductData['tax_class_id'],
                'stock_qty' => $associatedProductData['stock_qty'],
            ];

            foreach ($configurableAttributes as $code) {
                $associatedProduct['attributes'][$code] = $associatedProductData[$code];
            }
            $preparedAssociatedProductsData[] = $associatedProduct;
        }
        return $preparedAssociatedProductsData;
    }
}
