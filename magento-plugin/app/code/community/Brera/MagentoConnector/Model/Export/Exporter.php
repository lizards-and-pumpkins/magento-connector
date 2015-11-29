<?php

use Brera\MagentoConnector\XmlBuilder\ProductMerge;

class Brera_MagentoConnector_Model_Export_Exporter
{

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
        Mage::helper('brera_magentoconnector/export')->addAllProductIdsToProductUpdateQueue();
        $this->exportProductsInQueue();
    }

    /**
     * @param Mage_Core_Model_Website $website
     */
    public function exportOneWebsite(Mage_Core_Model_Website $website)
    {
        Mage::helper('brera_magentoconnector/export')
            ->addAllProductIdsFromWebsiteToProductUpdateQueue($website);
        $collector = Mage::getModel('brera_magentoconnector/export_productCollector');
        $collector->setStoresToExport($website->getStores());
        $this->export($collector);
    }

    public function exportProductsInQueue()
    {
        $collector = Mage::getModel('brera_magentoconnector/export_productCollector');
        $this->export($collector);
    }

    private function triggerCatalogUpdateApi($filename)
    {
        $apiUrl = Mage::getStoreConfig('brera/magentoconnector/api_url');
        $api = new \Brera\MagentoConnector\Api\Api($apiUrl);
        $api->triggerProductImport($filename);
    }

    /**
     * @param $collector
     */
    private function export($collector)
    {
        $xmlMerge = new ProductMerge();
        /** @var Brera_MagentoConnector_Model_ProductXmlUploader $uploader */
        $uploader = Mage::getModel('brera_magentoconnector/productXmlUploader');

        while ($product = $collector->getProduct()) {
            $xmlBuilderAndUploader = new Brera_MagentoConnector_Model_Export_ProductXmlBuilderAndUploader(
                $product,
                $xmlMerge,
                $uploader
            );

            $xmlBuilderAndUploader->process();
        }

        $uploader->writePartialString($xmlMerge->finish());

        try {
            $this->triggerCatalogUpdateApi($uploader->getFilename());
            $this->coreSession->addSuccess('Export was successfull.');
        } catch (Exception $e) {
            $this->coreSession->addError('Export failed: ' . $e->getMessage());
        }
    }
}
