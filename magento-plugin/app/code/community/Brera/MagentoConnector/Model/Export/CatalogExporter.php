<?php

use Brera\MagentoConnector\Api\Api;
use Brera\MagentoConnector\XmlBuilder\CatalogMerge;

class Brera_MagentoConnector_Model_Export_CatalogExporter
{
    private $numberOfProductsExported = 0;
    private $numberOfCategoriesExported = 0;

    /**
     * @var Mage_Core_Model_Session
     */
    private $coreSession;

    /**
     * @var Brera_MagentoConnector_Model_Export_ProductCollector
     */
    private $productCollector;

    public function __construct()
    {
        $this->productCollector = Mage::getModel('brera_magentoconnector/export_productCollector');
        $this->coreSession = Mage::getSingleton('core/session');
    }

    public function exportAllProducts()
    {
        $helper = Mage::helper('brera_magentoconnector/export');
        $helper->addAllProductIdsToProductUpdateQueue();
        $helper->addAllCategoryIdsToCategoryQueue();
        $this->exportProductsInQueue();
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return int
     */
    public function exportOneStore(Mage_Core_Model_Store $store)
    {
        /** @var Brera_MagentoConnector_Helper_Export $helper */
        $helper = Mage::helper('brera_magentoconnector/export');
        $helper->addAllProductIdsFromWebsiteToProductUpdateQueue($store->getWebsite());
        /** @var Brera_MagentoConnector_Model_Export_ProductCollector $collector */
        $collector = Mage::getModel('brera_magentoconnector/export_productCollector');
        $collector->setStoresToExport([$store]);
        $this->export($collector);
    }

    /**
     * @param Mage_Core_Model_Website $website
     * @return int
     */
    public function exportOneWebsite(Mage_Core_Model_Website $website)
    {
        /** @var Brera_MagentoConnector_Helper_Export $helper */
        $helper = Mage::helper('brera_magentoconnector/export');
        $helper->addAllProductIdsFromWebsiteToProductUpdateQueue($website);
        /** @var Brera_MagentoConnector_Model_Export_ProductCollector $collector */
        $collector = Mage::getModel('brera_magentoconnector/export_productCollector');
        $collector->setStoresToExport($website->getStores());
        $this->export($collector);
    }

    public function exportProductsInQueue()
    {
        /** @var Brera_MagentoConnector_Model_Export_ProductCollector $collector */
        $collector = Mage::getModel('brera_magentoconnector/export_productCollector');
        $this->export($collector);
    }

    /**
     * @param string $filename
     */
    private function triggerCatalogUpdateApi($filename)
    {
        $apiUrl = Mage::getStoreConfig('brera/magentoconnector/api_url');
        $api = new Api($apiUrl);
        $api->triggerProductImport($filename);
    }

    /**
     * @param Brera_MagentoConnector_Model_Export_ProductCollector $collector
     */
    private function export(Brera_MagentoConnector_Model_Export_ProductCollector $collector)
    {
        $xmlMerge = new CatalogMerge();
        /** @var Brera_MagentoConnector_Model_ProductXmlUploader $uploader */
        $uploader = Mage::getModel('brera_magentoconnector/productXmlUploader');

        while ($product = $collector->getProduct()) {
            $xmlBuilderAndUploader = new Brera_MagentoConnector_Model_Export_ProductXmlBuilderAndUploader(
                $product,
                $xmlMerge,
                $uploader
            );

            $xmlBuilderAndUploader->process();
            $this->numberOfProductsExported++;
        }

        $categoryCollector = new Brera_MagentoConnector_Model_Export_CategoryCollector();

        while ($category = $categoryCollector->getCategory()) {
            $transformer = Brera_MagentoConnector_Model_Export_CategoryTransformer::createFrom($category);
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
