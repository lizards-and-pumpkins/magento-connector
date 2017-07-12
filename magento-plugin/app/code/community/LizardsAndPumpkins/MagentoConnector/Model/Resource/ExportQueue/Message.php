<?php

class LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message
    extends Mage_Core_Model_Resource_Db_Abstract
{
    const TABLE = 'lizardsAndPumpkins_magentoconnector/queue';
    const ID_FIELD = 'id';
    
    protected function _construct()
    {
        $this->_init(self::TABLE, self::ID_FIELD);
    }
}
