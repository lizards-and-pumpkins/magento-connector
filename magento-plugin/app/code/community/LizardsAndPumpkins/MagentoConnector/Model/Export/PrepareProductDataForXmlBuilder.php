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

    public function __construct(
        CatalogMerge $merge,
        LizardsAndPumpkins_MagentoConnector_Model_XmlUploader $uploader
    ) {
        $this->merge = $merge;
        $this->uploader = $uploader;
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
        return array_reduce(array_keys($productData), function ($preparedData, $key) use ($productData) {
            $value = $productData[$key];
            switch ($key) {
                case 'media_gallery':
                    if (isset($productData['image'])) {
                        $preparedData['images'] = $this->prepareImagesData($value, $productData['image']);
                    }
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
            return $preparedData;
        }, []);
    }

    /**
     * @param array[] $mediaGalleryData
     * @param string $mainProductImage
     * @return array[]
     */
    private function prepareImagesData(array $mediaGalleryData, $mainProductImage)
    {
        if (! isset($mediaGalleryData['images']) || ! is_array($mediaGalleryData['images'])) {
            return [];
        }
        return array_map(function (array $image) use ($mainProductImage) {
            return [
                'main'  => $image['file'] === $mainProductImage,
                'label' => $image['label'],
                'file'  => basename($image['file']),
            ];
        }, $mediaGalleryData['images']);
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
            $associatedProduct = $this->transformData($associatedProductData);
//            $associatedProduct = [
//                'sku'       => $associatedProductData['sku'],
//                'type_id'   => $associatedProductData['type_id'],
//                'tax_class' => $associatedProductData['tax_class_id'],
//                'stock_qty' => $associatedProductData['stock_qty'],
//            ];

//            foreach ($configurableAttributes as $code) {
//                $associatedProduct['attributes'][$code] = $associatedProductData[$code];
//            }
            $preparedAssociatedProductsData[] = $associatedProduct;
        }
        return $preparedAssociatedProductsData;
    }
}
