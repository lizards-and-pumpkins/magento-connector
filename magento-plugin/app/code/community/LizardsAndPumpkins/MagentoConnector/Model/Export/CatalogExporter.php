<?php

use LizardsAndPumpkins\MagentoConnector\XmlBuilder\CatalogMerge;

class LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter
{
    const IMAGE_BASE_PATH = '/catalog/';

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Helper_Factory
     */
    private $factory;

    public function __construct()
    {
        $this->factory = Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
    }

    /**
     * @var int
     */
    private $numberOfProductsExported = 0;

    /**
     * @var int
     */
    private $numberOfCategoriesExported = 0;

    /**
     * @var bool
     */
    private $echoProgress = false;

    /**
     * @var \LizardsAndPumpkins\MagentoConnector\Images\ImagesCollector
     */
    private $imageCollector;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Helper_Export
     */
    private $memoizedExportHelper;

    /**
     * @param bool $enableProgressDisplay
     */
    public function setShowProgress($enableProgressDisplay)
    {
        $this->echoProgress = (bool) $enableProgressDisplay;
    }

    /**
     * @return string
     */
    public function exportAllCategories()
    {
        $helper = $this->getExportHelper();
        $helper->addAllCategoryIdsToCategoryQueue();

        $filename = $this->exportCategoriesInQueue();
        return $filename;
    }

    /**
     * @return string
     */
    public function exportAllProducts()
    {
        $helper = $this->getExportHelper();
        $helper->addAllProductIdsToProductUpdateQueue();

        $filename = $this->exportProductsInQueue();
        return $filename;
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return string
     */
    public function exportOneStore(Mage_Core_Model_Store $store)
    {
        $helper = $this->getExportHelper();
        $helper->addAllProductIdsFromWebsiteToProductUpdateQueue($store->getWebsite());

        $collector = $this->createProductCollector();
        $collector->setStoresToExport([$store]);

        $filename = $this->exportProducts($collector);
        return $filename;
    }

    /**
     * @param Mage_Core_Model_Website $website
     * @return int
     */
    public function exportOneWebsite(Mage_Core_Model_Website $website)
    {
        $helper = $this->getExportHelper();
        $helper->addAllProductIdsFromWebsiteToProductUpdateQueue($website);

        $collector = $this->createProductCollector();
        $collector->setStoresToExport($website->getStores());

        $filename = $this->exportProducts($collector);
        return $filename;
    }

    /**
     * @return string
     */
    public function exportProductsInQueue()
    {
        $collector = $this->createProductCollector();
        $filename = $this->exportProducts($collector);
        return $filename;
    }

    /**
     * @param LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector $collector
     * @return string
     */
    public function exportProducts(LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector $collector)
    {
        $xmlBuilderAndUploader = $this->factory->createCatalogExporter();
        $filename = $this->factory->getProductXmlFilename();
        $this->imageCollector = $this->factory->createImageCollector();

        $startTime = microtime(true);
        foreach ($collector as $product) {
            $xmlBuilderAndUploader->process($product);
            $totalTime = microtime(true) - $startTime;
            $avgTime = $totalTime / ++$this->numberOfProductsExported;
            $this->echoProgress($avgTime);

            $this->collectImages($product);
        }
        $this->echoProgressDone();

        if ($this->numberOfProductsExported + $this->numberOfCategoriesExported > 0) {
            $this->factory->getProductXmlUploader()->writePartialXmlString($this->factory->getCatalogMerge()->finish());
            $this->linkImages();
        }

        return $filename;
    }

    /**
     * @param float $avgTime
     */
    private function echoProgress($avgTime)
    {
        if ($this->echoProgress) {
            $this->echoToStdErr(sprintf("\r%d | %.4f", $this->numberOfProductsExported, $avgTime));
        }
    }

    private function echoProgressDone()
    {
        if ($this->echoProgress) {
            $this->echoToStdErr(PHP_EOL);
        }
    }

    /**
     * @param string $str
     */
    private function echoToStdErr($str)
    {
        $f = fopen('php://stderr', 'a');
        fwrite($f, $str);
        fclose($f);
    }

    /**
     * @return string
     */
    public function exportCategoriesInQueue()
    {
        $xmlMerge = new CatalogMerge();
        $config = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_magentoConfig');
        $categoryCollector = new LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryCollector($config);
        $uploader = new LizardsAndPumpkins_MagentoConnector_Model_ProductXmlUploader();
        $filename = $uploader->getFilename();

        while ($category = $categoryCollector->getCategory()) {
            $transformer = LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryTransformer::createFrom($category);
            $categoryXml = $transformer->getCategoryXml();
            $xmlMerge->addCategory($categoryXml);
            $this->numberOfCategoriesExported++;
        }

        if ($this->numberOfProductsExported + $this->numberOfCategoriesExported > 0) {
            $uploader->writePartialXmlString($xmlMerge->finish());
        }

        return $filename;
    }

    /**
     * @return int
     */
    public function getNumberOfCategoriesExported()
    {
        return $this->numberOfCategoriesExported;
    }

    /**
     * @return int
     */
    public function getNumberOfProductsExported()
    {
        return $this->numberOfProductsExported;
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Helper_Export
     */
    private function getExportHelper()
    {
        if (null === $this->memoizedExportHelper) {
            $this->memoizedExportHelper = Mage::helper('lizardsAndPumpkins_magentoconnector/export');
        }

        return $this->memoizedExportHelper;
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector
     */
    private function createProductCollector()
    {
        $factory = Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
        return $factory->createProductCollector();
    }

    /**
     * @param mixed[] $product
     */
    private function collectImages($product)
    {
        if (!isset($product['media_gallery']['images']) || !is_array($product['media_gallery']['images'])) {
            return;
        }

        foreach ($product['media_gallery']['images'] as $image) {
            $this->imageCollector->addImage(Mage::getBaseDir('media') . $image['file']);
        }
    }

    private function linkImages()
    {
        $linker = $this->factory->createImageLinker();
        foreach ($this->imageCollector as $image) {
            $linker->link($image);
        }
    }
}
