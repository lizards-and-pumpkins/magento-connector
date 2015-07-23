<?php

use Brera\MagentoConnector\Xml\Product\ProductMerge;

class Brera_MagentoConnector_Model_Export_Exporter
{

    public function exportAllProducts()
    {
        $xml = new ProductMerge();
        foreach (Mage::app()->getStores() as $store) {
            $productCollection = Mage::getModel('brera_magentoconnector/export_productCollector')
                ->getAllProducts($store);
            $arguments = [
                $productCollection,
                $store,
                $xml
            ];
            $xml = Mage::getModel('brera_magentoconnector/export_productXmlBuilder', $arguments)
                ->getXml();
        }

        $xmlString = $xml->getXmlString();

        $target = Mage::getStoreConfig('brera/magentoconnector/product_xml_target');

        $arguments = array($xmlString, $target);
        /** @var $uploader Brera_MagentoConnector_Model_XmlUploader */
        $uploader = Mage::getModel('brera_magentoconnector/xmlUploader', $arguments);
        $uploader->upload();
    }
}
