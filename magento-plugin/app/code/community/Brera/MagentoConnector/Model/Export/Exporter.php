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
        Mage::helper('brera_magentoconnector/export')
            ->addProductUpdatesToQueue(Mage::getResourceModel('catalog/product_collection')->getAllIds());
        $this->exportProductsInQueue();
    }

    public function exportProductsInQueue()
    {
        $collector = Mage::getModel('brera_magentoconnector/export_productCollector');
        $xmlMerge = new ProductMerge();
        /** @var Brera_MagentoConnector_Model_XmlUploader $uploader */
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
            $this->triggerCatalogUpdateApi();
            $this->coreSession->addSuccess('Export was successfull.');
        } catch (Exception $e) {
            $this->coreSession->addError('Export failed: ' . $e->getMessage());
        }
    }

    private function triggerCatalogUpdateApi()
    {
        $apiUrl = Mage::getStoreConfig('brera/magentoconnector/api_url');
        $remoteFileLocation = Mage::getStoreConfig('brera/magentoconnector/remote_catalog_xml_location');
        $api = new \Brera\MagentoConnector\Api\Api($apiUrl);
        $api->triggerProductImport($remoteFileLocation);
    }
}
