<?php

class LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Category_Collection
    extends Mage_Catalog_Model_Resource_Category_Collection
{
    /**
     * @var array[]
     */
    private $urlKeysByStore = [];

    public function load($printQuery = false, $logQuery = false)
    {
        Mage::throwException('Do not use load(), use getDataForStore() instead');
    }

    public function getData()
    {
        Mage::throwException('Do not use getData(), use getDataForStore() instead');
    }

    /**
     * @param int|string|Mage_Core_Model_Store $store
     * @return array[]
     */
    public function getDataForStore($store)
    {
        if (null === $this->_data) {
            $this->addAttributeToSelect(['path', 'is_anchor']);
            parent::getData();
        }
        $storeUrlKeys = $this->getCategoryUrlKeysByStore($store);
        return array_reduce(array_keys($this->_data), function (array $carry, $categoryId) use ($storeUrlKeys) {
            $carry[$categoryId] = array_merge(
                $this->_data[$categoryId],
                ['url_key' => $storeUrlKeys[$categoryId]]
            );
            return $carry;
        }, []);
    }

    /**
     * @param int|string|Mage_Core_Model_Store $store
     * @return string[]
     */
    private function getCategoryUrlKeysByStore($store)
    {
        $storeId = Mage::app()->getStore($store)->getId();
        if (!isset($this->urlKeysByStore[$storeId])) {
            $attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_category', 'url_key');
            $table = $attribute->getBackend()->getTable();
            $select = $this->getConnection()->select()
                ->from(
                    ['t_d' => $table],
                    ['entity_id', 'url_key' => 'IFNULL(t_s.value, t_d.value)']
                )
                ->joinLeft(
                    ['t_s' => $table],
                    $this->getConnection()->quoteInto(
                        't_d.attribute_id=t_s.attribute_id AND t_d.entity_id=t_s.entity_id AND t_s.store_id=?',
                        $storeId
                    ),
                    []
                )
                ->where('t_d.store_id=0')
                ->where('t_d.attribute_id=?', $attribute->getId());
            $this->urlKeysByStore[$storeId] = $this->getConnection()->fetchPairs($select);
        }
        return $this->urlKeysByStore[$storeId];
    }

    /**
     * @return Mage_Catalog_Model_Resource_Category
     */
    public function getResource()
    {
        return parent::getResource();
    }

    protected function _afterLoadData()
    {
        $this->_data = array_reduce(parent::getData(), function ($carry, array $row) {
            $carry[$row['entity_id']] = array_merge(
                $row,
                ['is_anchor' => 0],
                ['parent_ids' => explode('/', $row['path'])]
            );
            return $carry;
        }, []);
        $this->_loadAttributes();
        return parent::_afterLoadData();
    }

    public function _loadAttributes($printQuery = false, $logQuery = false)
    {
        $this->_items = ['dummy value so the parent attribute load method is executed'];
        $this->_itemsById = ['dummy value so the parent attribute load method is executed'];
        return parent::_loadAttributes($printQuery, $logQuery);
    }

    protected function _getLoadAttributesSelect($table, $attributeIds = [])
    {
        /** @var Varien_Db_Select $select */
        $select = parent::_getLoadAttributesSelect($table, $attributeIds);
        $where = array_filter($select->getPart(Zend_Db_Select::WHERE), function ($condition) {
            return strpos($condition, 'entity_id IN (0)') === false;
        });
        $select->setPart(Zend_Db_Select::WHERE, $where);
        return $select;
    }

    protected function _setItemAttributeValue($valueInfo)
    {
        $attributeCode = array_search($valueInfo['attribute_id'], $this->_selectAttributes);
        if (!$attributeCode) {
            $attribute = Mage::getSingleton('eav/config')->getCollectionAttribute(
                $this->getEntity()->getType(),
                $valueInfo['attribute_id']
            );
            $attributeCode = $attribute->getAttributeCode();
        }
        $this->_data[$valueInfo['entity_id']][$attributeCode] = $valueInfo['value'];
        return $this;
    }
}
