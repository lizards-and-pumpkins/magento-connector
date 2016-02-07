<?php

use LizardsAndPumpkins\MagentoConnector\XmlBuilder\CatalogMerge;

class LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter
{
    const IMAGE_BASE_PATH = '/catalog/product';
    const REFRESH_PRODUCT_QUEUE_COUNT_EVERY_N_PRODUCTS = 1000;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Helper_Factory
     */
    private $factory;

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
     * @return LizardsAndPumpkins_MagentoConnector_Helper_Factory
     */
    public function getFactory()
    {
        if (null === $this->factory) {
            $this->factory = Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
        }
        return $this->factory;
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
            $this->linkImages();
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
        $xmlMerge = new CatalogMerge();

        /** @var LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig $config */
        $config = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_magentoConfig');

        $categoryCollector = new LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryCollector($config);
        $categoryCollector->setStoresToExport($config->getStoresToExport());

        $uploader = new LizardsAndPumpkins_MagentoConnector_Model_ProductXmlUploader();

        while ($category = $categoryCollector->getCategory()) {
            $transformer = LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryTransformer::createFrom($category);
            $categoryXml = $transformer->getCategoryXml();
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

    private function linkImages()
    {
        $linker = $this->getFactory()->createImageLinker();
        foreach ($this->imageCollector as $image) {
            $linker->link($image);
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
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');

        $stats = new LizardsAndPumpkins_MagentoConnector_Model_Statistics($resource);
        $this->numberOfProductsInQueue = $stats->getQueuedProductCount();
    }

    /**
     * @return bool
     */
    public function wasSomethingExported()
    {
        return (bool) ($this->numberOfProductsExported + $this->numberOfCategoriesExported);
    }
}
