<?php

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
     * @var LizardsAndPumpkins_MagentoConnector_Model_Export_SourceTableDataProvider
     */
    private $sourceTableDataProvider;

    public function reset()
    {
        $this->catalogMerge = null;
        $this->productXmlUploader = null;
        $this->sourceTableDataProvider = null;
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
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_SourceTableDataProvider
     */
    public function getSourceTableDataProvider()
    {
        if (null === $this->sourceTableDataProvider) {
            $this->sourceTableDataProvider = new LizardsAndPumpkins_MagentoConnector_Model_Export_SourceTableDataProvider(
                Mage::getSingleton('core/resource'),
                Mage::getModel('lizardsAndPumpkins_magentoconnector/export_magentoConfig')
            );
        }
        return $this->sourceTableDataProvider;
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_ProductXmlBuilderAndUploader
     */
    public function createCatalogExporter()
    {
        return new LizardsAndPumpkins_MagentoConnector_Model_Export_PrepareProductDataForXmlBuilder(
            $this->getCatalogMerge(),
            $this->getProductXmlUploader(),
            $this->getSourceTableDataProvider()
        );
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector
     */
    public function createProductCollector()
    {
        $helper = Mage::helper('lizardsAndPumpkins_magentoconnector/export');
        return new LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector($helper);
    }
}
