<?php
 
class Brera_MagentoConnector_Model_Resource_Product_Queue_Item_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    protected function _construct()
    {
        $this->_init('brera_magentoconnector/product_queue_item');
    }

}
