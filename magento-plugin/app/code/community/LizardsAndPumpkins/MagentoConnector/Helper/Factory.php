<?php

use LizardsAndPumpkins\MagentoConnector\Images\ImageLinker;
use LizardsAndPumpkins\MagentoConnector\Images\ImagesCollector;
use LizardsAndPumpkins\MagentoConnector\XmlBuilder\CatalogMerge;

class LizardsAndPumpkins_MagentoConnector_Helper_Factory
{
    /**
     * @var CatalogMerge
     */
    private $catalogMerge;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_ProductXmlUploader
     */
    private $productXmlUploader;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig
     */
    private $config;

    public function reset()
    {
        $this->catalogMerge = null;
        $this->productXmlUploader = null;
    }

    /**
     * @return CatalogMerge
     */
    public function getCatalogMerge()
    {
        if (null === $this->catalogMerge) {
            $this->catalogMerge = new CatalogMerge();
        }
        return $this->catalogMerge;
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_ProductXmlUploader
     */
    public function getProductXmlUploader()
    {
        if (null === $this->productXmlUploader) {
            $this->productXmlUploader = new LizardsAndPumpkins_MagentoConnector_Model_ProductXmlUploader();
        }
        return $this->productXmlUploader;
    }

    /**
     * @return string
     */
    public function getProductXmlFilename()
    {
        return $this->getProductXmlUploader()->getFilename();
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_PrepareProductDataForXmlBuilder
     */
    public function createPrepareProductDataForXmlBuilder()
    {
        return new LizardsAndPumpkins_MagentoConnector_Model_Export_PrepareProductDataForXmlBuilder(
            $this->getCatalogMerge(),
            $this->getProductXmlUploader()
        );
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector
     */
    public function createProductCollector()
    {
        $helper = Mage::helper('lizardsAndPumpkins_magentoconnector/export');
        $collector = new LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector($helper);

        if ($config = Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/stores_to_export')) {
            $stores = array_map(
                function ($storeId) {
                    return Mage::app()->getStore($storeId);
                },
                array_filter(explode(',', $config))
            );
            $collector->setStoresToExport($stores);
        }
        return $collector;
    }

    /**
     * @return ImagesCollector
     */
    public function createImageCollector()
    {
        return new ImagesCollector();
    }

    /**
     * @return ImageLinker
     */
    public function createImageLinker()
    {
        $targetDir = $this->getConfig()->getImageTargetDirectory();
        if ($this->validateDirectory($targetDir)) {
            $targetDir = $this->getConfig()->getLocalPathForProductExport() . '/product-images';
        }
        return ImageLinker::createFor($targetDir);
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig
     */
    private function getConfig()
    {
        if (null === $this->config) {
            $this->config = new LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig();
        }
        return $this->config;
    }

    /**
     * @param string $targetDir
     * @return bool
     */
    private function validateDirectory($targetDir)
    {
        return !is_string($targetDir) || !is_dir($targetDir);
    }
}
