<?php

class LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Category_Collection
    extends Mage_Catalog_Model_Resource_Category_Collection
{
    /**
     * @var array[]
     */
    private $urlKeysByStore = [];

    /**
     * @var array[]
     */
    private $categoryDataByStore = [];

    /**
     * @var string[]
     */
    private $rootCategoryIdPaths = [];

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
        $storeId = Mage::app()->getStore($store)->getId();
        if (!isset($this->categoryDataByStore[$storeId])) {
            $this->categoryDataByStore[$storeId] = $this->loadDataForStore($storeId);
        }
        return $this->categoryDataByStore[$storeId];
    }

    /**
     * @param int $storeId
     * @return array[]
     */
    public function loadDataForStore($storeId)
    {
        if (null === $this->_data) {
            $this->addAttributeToSelect(['path', 'is_anchor']);
            $this->limitResultsToChildrenOfStoreRootCategory($storeId);
            parent::getData();
        }
        $storeUrlKeys = $this->getCategoryUrlKeysByStore($storeId);
        return $this->addUrlKeysToCategoryData($this->_data, $storeUrlKeys);
    }

    /**
     * @param array[] $categoryData
     * @param string[] $storeUrlKeys
     * @return mixed
     */
    private function addUrlKeysToCategoryData(array $categoryData, array $storeUrlKeys)
    {
        $mergedData = [];
        foreach ($categoryData as $categoryId => $categoryInfo) {
            $mergedData[$categoryId] = $categoryInfo;
            $mergedData[$categoryId]['url_key'] = $storeUrlKeys[$categoryId];
        }
        return $mergedData;
    }

    /**
     * @param int $storeId
     * @return string[]
     */
    private function getCategoryUrlKeysByStore($storeId)
    {
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
                ->where('t_d.entity_id IN (?)', array_keys($this->_data))
                ->where('t_d.attribute_id=?', $attribute->getId());
            $this->urlKeysByStore[$storeId] = $this->getConnection()->fetchPairs($select);
        }
        return $this->urlKeysByStore[$storeId];
    }

    private function getRootCategoryIdPathForStore($storeId)
    {
        if (!isset($this->rootCategoryIdPaths[$storeId])) {
            $select = $this->getConnection()->select();
            $categoryTable = $this->getResource()->getEntityTable();
            $select->from(['s' => $this->getTable('core/store')], ['store_id']);
            $select->joinInner(['g' => $this->getTable('core/store_group')], 's.group_id=g.group_id', []);
            $select->joinInner(['c' => $categoryTable], 'g.root_category_id=c.entity_id', ['path']);
            $this->rootCategoryIdPaths = $this->getConnection()->fetchPairs($select);
        }
        return $this->rootCategoryIdPaths[$storeId];
    }

    protected function _afterLoadData()
    {
        $this->_data = array_reduce(parent::getData(), function ($carry, array $row) {
            $carry[$row['entity_id']] = array_merge(
                $row,
                ['is_anchor' => 0],
                ['parent_ids' => $this->getParentIdsWithoutRootAndCurrentCategory($row['path'])]
            );
            return $carry;
        }, []);
        $this->_loadAttributes();
        return parent::_afterLoadData();
    }

    /**
     * @param string $idPath
     * @return string[]
     */
    private function getParentIdsWithoutRootAndCurrentCategory($idPath)
    {
        return array_slice(explode('/', $idPath), 2, -1);
    }

    public function _loadAttributes($printQuery = false, $logQuery = false)
    {
        $this->_items = ['dummy value so the parent attribute load method is executed'];
        $this->_itemsById = array_flip(array_keys($this->_data));
        return parent::_loadAttributes($printQuery, $logQuery);
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

    /**
     * @param int $storeId
     */
    protected function limitResultsToChildrenOfStoreRootCategory($storeId)
    {
        $this->getSelect()->where("e.path like ?", $this->getRootCategoryIdPathForStore($storeId) . '/%');
    }
}
