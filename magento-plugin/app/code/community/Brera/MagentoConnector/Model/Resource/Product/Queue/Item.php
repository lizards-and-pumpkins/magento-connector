<?php

class Brera_MagentoConnector_Model_Resource_Product_Queue_Item extends Mage_Core_Model_Resource_Db_Abstract
{

    protected function _construct()
    {
        $this->_init('brera_magentoconnector/product_queue', 'product_queue_id');
    }

    /**
     * @param array $productIds
     * @param string $action
     */
    public function saveProductIds(array $productIds, $action)
    {
        $dataToInsert = array();
        foreach ($productIds as $productId) {
            $dataToInsert[] = array(
                'product_id' => $productId,
                'action' => $action,
            );
        }
        $this->_getWriteAdapter()->insertMultiple($this->getMainTable(), $dataToInsert);
    }
}
