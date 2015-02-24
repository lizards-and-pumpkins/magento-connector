<?php

/**
 * Class Brera_MagentoConnector_Model_Product_Queue_Item
 *
 * @method $this setProductId(int)
 * @method int getProductId()
 */
class Brera_MagentoConnector_Model_Product_Queue_Item extends Mage_Core_Model_Abstract
{

    protected function _construct()
    {
        $this->_init('brera_magentoconnector/product_queue_item');
    }
}
