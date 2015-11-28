<?php

class Brera_MagentoConnector_Model_ProductXmlUploader extends Brera_MagentoConnector_Model_XmlUploader
{
    public function __construct()
    {
        parent::__construct(strftime(Mage::getStoreConfig('brera/magentoconnector/product_xml_target')));
    }

}
