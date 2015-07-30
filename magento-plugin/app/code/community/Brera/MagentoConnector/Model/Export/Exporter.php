<?php

use Brera\MagentoConnector\XmlBuilder\ProductMerge;

class Brera_MagentoConnector_Model_Export_Exporter
{

    public function exportAllProducts()
    {
        $xmlMerge = new ProductMerge();
        foreach (Mage::app()->getStores() as $store) {
            $productCollection = Mage::getModel('brera_magentoconnector/export_productCollector')
                ->getAllProducts($store);

            (new Brera_MagentoConnector_Model_Export_ProductXmlBuilder($productCollection, $store, $xmlMerge))
                ->process();
        }

        $xmlString = $xmlMerge->getXmlString();

        $target = Mage::getStoreConfig('brera/magentoconnector/product_xml_target');
        $uploader = new Brera_MagentoConnector_Model_XmlUploader($xmlString, $target);
        $uploader->upload();

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
}
