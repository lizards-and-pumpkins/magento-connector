<?php

use LizardsAndPumpkins\MagentoConnector\Api\Api;
use LizardsAndPumpkins\MagentoConnector\XmlBuilder\CatalogMerge;

class LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter
{
    private $numberOfProductsExported = 0;
    private $numberOfCategoriesExported = 0;

    /**
     * @var Mage_Core_Model_Session
     */
    private $coreSession;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector
     */
    private $productCollector;

    public function __construct()
    {
        $this->productCollector = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_productCollector');
        $this->coreSession = Mage::getSingleton('core/session');
    }

    public function exportAllProducts()
    {
        $helper = Mage::helper('lizardsAndPumpkins_magentoconnector/export');
        $helper->addAllProductIdsToProductUpdateQueue();
        $helper->addAllCategoryIdsToCategoryQueue();
        return $this->exportProductsInQueue();
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return int
     */
    public function exportOneStore(Mage_Core_Model_Store $store)
    {
        Mage::helper('lizardsAndPumpkins_magentoconnector/export')
            ->addAllProductIdsFromWebsiteToProductUpdateQueue($store->getWebsite());
        $collector = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_productCollector');
        $collector->setStoresToExport([$store]);
        return $this->export($collector);
    }

    /**
     * @param Mage_Core_Model_Website $website
     * @return int
     */
    public function exportOneWebsite(Mage_Core_Model_Website $website)
    {
        Mage::helper('lizardsAndPumpkins_magentoconnector/export')
            ->addAllProductIdsFromWebsiteToProductUpdateQueue($website);
        $collector = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_productCollector');
        $collector->setStoresToExport($website->getStores());
        return $this->export($collector);
    }

    public function exportProductsInQueue()
    {
        $collector = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_productCollector');
        return $this->export($collector);
    }

    private function triggerCatalogUpdateApi($filename)
    {
        $apiUrl = Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/api_url');
        $api = new Api($apiUrl);
        $api->triggerProductImport($filename);
    }

    /**
     * @param LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector $collector
     * @return int
     */
    private function export(LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector $collector)
    {
        $xmlMerge = new CatalogMerge();
        /** @var LizardsAndPumpkins_MagentoConnector_Model_ProductXmlUploader $uploader */
        $uploader = Mage::getModel('lizardsAndPumpkins_magentoconnector/productXmlUploader');

        while ($product = $collector->getProduct()) {
            $xmlBuilderAndUploader = new LizardsAndPumpkins_MagentoConnector_Model_Export_ProductXmlBuilderAndUploader(
                $product,
                $xmlMerge,
                $uploader
            );

            $xmlBuilderAndUploader->process();
            $this->numberOfProductsExported++;
        }

        $categoryCollector = new LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryCollector();

        while ($category = $categoryCollector->getCategory()) {
            $transformer = new LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryToLapTransformer($category);
            $categoryXml = $transformer->getCategoryXml();
            $xmlMerge->addCategory($categoryXml);
            $this->numberOfCategoriesExported++;
        }
        if (0 === ($this->numberOfProductsExported + $this->numberOfCategoriesExported)) {
            return;
        }

        $uploader->writePartialXmlString($xmlMerge->finish());
        $filename = $uploader->getFilename();
        $this->triggerCatalogUpdateApi($filename);
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
}
