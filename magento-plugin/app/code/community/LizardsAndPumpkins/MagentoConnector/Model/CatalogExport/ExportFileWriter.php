<?php

use LizardsAndPumpkins\MagentoConnector\Images\ImageExporter;
use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_CatalogDataForStoresCollector as CatalogDataForStoresCollector;
use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_ProductDataCollectionFactory as ProductDataCollectionFactory;
use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_CategoryDataCollectionFactory as CategoryDataCollectionFactory;
use LizardsAndPumpkins\MagentoConnector\XmlBuilder\CatalogMerge;
use LizardsAndPumpkins\MagentoConnector\Images\ImagesCollector;

class LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFileWriter
{
    const IMAGE_BASE_PATH = '/catalog/product';

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Helper_Factory
     */
    private $factory;

    /**
     * @var CatalogDataForStoresCollector
     */
    private $catalogDataForStoresCollector;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_ProductDataCollectionFactory
     */
    private $productDataCollectionFactory;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_CategoryDataCollectionFactory
     */
    private $categoryDataCollectionFactory;

    /**
     * @var ImageExporter
     */
    private $imageExporter;

    /**
     * @param LizardsAndPumpkins_MagentoConnector_Helper_Factory $factory
     * @param CatalogDataForStoresCollector $catalogDataForStoresCollector
     * @param ProductDataCollectionFactory $productDataCollectionFactory
     * @param CategoryDataCollectionFactory $categoryDataCollectionFactory
     * @param ImageExporter|null $imageExporter
     */
    public function __construct(
        $factory = null,
        CatalogDataForStoresCollector $catalogDataForStoresCollector = null,
        ProductDataCollectionFactory $productDataCollectionFactory = null,
        CategoryDataCollectionFactory $categoryDataCollectionFactory = null,
        ImageExporter $imageExporter = null
        
    ) {
        $this->factory = $factory ?: Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
        $this->catalogDataForStoresCollector = $catalogDataForStoresCollector ?: $this->factory->createCatalogDataforStoresCollector();
        $this->productDataCollectionFactory = $productDataCollectionFactory ?: $this->factory->createProductDataCollectionFactory();
        $this->categoryDataCollectionFactory = $categoryDataCollectionFactory ?: $this->factory->createCategoryDataCollectionFactory();
        $this->imageExporter = $imageExporter?: $this->factory->createImageExporter();
    }

    /**
     * @param int[] $productIds
     * @param int[] $categoryIds
     * @param string $catalogXmlFilename
     */
    public function write(array $productIds, array $categoryIds, $catalogXmlFilename)
    {
        $xmlMerger = $this->factory->createCatalogMerge();
        $uploader = $this->factory->createXmlUploader($catalogXmlFilename);

        $numberOfProductsExported = $this->writeProducts($productIds, $uploader, $xmlMerger);
        $numberOfCategoriesExported = $this->writeCategories($categoryIds, $uploader, $xmlMerger);

        if ($numberOfProductsExported + $numberOfCategoriesExported > 0) {
            $uploader->writePartialXmlString($xmlMerger->finish());
        }
    }

    /**
     * @param int[] $productIds
     * @param LizardsAndPumpkins_MagentoConnector_Model_XmlUploader $uploader
     * @param CatalogMerge $xmlMerger
     * @return int
     */
    private function writeProducts(
        array $productIds,
        LizardsAndPumpkins_MagentoConnector_Model_XmlUploader $uploader,
        CatalogMerge $xmlMerger
    ) {
        $numberOfProductsExported = 0;
        if ([] === $productIds) {
            return $numberOfProductsExported;
        }
        $productXmlBuilder = $this->factory->createPrepareProductDataForXmlBuilder();
        $imageCollector = $this->factory->createImageCollector();

        foreach ($this->collectProductData($productIds) as $productData) {
            $uploader->writePartialXmlString($xmlMerger->addProduct($productXmlBuilder->process($productData)) . "\n");
            $this->collectImages($productData, $imageCollector);
            $numberOfProductsExported++;
        }

        if ($imageCollector->hasImages()) {
            $this->exportImages($imageCollector);
        }

        return $numberOfProductsExported;
    }

    /**
     * @param int[] $categoryIds
     * @param CatalogMerge $xmlMerger
     * @return int
     */
    private function writeCategories(
        array $categoryIds,
        LizardsAndPumpkins_MagentoConnector_Model_XmlUploader $uploader,
        CatalogMerge $xmlMerger
    ) {
        $numberOfCategoriesExported = 0;
        if ([] === $categoryIds) {
            return $numberOfCategoriesExported;
        }

        $listingXmlBuilder = $this->factory->createListingXml();
        foreach ($this->collectCategoryData($categoryIds) as $category) {
            $uploader->writePartialXmlString($xmlMerger->addCategory($listingXmlBuilder->buildXml($category)) . "\n");
            $numberOfCategoriesExported++;
        }

        return $numberOfCategoriesExported;
    }

    /**
     * @param mixed[] $product
     * @param $imageCollector
     */
    private function collectImages(array $product, ImagesCollector $imageCollector)
    {
        if (! isset($product['media_gallery']['images']) || ! is_array($product['media_gallery']['images'])) {
            return;
        }

        foreach ($product['media_gallery']['images'] as $image) {
            try {
                $imageCollector->addImage(Mage::getBaseDir('media') . self::IMAGE_BASE_PATH . $image['file']);
            } catch (\InvalidArgumentException $e) {
                Mage::logException($e);
            }
        }
    }

    private function exportImages(ImagesCollector $imageCollector)
    {
        foreach ($imageCollector as $image) {
            $this->imageExporter->export($image);
        }
    }

    /**
     * @param int[] $productIds
     * @return Traversable
     */
    private function collectProductData(array $productIds)
    {
        return $this->catalogDataForStoresCollector->aggregate($productIds, $this->productDataCollectionFactory);
    }

    /**
     * @param int[] $categoryIds
     * @return Traversable
     */
    private function collectCategoryData(array $categoryIds)
    {
        return $this->catalogDataForStoresCollector->aggregate($categoryIds, $this->categoryDataCollectionFactory);
    }
}
