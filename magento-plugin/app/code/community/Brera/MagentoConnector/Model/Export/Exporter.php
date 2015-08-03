<?php

use Brera\MagentoConnector\XmlBuilder\ProductMerge;

class Brera_MagentoConnector_Model_Export_Exporter
{

    const PAGE_SIZE = 100;

    public function exportAllProducts()
    {
        $this->createAndUploadCatalogXml();

        try {
            $apiUrl = Mage::getStoreConfig('brera/magentoconnector/api_url');
            $remoteFileLocation = Mage::getStoreConfig('brera/magentoconnector/remote_catalog_xml_location');
            $api = new \Brera\MagentoConnector\Api\Api($apiUrl);
            $api->triggerProductImport($remoteFileLocation);
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError('Export failed: ' . $e->getMessage());

            return;
        }

        Mage::getSingleton('core/session')->addSuccess('Export was successfull.');
    }

    private function createAndUploadCatalogXml()
    {
        $xmlMerge = new ProductMerge();
        $uploader = Mage::getModel('brera_magentoconnector/xmlUploader');
        $productCollector = Mage::getModel('brera_magentoconnector/export_productCollector');
        foreach (Mage::app()->getStores() as $store) {
            $productCollection = $productCollector
                ->getAllProducts($store);
            $productCollection->setPageSize(self::PAGE_SIZE);
            $pages = $productCollection->getLastPageNumber();
            $currentPage = 1;


            do {
                $productCollection->setCurPage($currentPage);
                $productCollector->addStockItemsAndMediaGallery($productCollection, $store);

                $xmlBuilderAndUploader = new Brera_MagentoConnector_Model_Export_ProductXmlBuilderAndUploader(
                    $productCollection, $store, $xmlMerge, $uploader
                );

                $xmlBuilderAndUploader->process();

                $currentPage++;
                //clear collection and free memory
                $productCollection->clear();
            } while ($currentPage <= $pages);
        }

        $uploader->writePartialString($xmlMerge->finish());
    }
}
