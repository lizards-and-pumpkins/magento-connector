<?php
declare(strict_types=1);

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

    private $attributesToExclude = ['tax_class_id'];

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
     * @return bool
     */
    private function isMainImageSet(array $productData)
    {
        return isset($productData['image']) && trim($productData['image']) !== '';
    }

    /**
     * @param mixed[] $productData
     * @return bool
     */
    private function hasMediaGalleryImages(array $productData)
    {
        $gallery = $productData['media_gallery'];
        return is_array($gallery['images']) && count($gallery['images']);
    }

    /**
     * @param mixed[] $productData
     * @return string
     */
    private function getFirstImageFromMediaGallery(array $productData)
    {
        return $productData['media_gallery']['images'][0]['file'];
    }
    
    /**
     * @param mixed[] $productData
     * @return string
     */
    private function getMainProductImage(array $productData)
    {
        if ($this->isMainImageSet($productData)) {
            $mainImage = $productData['image'];
        } elseif ($this->hasMediaGalleryImages($productData)) {
            $mainImage = $this->getFirstImageFromMediaGallery($productData);
        } else {
            $mainImage = '';
        }
        return $mainImage;
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
                    if (isset($value['images'])) {
                        $mainImage = $this->getMainProductImage($productData);
                        $preparedData['images'] = $this->prepareImagesData($value, $mainImage);
                    }
                    break;

                case 'associated_products':
                    $associatedProducts = $this->prepareAssociatedProductsData($value);
                    $preparedData['associated_products'] = $associatedProducts;
                    break;

                case 'configurable_attributes':
                    if (is_array($value) && count($value) > 0) {
                        $preparedData['variations'] = $value;
                    }
                    break;

                case 'type_id':
                case 'sku':
                case 'tax_class':
                    $preparedData[$key] = $value;
                    break;

                case 'website':
                case 'locale':
                    break;

                default:
                    if (!in_array($key, $this->attributesToExclude)) {
                        $preparedData['attributes'][$key] = $value;
                    }
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
        if (!is_array($mediaGalleryData['images'])) {
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
     * @return array[]
     */
    private function prepareAssociatedProductsData(array $associatedProductsData)
    {
        return array_map(function (array $associatedProductData) {
            return $this->transformData($associatedProductData);
        }, $associatedProductsData);
    }
}
