<?php

/**
 * Class Brera_MagentoConnector_Model_Product_Queue_Item
 *
 * @method $this setProductId(int)
 * @method int getProductId()
 * @method string getAction()
 * @method $this setAction(string)
 */
class Brera_MagentoConnector_Model_Product_Queue_Item extends Mage_Core_Model_Abstract
{

    const ACTION_CREATE_AND_UPDATE = 'create';
    const ACTION_DELETE = 'delete';
    const ACTION_STOCK_UPDATE = 'stock_update';

    protected function _construct()
    {
        $this->_init('brera_magentoconnector/product_queue_item');
    }

    /**
     * @param int[] $productIds
     * @param string $action
     */
    public function saveProductIds(array $productIds, $action)
    {
        $this->getResource()->saveProductIds($productIds, $action);
    }
}
