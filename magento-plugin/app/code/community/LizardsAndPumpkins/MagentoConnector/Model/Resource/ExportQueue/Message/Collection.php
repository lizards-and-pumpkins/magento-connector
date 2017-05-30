<?php

class LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('lizardsAndPumpkins_magentoconnector/exportQueue_message');
    }
}
