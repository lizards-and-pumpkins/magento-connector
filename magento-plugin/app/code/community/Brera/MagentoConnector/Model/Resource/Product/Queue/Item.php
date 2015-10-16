<?php

class Brera_MagentoConnector_Model_Resource_Product_Queue_Item extends Mage_Core_Model_Resource_Db_Abstract
{

    protected function _construct()
    {
        $this->_init('brera_magentoconnector/product_queue', 'product_queue_id');
    }

    /**
     * @param int[] $productIds
     * @param string $action
     */
    public function saveProductIds(array $productIds, $action)
    {
        $this->insert($productIds, $action, 'id');
    }

    /**
     * @param string[] $skus
     * @param string $action
     */
    public function saveProductSkus(array $skus, $action)
    {
        $this->insert($skus, $action, 'sku');
    }

    /**
     * @param string[]|int[] $identifier
     * @param string $action
     * @param string $column
     */
    private function insert($identifier, $action, $column)
    {
        $dataToInsert = [];
        foreach ($identifier as $value) {
            $dataToInsert[] = [
                'product_' . $column => $value,
                'action' => $action,
            ];
        }
        $this->_getWriteAdapter()->insertOnDuplicate($this->getMainTable(), $dataToInsert);
    }

    /**
     * @param Brera_MagentoConnector_Model_Product_Queue_Item $object
     * @return $this
     */
    public function save(Mage_Core_Model_Abstract $object)
    {
        if ($object->isDeleted()) {
            return $this->delete($object);
        }

        $this->_serializeFields($object);
        $this->_beforeSave($object);
        $this->_checkUnique($object);
        $bind = $this->_prepareDataForSave($object);
        if ($this->_isPkAutoIncrement) {
            unset($bind[$this->getIdFieldName()]);
        }
        $this->_getWriteAdapter()->insertOnDuplicate($this->getMainTable(), $bind);

        $object->setId($this->_getWriteAdapter()->lastInsertId($this->getMainTable()));

        $this->unserializeFields($object);
        $this->_afterSave($object);

        return $this;
    }

    /**
     * @param int[] $ids
     * @param string[] $skus
     * @param string $action
     */
    public function cleanupQueue(array $ids, array $skus, $action)
    {
        $ids = implode(',', $ids);
        $skus = "'" . implode('","', $skus) . "'";
        $where = new Zend_Db_Expr("action = '$action' AND (product_id IN ($ids) OR product_sku IN ($skus))");
        $this->_getWriteAdapter()->delete($this->getMainTable(), $where);
    }

    public function addAllProductIdsToStockExport()
    {
        $helper = Mage::helper('brera_magentoconnector');
        $ids = Mage::getResourceModel('catalog/product_collection')
            ->getAllIds();
        foreach ($ids as $id) {
            $helper->addStockUpdateToQueue($id);
        }
    }
}
