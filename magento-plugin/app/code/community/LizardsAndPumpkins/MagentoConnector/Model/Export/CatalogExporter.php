<?php

use LizardsAndPumpkins\MagentoConnector\Images\ImagesCollector;

class LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter
{
    const IMAGE_BASE_PATH = '/catalog/product';
    const REFRESH_PRODUCT_QUEUE_COUNT_EVERY_N_PRODUCTS = 1000;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Helper_Factory
     */
    private $memoizedFactory;

    /**
     * @var int
     */
    private $numberOfProductsInQueue;

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
     * @var ImagesCollector
     */
    private $imageCollector;

    /**
     * @param bool $enableProgressDisplay
     */
    public function setShowProgress($enableProgressDisplay)
    {
        $this->echoProgress = (bool)$enableProgressDisplay;
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Helper_Factory
     */
    private function getFactory()
    {
        if (null === $this->memoizedFactory) {
            $this->memoizedFactory = Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
        }
        return $this->memoizedFactory;
    }

    /**
     * @return string
     */
    public function exportAllCategories()
    {
        $targetDataVersion = $this->getTargetDataVersion();
        $this->getExportQueue()->addAllCategoryIdsToCategoryQueue($targetDataVersion);

        $filename = $this->exportCategoriesInQueue();
        return $filename;
    }

    /**
     * @return string
     */
    private function getTargetDataVersion()
    {
        /** @var LizardsAndPumpkins_MagentoConnector_Helper_DataVersion $dataVersionHelper */
        $dataVersionHelper = Mage::helper('lizardsAndPumpkins_magentoconnector/dataVersion');
        
        return $dataVersionHelper->getTargetVersion();
    }
    
    /**
     * @return string
     */
    public function exportAllProducts()
    {
        $targetDataVersion = $this->getTargetDataVersion();
        $this->getExportQueue()->addAllProductIdsToProductUpdateQueue($targetDataVersion);
        
        $filename = $this->exportProductsInQueue();
        return $filename;
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return string
     */
    public function exportOneStore(Mage_Core_Model_Store $store)
    {
        $targetDataVersion = $this->getTargetDataVersion();
        $website = $store->getWebsite();
        $this->getExportQueue()->addAllProductIdsFromWebsiteToProductUpdateQueue($website, $targetDataVersion);
        
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
        $targetDataVersion = $this->getTargetDataVersion();
        $this->getExportQueue()->addAllProductIdsFromWebsiteToProductUpdateQueue($website, $targetDataVersion);

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
        /** @var LizardsAndPumpkins_MagentoConnector_Helper_Factory $factoryHelper */
        $factoryHelper = Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
        $factoryHelper->reset();

        $xmlBuilderAndUploader = $this->getFactory()->createPrepareProductDataForXmlBuilder();
        $filename = $this->getFactory()->getProductXmlFilename();
        $this->imageCollector = $this->getFactory()->createImageCollector();

        $startTime = microtime(true);
        foreach ($collector as $product) {
            $xmlBuilderAndUploader->process($product);
            $this->refreshProductQueueCountIfNeeded();

            $totalTime = microtime(true) - $startTime;
            $avgTime = $totalTime / ++$this->numberOfProductsExported;
            $this->echoProgress($avgTime);

            $this->collectImages($product);
        }
        $this->echoProgressDone();

        if ($this->numberOfProductsExported + $this->numberOfCategoriesExported > 0) {
            $this->getFactory()->getProductXmlUploader()->writePartialXmlString(
                $this->getFactory()->getCatalogMerge()->finish()
            );
            $this->exportImages();
        }

        return $filename;
    }

    /**
     * @param float $avgTime
     */
    private function echoProgress($avgTime)
    {
        if (!$this->echoProgress) {
            return;
        }
        $this->echoToStdErr(sprintf("\r%d | %.4f", $this->numberOfProductsExported, $avgTime));
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
        $config = $this->getMagentoConfig();

        $categoryCollector = new LizardsAndPumpkins_MagentoConnector_Model_Export_CategoriesInQueueCollector($config);
        $categoryCollector->setStoresToExport($config->getStoresToExport());

        return $this->exportCategories($categoryCollector);
    }

    /**
     * @param LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryCollector $categoryCollector
     * @return string
     */
    public function exportCategories(
        LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryCollector $categoryCollector
    ) {
        $xmlMerge = $this->getFactory()->createCatalogMerge();

        $uploader = new LizardsAndPumpkins_MagentoConnector_Model_ProductXmlUploader();
        $listingXml = $this->getFactory()->createListingXml();

        while ($category = $categoryCollector->getCategory()) {
            $categoryXml = $listingXml->buildXml($category);
            $xmlMerge->addCategory($categoryXml);
            $this->numberOfCategoriesExported++;
        }

        if ($this->numberOfProductsExported + $this->numberOfCategoriesExported > 0) {
            $uploader->writePartialXmlString($xmlMerge->finish());
        }
        return $uploader->getFilename();
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
     * @return LizardsAndPumpkins_MagentoConnector_Model_ExportQueue
     */
    private function getExportQueue()
    {
        return Mage::helper('lizardsAndPumpkins_magentoconnector/factory')->createExportQueue();
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector
     */
    private function createProductCollector()
    {
        /** @var LizardsAndPumpkins_MagentoConnector_Helper_Factory $factory */
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
            try {
                $this->imageCollector->addImage(Mage::getBaseDir('media') . self::IMAGE_BASE_PATH . $image['file']);
            } catch (\InvalidArgumentException $e) {
                Mage::logException($e);
            }
        }
    }

    private function exportImages()
    {
        $imageExporter = $this->getFactory()->createImageExporter();
        foreach ($this->imageCollector as $image) {
            $imageExporter->export($image);
        }
    }

    private function refreshProductQueueCountIfNeeded()
    {
        if ($this->numberOfProductsExported % self::REFRESH_PRODUCT_QUEUE_COUNT_EVERY_N_PRODUCTS === 0) {
            $this->refreshNumberOfProductsInQueue();
        }
    }

    private function refreshNumberOfProductsInQueue()
    {
        $queue = $this->getFactory()->createExportQueue();
        $this->numberOfProductsInQueue = $queue->getProductQueueCount();
    }

    /**
     * @return bool
     */
    public function wasSomethingExported()
    {
        return (bool)($this->numberOfProductsExported + $this->numberOfCategoriesExported);
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig
     */
    private function getMagentoConfig()
    {
        return $this->getFactory()->getConfig();
    }
}
