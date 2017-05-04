<?php

class LizardsAndPumpkins_MagentoConnector_Model_StockXmlUploader
    extends LizardsAndPumpkins_MagentoConnector_Model_XmlUploader
{
    public function __construct()
    {
        parent::__construct(Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/stock_xml_target'));
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return basename(Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/stock_xml_target'));
    }

}
