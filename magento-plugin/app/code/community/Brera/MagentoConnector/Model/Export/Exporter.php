<?php

require_once 'Brera/src/XmlBuilder/ProductMerge.php';

use Brera\MagentoConnector\Xml\Product\ProductMerge;

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
    }
}
