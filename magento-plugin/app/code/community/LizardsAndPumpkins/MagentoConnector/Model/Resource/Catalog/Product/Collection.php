<?php

class LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection
    extends Mage_Catalog_Model_Resource_Product_Collection
{
    const FLAG_LOAD_ASSOCIATED_PRODUCTS = 'load-associated-simple-products';

    /**
     * @var string[]
     */
    private $productIds = [];

    /**
     * @return bool
     */
    public function isEnabledFlat()
    {
        return false;
    }

    /**
     * @param bool $printQuery
     * @param bool $logQuery
     * @return void
     */
    public function load($printQuery = false, $logQuery = false)
    {
        throw new \LogicException('This collection should only be used to load raw data via getData()');
    }

    private function _beforeLoadData()
    {
        // todo: is_in_stock, is_salable
        $this->addCategoryUrlKeys();
        $this->addStockItemData();
        $this->addAttributeToSelect(['tax_class_id', 'visibility', 'status']);
        $this->addConfigurableAttributeCodes();
    }

    public function getData()
    {
        $this->_beforeLoadData();
        return parent::getData();
    }

    /**
     * @return Varien_Data_Collection_Db
     */
    protected function _afterLoadData()
    {
        $websiteCode = $this->getStore()->getWebsite()->getCode();
        $localeCode = Mage::getStoreConfig('general/locale/code', $this->getStore());
        $indexedProductData = [];
        foreach ($this->_data as $row) {
            $indexedProductData[$row['entity_id']] = array_merge(
                $row,
                ['categories' => $this->categoryUrlKeysToPaths($row['categories'])],
                ['configurable_attributes' => $this->configAttributeIdsToCodes($row['configurable_attributes'])],
                ['website' => $websiteCode],
                ['locale' => $localeCode]
            );
        }
        $this->_data = $indexedProductData;

        $this->loadEavAttributeValues();
        $this->mergeAdditionalProductData();

        return parent::_afterLoadData();
    }

    private function mergeAdditionalProductData()
    {
        $mediaGalleryData = $this->loadMediaGalleryData();
        $associatedProductData = $this->getFlag(self::FLAG_LOAD_ASSOCIATED_PRODUCTS) ?
            $this->loadAssociatedSimpleProductData() :
            [];

        foreach ($this->_data as $productId => $productData) {
            $this->_data[$productId]['media_gallery'] = isset($mediaGalleryData[$productId]) ?
                $mediaGalleryData[$productId] :
                [];
            $this->_data[$productId]['associated_products'] = isset($associatedProductData[$productId]) ?
                $associatedProductData[$productId] :
                [];
            $this->_data[$productId]['is_in_stock'] = $this->isInStock($this->_data[$productId]);
        }
    }

    /**
     * @param array $productData
     * @return string
     */
    private function isInStock(array $productData)
    {
        if (isset($productData['associated_products']) && count($productData['associated_products']) > 0) {
            $isInStock = array_reduce($productData['associated_products'], function ($isSalable, $childProduct) {
                return $isSalable || $this->isInStock($childProduct); 
            }, false);
        } else {
            $isInStock = $productData['stock_qty'] > 0 && 'true' === $productData['backorders'];
        }
        return sprintf('%d', $isInStock);
    }

    /**
     * @param string|string[] $productId
     * @param bool $exclude
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function addIdFilter($productId, $exclude = false)
    {
        $this->productIds = $productId;
        return parent::addIdFilter($productId, $exclude);
    }

    private function addCategoryUrlKeys()
    {
        $table = Mage::getSingleton('core/resource')->getTableName('catalog/category_product');
        $connection = $this->getConnection();
        $categoryUrlKeyAttribute = Mage::getSingleton('eav/config')->getAttribute('catalog_category', 'url_key');
        $select = $this->getSelect();
        $columnValue = new Zend_Db_Expr("GROUP_CONCAT(IFNULL(category_s.value, category_d.value) SEPARATOR '|||')");
        $select->joinInner(
            ['category_link' => $table],
            'e.entity_id=category_link.product_id',
            []
        );
        $select->joinInner(
            ['category_d' => $categoryUrlKeyAttribute->getBackend()->getTable()],
            "category_link.category_id=category_d.entity_id AND category_d.attribute_id='{$categoryUrlKeyAttribute->getId()}' AND category_d.store_id=0",
            []
        );
        $select->joinLeft(
            ['category_s' => $categoryUrlKeyAttribute->getBackend()->getTable()],
            $connection->quoteInto(
                "category_link.category_id=category_s.entity_id AND category_s.attribute_id='{$categoryUrlKeyAttribute->getId()}' AND category_s.store_id=?",
                $this->getStoreId()
            ),
            ['categories' => $columnValue]
        );
        $this->groupSelectBy($this->getSelect(), 'e.entity_id');
    }

    /**
     * @return Mage_Core_Model_Store
     */
    private function getStore()
    {
        return Mage::app()->getStore($this->getStoreId());
    }

    public function addStockItemData()
    {
        $defaultBackOrders = $this->getStore()->getConfig('cataloginventory/item_options/backorders') ? 'true' : 'false';

        $table = Mage::getSingleton('core/resource')->getTableName('cataloginventory/stock_item');

        $stockItemBackordersIf = new Zend_Db_Expr(
            "IF(stock_item.backorders > 0, 'true', 'false')"
        );
        $configBackordersIf = new Zend_Db_Expr(
            "IF(use_config_backorders > 0, '{$defaultBackOrders}', {$stockItemBackordersIf})"
        );

        $select = $this->getSelect();
        $select->join(
            ['stock_item' => $table],
            'e.entity_id=stock_item.product_id',
            ['stock_qty' => 'qty', 'backorders' => $configBackordersIf]
        );
    }

    private function addConfigurableAttributeCodes()
    {
        $this->getSelect()->joinLeft(
            ['configurable_attribute' => $this->getResource()->getTable('catalog/product_super_attribute')],
            "e.entity_id=configurable_attribute.product_id",
            ['configurable_attributes' => new Zend_Db_Expr("GROUP_CONCAT(configurable_attribute.attribute_id SEPARATOR ',')")]
        );
        $this->groupSelectBy($this->getSelect(), 'e.entity_id');
    }

    /**
     * @param Zend_Db_Select $select
     * @param string $column
     */
    public function groupSelectBy(Zend_Db_Select $select, $column)
    {
        if (!in_array($column, $select->getPart(Zend_Db_Select::GROUP))) {
            $select->group($column);
        }
    }

    /**
     * @return array[]
     */
    public function loadAssociatedSimpleProductData()
    {
        $coreResource = Mage::getSingleton('core/resource');
        $connection = $this->getConnection();

        /** @var LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection $simpleProducts */
        $simpleProducts = Mage::getResourceModel('lizardsAndPumpkins_magentoconnector/catalog_product_collection');
        $simpleProducts->addAttributeToSelect('sku');
        $simpleProducts->addAttributeToFilter('type_id', Mage_Catalog_Model_Product_Type::TYPE_SIMPLE);
        $simpleProducts->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $simpleProducts->addAttributeToSelect($this->getConfigurableAttributeIdToCodeMap());

        $select = $simpleProducts->getSelect();
        $select->joinInner(
            ['link' => $coreResource->getTableName('catalog/product_super_link')],
            "e.entity_id=link.product_id AND link.parent_id IN ({$connection->quote($this->productIds)})",
            ['parent_id' => 'link.parent_id']
        );

        $simpleProductData = [];
        foreach ($simpleProducts->getData() as $row) {
            $simpleProductData[$row['parent_id']][] = $row;
        }

        return $simpleProductData;
    }

    /**
     * @return array[]
     */
    public function loadMediaGalleryData()
    {
        $mediaGalleryAttributeId = Mage::getSingleton('eav/config')
            ->getAttribute('catalog_product', 'media_gallery')
            ->getAttributeId();
        /* @var $coreResource Mage_Core_Model_Resource */
        $coreResource = Mage::getSingleton('core/resource');
        /* @var $readConnection Varien_Db_Adapter_Interface */
        $readConnection = $this->getConnection();
        $mediaGalleryTable = $coreResource->getTableName('catalog/product_attribute_media_gallery');
        $mediaGalleryValueTable = $coreResource->getTableName('catalog/product_attribute_media_gallery_value');

        $productIds = array_keys($this->_data);

        $select = $readConnection->select();
        $select->from(
            ['main' => $mediaGalleryTable],
            ['entity_id', 'value_id', 'file' => 'value']
        );
        $select->joinLeft(
            ['value' => $mediaGalleryValueTable],
            $readConnection->quoteInto("main.value_id=value.value_id AND value.store_id=?", $this->getStoreId()),
            ['label', 'position', 'label_default' => 'value.label']
        );
        $select->joinLeft(
            ['default_value' => $mediaGalleryValueTable],
            "main.value_id=default_value.value_id AND default_value.store_id=0",
            ['position_default' => 'default_value.position', 'disabled_default' => 'default_value.disabled']
        );
        $select->where("main.attribute_id=?", $mediaGalleryAttributeId);
        $select->where("main.entity_id IN (?)", $productIds);
        $select->order(new Zend_Db_Expr("IF(value.position IS NULL, default_value.position, value.position) ASC"));

        $mediaGalleryData = [];
        foreach ($readConnection->fetchAll($select) as $row) {
            $mediaGalleryData[$row['entity_id']]['images'][] = $row;
        }
        return $mediaGalleryData;
    }

    /**
     * @param string $combinedCategoryUrlKeys
     * @return string[]
     */
    private function categoryUrlKeysToPaths($combinedCategoryUrlKeys)
    {
        $suffix = Mage::getStoreConfig('catalog/seo/category_url_suffix', $this->getStoreId());
        return array_map(function ($urlKey) use ($suffix) {
            return $urlKey . '.' . $suffix;
        }, array_unique(explode('|||', $combinedCategoryUrlKeys)));
    }

    /**
     * @param string $configurableAttributeIds
     * @return string[]
     */
    private function configAttributeIdsToCodes($configurableAttributeIds)
    {
        $configurableAttributes = $this->getConfigurableAttributeIdToCodeMap();
        return $configurableAttributeIds ?
            array_map(function ($configurableAttributeId) use ($configurableAttributes) {
                return $configurableAttributes[$configurableAttributeId];
            }, array_unique(explode(',', $configurableAttributeIds))) :
            [];
    }

    /**
     * @return string[]
     */
    private function getConfigurableAttributeIdToCodeMap()
    {
        static $configurableAttributes;
        if (null === $configurableAttributes) {
            $coreResource = Mage::getSingleton('core/resource');
            $connection = $coreResource->getConnection('default_read');
            $attributesTable = $coreResource->getTableName('eav/attribute');
            $configurableAttributesTable = $coreResource->getTableName('catalog/product_super_attribute');
            $configurableAttributes = $connection->fetchPairs($connection->select()
                ->from(['config_attributes' => $configurableAttributesTable], '')
                ->distinct(true)
                ->joinInner(
                    ['eav_attributes' => $attributesTable],
                    'config_attributes.attribute_id=eav_attributes.attribute_id',
                    ['attribute_id', 'attribute_code']
                ));
        }
        return $configurableAttributes;
    }

    /**
     * @param int $attributeId
     * @return string[]
     */
    private function getEavAttributeOptions($attributeId)
    {
        static $eavAttributeOptions;
        $storeId = $this->getStoreId();
        if (
            null === $eavAttributeOptions ||
            !isset($eavAttributeOptions[$attributeId]) ||
            !isset($eavAttributeOptions[$attributeId][$storeId])
        ) {
            $eavAttributeOptions[$attributeId][$storeId] = [];
            if (isset($this->getAttributeToSourceModelMap()[$attributeId])) {
                $eavAttributeOptions[$attributeId][$storeId] = $this->loadOptionsForAttribute($attributeId);
            }
        }
        return $eavAttributeOptions[$attributeId][$storeId];
    }

    /**
     * @param int $attributeId
     * @return mixed[]
     */
    private function loadOptionsForAttribute($attributeId)
    {
        $sourceModel = $this->getAttributeToSourceModelMap()[$attributeId];
        $source = $this->createSourceModel($sourceModel, $attributeId);
        $options = [];
        if ($source->getAttribute()->getAttributeCode() === 'visibility') {
            foreach (Mage_Catalog_Model_Product_Visibility::getOptionArray() as $value => $label) {
                $options[$value] = array_map('trim', explode(',', $label));
            }
        } else {
            foreach ($source->getAllOptions() as $option) {
                if (! is_array($option['value'])) {
                    $options[$option['value']] = $option['label'];
                }
            }
        }
        return $options;
    }

    /**
     * @param string $sourceModelClassId
     * @param int $attributeId
     * @return Mage_Eav_Model_Entity_Attribute_Source_Abstract
     */
    private function createSourceModel($sourceModelClassId, $attributeId)
    {
        /** @var Mage_Eav_Model_Entity_Attribute_Source_Abstract $sourceModel */
        $sourceModel = Mage::getModel($sourceModelClassId);
        $attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $attributeId);
        $attribute->setStoreId($this->getStoreId());
        $sourceModel->setAttribute($attribute);
        return $sourceModel;
    }

    private function getAttributeToSourceModelMap()
    {
        static $attributeToSourceModelMap;
        if (null === $attributeToSourceModelMap) {
            $coreResource = Mage::getSingleton('core/resource');
            $connection = $coreResource->getConnection('default_read');
            $attributesTable = $coreResource->getTableName('eav/attribute');
            $select = $connection->select();
            $select->from(
                $attributesTable, [
                    'attribute_id',
                    'source_model' => new Zend_Db_Expr("IFNULL(source_model, 'eav/entity_attribute_source_table')"),
                ]
            );
            $select->where('entity_type_id=?',
                Mage::getSingleton('eav/config')->getEntityType('catalog_product')->getId());
            $select->where('frontend_input IN(?)', ['select', 'multiselect']);
            $attributeToSourceModelMap = $connection->fetchPairs($select);
        }
        return $attributeToSourceModelMap;
    }

    /**
     * @return string[]
     */
    private function getTaxClassIdToNameMap()
    {
        static $taxClassNames;
        if (null === $taxClassNames) {
            $sourceModel = Mage::getModel('tax/class_source_product');
            $options = $sourceModel->getAllOptions();
            $taxClassNames = array_reduce($options, function ($carry, array $option) {
                $carry[$option['value']] = $option['label']; // don't use array_merge() because of numeric array keys
                return $carry;
            }, []);
        }
        return $taxClassNames;
    }

    private function loadEavAttributeValues()
    {
        $this->_loadAttributes();
    }

    /**
     * @param bool $printQuery
     * @param bool $logQuery
     * @return $this
     */
    public function _loadAttributes($printQuery = false, $logQuery = false)
    {
        // Force _loadAttributes to run even though no items are instantiated
        $this->_items = true;
        $this->_itemsById = $this->_data;

        return parent::_loadAttributes($printQuery, $logQuery);
    }

    /**
     * @param string[] $valueInfo
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     * @throws Mage_Core_Exception
     */
    protected function _setItemAttributeValue($valueInfo)
    {
        $attributeId = $valueInfo['attribute_id'];
        $rawValue = $valueInfo['value'];
        $attributeCode = array_search($attributeId, $this->_selectAttributes);

        if ($this->hasOptions($attributeId)) {
            $value = $this->getValuesFromOptions($attributeId, $rawValue);
        } else {
            $value = $rawValue;
        }
        $this->_data[$valueInfo['entity_id']][$attributeCode] = $value;
    }

    /**
     * @param int $attributeId
     * @return bool
     */
    private function hasOptions($attributeId)
    {
        return count($this->getEavAttributeOptions($attributeId)) > 0;
    }

    /**
     * @param int $attributeId
     * @param string $rawValue
     * @return string[]|string
     */
    private function getValuesFromOptions($attributeId, $rawValue)
    {
        $options = $this->getEavAttributeOptions($attributeId);
        $values = explode(',', $rawValue);
        if (count($values) > 1) {
            $value = array_map(function ($optionId) use ($options) {
                return $this->getOptionValue($options, $optionId);
            }, $values);
        } else {
            $value = $this->getOptionValue($options, $rawValue);
        }
        return $value;
    }

    /**
     * @param string[] $options
     * @param string $optionId
     * @return string
     */
    private function getOptionValue(array $options, $optionId)
    {
        return isset($options[$optionId]) ?
            $options[$optionId] :
            $optionId;
    }
}
