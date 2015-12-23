<?php

use LizardsAndPumpkins\MagentoConnector\Images\Collector;
use LizardsAndPumpkins\MagentoConnector\Images\Linker;
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
    public function createCatalogExporter()
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
                }, array_filter(explode(',', $config))
            );
            $collector->setStoresToExport($stores);
        }
        return $collector;
    }

    /**
     * @return Collector
     */
    public function createImageCollector()
    {
        return new Collector();
    }

    /**
     * @return Linker
     */
    public function createImageLinker()
    {
        return Linker::createFor($this->getConfig()->getImageTargetDirectory());
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
}
