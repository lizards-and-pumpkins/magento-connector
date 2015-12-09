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
        $this->exportProductsInQueue();
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return int
     */
    public function exportOneStore(Mage_Core_Model_Store $store)
    {
<<<<<<< HEAD:magento-plugin/app/code/community/LizardsAndPumpkins/MagentoConnector/Model/Export/CatalogExporter.php
        Mage::helper('lizardsAndPumpkins_magentoconnector/export')
            ->addAllProductIdsFromWebsiteToProductUpdateQueue($store->getWebsite());
        $collector = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_productCollector');
=======
        /** @var Brera_MagentoConnector_Helper_Export $helper */
        $helper = Mage::helper('brera_magentoconnector/export');
        $helper->addAllProductIdsFromWebsiteToProductUpdateQueue($store->getWebsite());
        /** @var Brera_MagentoConnector_Model_Export_ProductCollector $collector */
        $collector = Mage::getModel('brera_magentoconnector/export_productCollector');
>>>>>>> master:magento-plugin/app/code/community/Brera/MagentoConnector/Model/Export/CatalogExporter.php
        $collector->setStoresToExport([$store]);
        $this->export($collector);
    }

    /**
     * @param Mage_Core_Model_Website $website
     * @return int
     */
    public function exportOneWebsite(Mage_Core_Model_Website $website)
    {
<<<<<<< HEAD:magento-plugin/app/code/community/LizardsAndPumpkins/MagentoConnector/Model/Export/CatalogExporter.php
        Mage::helper('lizardsAndPumpkins_magentoconnector/export')
            ->addAllProductIdsFromWebsiteToProductUpdateQueue($website);
        $collector = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_productCollector');
=======
        /** @var Brera_MagentoConnector_Helper_Export $helper */
        $helper = Mage::helper('brera_magentoconnector/export');
        $helper->addAllProductIdsFromWebsiteToProductUpdateQueue($website);
        /** @var Brera_MagentoConnector_Model_Export_ProductCollector $collector */
        $collector = Mage::getModel('brera_magentoconnector/export_productCollector');
>>>>>>> master:magento-plugin/app/code/community/Brera/MagentoConnector/Model/Export/CatalogExporter.php
        $collector->setStoresToExport($website->getStores());
        $this->export($collector);
    }

    public function exportProductsInQueue()
    {
<<<<<<< HEAD:magento-plugin/app/code/community/LizardsAndPumpkins/MagentoConnector/Model/Export/CatalogExporter.php
        $collector = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_productCollector');
        return $this->export($collector);
=======
        /** @var Brera_MagentoConnector_Model_Export_ProductCollector $collector */
        $collector = Mage::getModel('brera_magentoconnector/export_productCollector');
        $this->export($collector);
>>>>>>> master:magento-plugin/app/code/community/Brera/MagentoConnector/Model/Export/CatalogExporter.php
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
<<<<<<< HEAD:magento-plugin/app/code/community/LizardsAndPumpkins/MagentoConnector/Model/Export/CatalogExporter.php
     * @param LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector $collector
     * @return int
=======
     * @param Brera_MagentoConnector_Model_Export_ProductCollector $collector
>>>>>>> master:magento-plugin/app/code/community/Brera/MagentoConnector/Model/Export/CatalogExporter.php
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
<<<<<<< HEAD:magento-plugin/app/code/community/LizardsAndPumpkins/MagentoConnector/Model/Export/CatalogExporter.php
            $transformer = new LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryToLapTransformer($category);
=======
            $transformer = Brera_MagentoConnector_Model_Export_CategoryTransformer::createFrom($category);
>>>>>>> master:magento-plugin/app/code/community/Brera/MagentoConnector/Model/Export/CatalogExporter.php
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
