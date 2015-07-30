<?php

use Brera\MagentoConnector\XmlBuilder\ProductMerge;

class Brera_MagentoConnector_Model_Export_Exporter
{

    public function exportAllProducts()
    {
        $this->createXmlAndUpload();

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

    private function createXmlAndUpload()
    {
        $xmlString = $this->createCatalogXml();
        $this->uploadXml($xmlString);
    }

    /**
     * @return string
     */
    private function createCatalogXml()
    {
        $xmlMerge = new ProductMerge();
        foreach (Mage::app()->getStores() as $store) {
            $productCollection = Mage::getModel('brera_magentoconnector/export_productCollector')
                ->getAllProducts($store);

            (new Brera_MagentoConnector_Model_Export_ProductXmlBuilder($productCollection, $store, $xmlMerge))
                ->process();
        }

        $xmlString = $xmlMerge->getXmlString();

        return $xmlString;
    }

    /**
     * @param $xmlString
     */
    private function uploadXml($xmlString)
    {
        $target = Mage::getStoreConfig('brera/magentoconnector/product_xml_target');
        $uploader = new Brera_MagentoConnector_Model_XmlUploader($xmlString, $target);
        $uploader->upload();
    }
}
