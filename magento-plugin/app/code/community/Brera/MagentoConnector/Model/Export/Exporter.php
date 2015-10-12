<?php

use Brera\MagentoConnector\XmlBuilder\ProductMerge;

class Brera_MagentoConnector_Model_Export_Exporter
{

    const PAGE_SIZE = 100;
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
        $collectorCollection = array($this->productCollector, 'getAllProductsCollection');
        $this->exportProductsWith($collectorCollection);
    }

    public function exportProductsInQueue()
    {
        $collectorCollection = array($this->productCollector, 'getAllQueuedProductUpdates');
        $this->exportProductsWith($collectorCollection);
        $updates = $this->productCollector->getQueuedProductUpdates();
        $this->cleanupQueue(
            $updates['ids'],
            $updates['skus'],
            Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_STOCK_UPDATE
        );
    }

    /**
     * @param int[] $ids
     * @param string[] $skus
     * @param string $action
     */
    private function cleanupQueue(array $ids, array $skus, $action)
    {
        Mage::getResourceModel('brera_magentoconnector/product_queue_item')
            ->cleanupQueue($ids, $skus, $action);
    }

    /**
     * @param callable $collectorCollection
     */
    private function createAndUploadCatalogXml(callable $collectorCollection)
    {
        $xmlMerge = new ProductMerge();
        /** @var Brera_MagentoConnector_Model_XmlUploader $uploader */
        $uploader = Mage::getModel('brera_magentoconnector/xmlUploader');

        foreach (Mage::app()->getStores() as $store) {
            $this->exportProductsFromStore($collectorCollection, $store, $xmlMerge, $uploader);
        }

        $uploader->writePartialString($xmlMerge->finish());
    }

    private function triggerCatalogUpdateApi()
    {
        $apiUrl = Mage::getStoreConfig('brera/magentoconnector/api_url');
        $remoteFileLocation = Mage::getStoreConfig('brera/magentoconnector/remote_catalog_xml_location');
        $api = new \Brera\MagentoConnector\Api\Api($apiUrl);
        $api->triggerProductImport($remoteFileLocation);
    }

    /**
     * @param callable $collectorCollection
     */
    private function exportProductsWith(callable $collectorCollection)
    {
        $this->createAndUploadCatalogXml($collectorCollection);

        try {
            $this->triggerCatalogUpdateApi();
            $this->coreSession->addSuccess('Export was successfull.');
        } catch (Exception $e) {
            $this->coreSession->addError('Export failed: ' . $e->getMessage());
        }
    }

    /**
     * @param callable $collectorCollection
     * @param Mage_Core_Model_Store $store
     * @param ProductMerge $xmlMerge
     * @param Brera_MagentoConnector_Model_XmlUploader $uploader
     */
    private function exportProductsFromStore(callable $collectorCollection, $store, $xmlMerge, $uploader)
    {
        /** @var Mage_Catalog_Model_Resource_Product_Collection $productCollection */
        $productCollection = $collectorCollection($store);
        $productCollection->setPageSize(self::PAGE_SIZE);
        $pages = $productCollection->getLastPageNumber();
        $currentPage = 1;

        do {
            $productCollection->setCurPage($currentPage);
            $this->productCollector->addAdditionalData($productCollection, $store);

            $xmlBuilderAndUploader = new Brera_MagentoConnector_Model_Export_ProductXmlBuilderAndUploader(
                $productCollection, $store, $xmlMerge, $uploader
            );

            $xmlBuilderAndUploader->process();

            $currentPage++;
            //clear collection and free memory
            $productCollection->clear();
        } while ($currentPage <= $pages);
    }
}
