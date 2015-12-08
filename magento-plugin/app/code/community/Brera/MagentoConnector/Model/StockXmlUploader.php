<?php

class Brera_MagentoConnector_Model_StockXmlUploader extends Brera_MagentoConnector_Model_XmlUploader
{
    public function __construct()
    {
        parent::__construct(Mage::getStoreConfig('brera/magentoconnector/stock_xml_target'));
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return basename(Mage::getStoreConfig('brera/magentoconnector/stock_xml_target'));
    }

}
