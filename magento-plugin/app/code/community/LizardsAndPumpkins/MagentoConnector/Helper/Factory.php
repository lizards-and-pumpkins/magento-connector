<?php

use LizardsAndPumpkins\MagentoConnector\XmlBuilder\CatalogMerge;

class LizardsAndPumpkins_MagentoConnector_Helper_Factory extends Mage_Core_Helper_Abstract
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

    public function getCatalogMerge()
    {
        if (null === $this->catalogMerge) {
            $this->catalogMerge = new CatalogMerge();
        }
        return $this->catalogMerge;
    }

    public function getProductXmlUploader()
    {
        if (null === $this->productXmlUploader) {
            $this->productXmlUploader = new LizardsAndPumpkins_MagentoConnector_Model_ProductXmlUploader();
        }
        return $this->productXmlUploader;
    }

    public function getProductXmlFilename()
    {
        return $this->getProductXmlUploader()->getFilename();
    }

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
    
    public function createCatalogExporter()
    {
        return new LizardsAndPumpkins_MagentoConnector_Model_Export_ProductXmlBuilderAndUploader(
            $this->getCatalogMerge(),
            $this->getProductXmlUploader(),
            $this->getSourceTableDataProvider()
        );
    }
    
    public function createProductCollector()
    {
        $helper = $helper = Mage::helper('lizardsAndPumpkins_magentoconnector/export');
        return new LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector($helper);
    }
}
