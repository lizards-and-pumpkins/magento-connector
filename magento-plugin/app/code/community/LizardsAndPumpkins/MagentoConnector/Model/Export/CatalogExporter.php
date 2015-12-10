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

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Helper_Export
     */
    private $memoizedExportHelper;

    public function __construct()
    {
        $this->productCollector = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_productCollector');
        $this->coreSession = Mage::getSingleton('core/session');
    }

    public function exportAllProducts()
    {
        $helper = $this->getExportHelper();
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
        $helper = $this->getExportHelper();
        $helper->addAllProductIdsFromWebsiteToProductUpdateQueue($store->getWebsite());

        $collector = $this->createProductCollector();

        $collector->setStoresToExport([$store]);

        $this->export($collector);
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

        $this->export($collector);
    }

    public function exportProductsInQueue()
    {
        $collector = $this->createProductCollector();
        $this->export($collector);
    }

    /**
     * @param string $filename
     */
    private function triggerCatalogUpdateApi($filename)
    {
        $apiUrl = Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/api_url');
        $api = new Api($apiUrl);
        $api->triggerProductImport($filename);
    }

    /**
     * @param LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector $collector
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
            $transformer = LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryTransformer::createFrom($category);
            $categoryXml = $transformer->getCategoryXml();
            $xmlMerge->addCategory($categoryXml);
            $this->numberOfCategoriesExported++;
        }

        if ($this->numberOfProductsExported + $this->numberOfCategoriesExported === 0) {
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
        return Mage::getModel('lizardsAndPumpkins_magentoconnector/export_productCollector');
    }
}
