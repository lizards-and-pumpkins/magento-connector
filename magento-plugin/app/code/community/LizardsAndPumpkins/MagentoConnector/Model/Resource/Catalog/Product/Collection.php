<?php

declare(strict_types = 1);

class LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection
    extends Mage_Catalog_Model_Resource_Product_Collection
{
    const FLAG_LOAD_ASSOCIATED_PRODUCTS = 'load-associated-simple-products';
    const FLAG_ADD_CATEGORY_IDS = 'add-category-ids';

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
        if ($this->getFlag(self::FLAG_ADD_CATEGORY_IDS)) {
            $this->addCategoryIdsToSelect();
        }
        $this->addStockItemData();
        $this->addAttributeToSelect(['tax_class_id', 'visibility', 'status']);
        $this->addConfigurableAttributeCodes();
    }

    /**
     * @return array[]
     */
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
        $indexedProductData = [];
        foreach ($this->_data as $row) {
            $indexedProductData[$row['entity_id']] = array_merge(
                $row,
                ['categories' => isset($row['category_ids']) ? $this->addCategoryUrlKeys($row['category_ids']) : []],
                ['configurable_attributes' => $this->configAttributeIdsToCodes($row['configurable_attributes'])],
                ['website' => $this->getStore()->getCode()],
                ['locale' => Mage::getStoreConfig('general/locale/code', $this->getStore())]
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
            $this->_data[$productId]['backorders'] = $this->hasBackorders($this->_data[$productId]) ?
                'true' :
                'false';
            $this->_data[$productId]['stock_qty'] = $this->getStockQty($this->_data[$productId]);
            $this->_data[$productId]['is_in_stock'] = $this->isInStock($this->_data[$productId]);
            $this->_data[$productId]['url_key'] = $this->getUrlKey($this->_data[$productId]);
            if (isset($this->_data[$productId]['category_ids'])) {
                $this->_data[$productId]['non_canonical_url_key'] = $this->addProductInCategoryUrlKeys(
                    $this->_data[$productId]['category_ids'],
                    $this->_data[$productId]['url_key']
                );
            }
            if (isset($this->_data[$productId]['price'])) {
                $this->_data[$productId]['price'] = $this->getPriceExcludingTax($this->_data[$productId], 'price');
            }
            if (isset($this->_data[$productId]['special_price'])) {
                $this->_data[$productId]['special_price'] = $this->getPriceExcludingTax(
                    $this->_data[$productId],
                    'special_price'
                );
            }
        }
    }

    /**
     * @param mixed[] $productData
     * @param string $priceAttribute
     * @return float
     */
    private function getPriceExcludingTax(array $productData, $priceAttribute)
    {
        /** @var Mage_Tax_Model_Config $taxConfig */
        $taxConfig = Mage::getSingleton('tax/config');
        return $taxConfig->priceIncludesTax($this->getStore()) ?
            $this->calculatePriceExcludingTax($productData['tax_class_id'], $productData[$priceAttribute]) :
            $productData[$priceAttribute];
    }

    /**
     * @param int $taxClassId
     * @param string $price
     * @return float
     */
    private function calculatePriceExcludingTax($taxClassId, $price)
    {
        /** @var Mage_Catalog_Model_Product $product */
        static $product;
        static $taxRatePercentCache;
        if (null === $product) {
            $product = Mage::getModel('catalog/product');
            $taxRatePercentCache = [];
        }
        $product->setData(['tax_class_id' => $taxClassId]);
        if (isset($taxRatePercentCache[$this->getStoreId()][$taxClassId])) {
            $product->setData('tax_percent', $taxRatePercentCache[$this->getStoreId()][$taxClassId]['tax_percent']);
            $product->setData('applied_rates', $taxRatePercentCache[$this->getStoreId()][$taxClassId]['applied_rates']);
        }
        /** @var Mage_Tax_Helper_Data $helper */
        $helper = Mage::helper('tax');
        $priceExclTax = $helper->getPrice(
            $product,
            $price,
            $includingTax = false,
            $shippingAddress = null,
            $billingAddress = null,
            $ctc = null,
            $this->getStore(),
            $pricesIncludeTax = true,
            $roundPrice = false
        );
        $taxRatePercentCache[$this->getStoreId()][$taxClassId]['tax_percent'] = $product->getData('tax_percent');
        $taxRatePercentCache[$this->getStoreId()][$taxClassId]['applied_rates'] = $product->getData('applied_rates');

        return $priceExclTax;
    }

    /**
     * @param mixed[] $productData
     * @return bool
     */
    private function hasBackorders(array $productData)
    {
        if (isset($productData['associated_products']) && count($productData['associated_products']) > 0) {
            return array_reduce($productData['associated_products'], function ($carry, $childProduct) {
                return $carry || $this->hasBackorders($childProduct);
            }, false);
        }

        return isset($productData['backorders']) && 'true' === $productData['backorders'];
    }

    /**
     * @param mixed[] $productData
     * @return string
     */
    private function isInStock(array $productData)
    {
        if (isset($productData['associated_products']) && count($productData['associated_products']) > 0) {
            $isInStock = array_reduce($productData['associated_products'], function ($isSalable, $childProduct) {
                return $isSalable || $this->isInStock($childProduct);
            }, false);
        } else {
            $hasBackorders = isset($productData['backorders']) && 'true' === $productData['backorders'];
            $isInStock = $productData['stock_qty'] > 0 && $hasBackorders;
        }
        return sprintf('%d', $isInStock);
    }

    /**
     * @param mixed[] $productData
     * @return int
     */
    private function getStockQty(array $productData)
    {
        if (isset($productData['associated_products']) && count($productData['associated_products']) > 0) {
            return array_reduce($productData['associated_products'], function ($stockQty, $childProduct) {
                return $stockQty + $this->getStockQty($childProduct);
            }, 0);
        }

        return $productData['stock_qty'];
    }

    /**
     * @param mixed[] $productData
     * @return string
     */
    private function getUrlKey(array $productData)
    {
        static $productUrlKeySuffix;
        $storeId = $this->getStoreId();
        if (is_null($productUrlKeySuffix) || !isset($productUrlKeySuffix[$storeId])) {
            $productUrlKeySuffix[$storeId] = Mage::getStoreConfig('catalog/seo/product_url_suffix', $storeId);
        }
        return isset($productData['url_key']) && $productUrlKeySuffix[$storeId] ?
            $productData['url_key'] . $productUrlKeySuffix[$storeId] :
            'catalog/product/view/id/' . $productData['entity_id'];
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

    private function addCategoryIdsToSelect()
    {
        $table = $this->getCoreResource()->getTableName('catalog/category_product');
        $select = $this->getSelect();
        $select->joinInner(
            ['category_link' => $table],
            'e.entity_id=category_link.product_id',
            ['category_ids' => new Zend_Db_Expr("GROUP_CONCAT(category_link.category_id SEPARATOR ',')")]
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
        $defaultBackOrders = $this->getStore()->getConfig('cataloginventory/item_options/backorders') ?
            'true' :
            'false';

        $table = $this->getCoreResource()->getTableName('cataloginventory/stock_item');

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
            ['stock_qty' => 'FLOOR(qty)', 'backorders' => $configBackordersIf]
        );
    }

    private function addConfigurableAttributeCodes()
    {
        $this->getSelect()->joinLeft(
            ['configurable_attribute' => $this->getResource()->getTable('catalog/product_super_attribute')],
            "e.entity_id=configurable_attribute.product_id",
            [
                'configurable_attributes' => new Zend_Db_Expr(
                    "GROUP_CONCAT(configurable_attribute.attribute_id SEPARATOR ',')"
                ),
            ]
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
    private function loadAssociatedSimpleProductData()
    {
        $coreResource = $this->getCoreResource();
        $connection = $this->getConnection();

        $configurableAttributes = $this->getConfigurableAttributeIdToCodeMap();

        /** @var LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection $simpleProducts */
        $simpleProducts = Mage::getResourceModel('lizardsAndPumpkins_magentoconnector/catalog_product_collection');
        $simpleProducts->addAttributeToSelect('sku');
        $simpleProducts->addAttributeToFilter('type_id', Mage_Catalog_Model_Product_Type::TYPE_SIMPLE);
        $simpleProducts->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $simpleProducts->addAttributeToSelect($configurableAttributes);

        $configValue = Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/associated_product_attributes');
        $additionalAttributes = array_filter(preg_split('/\s*,\s*/', trim($configValue), -1, PREG_SPLIT_NO_EMPTY));
        if (count($additionalAttributes) > 0) {
            $simpleProducts->addAttributeToSelect($additionalAttributes);
        }

        $select = $simpleProducts->getSelect();
        $select->joinLeft(
            ['super_link' => $coreResource->getTableName('catalog/product_super_link')],
            "e.entity_id = super_link.product_id AND super_link.parent_id IN ({$connection->quote($this->productIds)})",
            []
        );
        $select->joinLeft(
            ['link' => $coreResource->getTableName('catalog/product_link')],
            "e.entity_id = link.linked_product_id AND link.product_id IN ({$connection->quote($this->productIds)})",
            []
        );

        $select->columns(
            new Zend_Db_Expr('IF (link.product_id IS NOT NULL, link.product_id, super_link.parent_id) parent_id')
        );
        $select->where('link.product_id IS NOT NULL OR super_link.parent_id IS NOT NULL');

        $attributesToCopy = array_merge(
            $this->getRequiredAttributeCodes(),
            $this->getConfigurableAttributeIdToCodeMap(),
            $additionalAttributes
        );

        return array_reduce($simpleProducts->getData(), function (array $carry, array $data) use ($attributesToCopy) {
            $carry[$data['parent_id']][] = $this->getProductAttributes($attributesToCopy, $data);
            return $carry;
        }, []);
    }

    /**
     * @return array[]
     */
    public function loadMediaGalleryData()
    {
        $mediaGalleryAttributeId = $this->getEavConfig()
            ->getAttribute('catalog_product', 'media_gallery')
            ->getAttributeId();

        $coreResource = $this->getCoreResource();
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
     * @param string[] $attributeCodes
     * @param string[] $productData
     * @return string[]
     */
    private function getProductAttributes($attributeCodes, $productData)
    {
        return array_reduce($attributeCodes, function ($carry, $attribute) use ($productData) {
            return array_merge($carry, [$attribute => $this->getAttributeValueToExport($attribute, $productData)]);
        }, []);
    }

    /**
     * @param string $attribute
     * @param string[] $row
     * @return string
     */
    private function getAttributeValueToExport($attribute, array $row)
    {
        if (isset($row[$attribute])) {
            return $row[$attribute];
        }

        if (in_array($attribute, $this->getRequiredAttributeCodes())) {
            Mage::throwException(sprintf('Attribute "%s" is not set. Product ID: %s', $attribute, $row['entity_id']));
        }

        return '';
    }

    /**
     * @param string $combinedCategoryIds
     * @return string[]
     */
    private function addCategoryUrlKeys($combinedCategoryIds)
    {
        $categoryIds = array_unique(explode(',', $combinedCategoryIds));
        return array_unique(array_reduce($categoryIds, function (array $carry, $categoryId) {
            return array_merge($carry, $this->getCategoryUrlPathsForId($categoryId));
        }, []));
    }

    /**
     * @param int|string $categoryId
     * @return string[]
     */
    private function getCategoryUrlPathsForId($categoryId)
    {
        $categoryUrlKeys = $this->getCategoryUrlKeysForId($categoryId);
        $suffix = Mage::getStoreConfig('catalog/seo/category_url_suffix', $this->getStoreId());
        return array_map(function ($urlKey) use ($suffix) {
            return $urlKey . $suffix;
        }, $categoryUrlKeys);
    }

    /**
     * @param int|string $categoryId
     * @return string[]
     */
    private function getCategoryUrlKeysForId($categoryId)
    {
        /** @var LizardsAndPumpkins_MagentoConnector_Helper_Factory $factory */
        $factory = Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
        $categoryUrlKeyService = $factory->createCategoryUrlKeyService();
        return $categoryUrlKeyService->getCategoryUrlKeysByIdAndStore($categoryId, $this->getStoreId());
    }

    /**
     * @param string $combinedCategoryIds
     * @param string $productUrlKey
     * @return string[]
     */
    private function addProductInCategoryUrlKeys($combinedCategoryIds, $productUrlKey)
    {
        $categoryIds = array_unique(explode(',', $combinedCategoryIds));
        return array_unique(array_reduce($categoryIds, function (array $carry, $categoryId) use ($productUrlKey) {
            $productInCategoryUrlKeys = array_map(function ($categoryUrlKey) use ($productUrlKey) {
                return $categoryUrlKey . '/' . $productUrlKey;
            }, $this->getCategoryUrlKeysForId($categoryId));
            return array_merge($carry, $productInCategoryUrlKeys);
        }, []));
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
            $coreResource = $this->getCoreResource();
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
        return 'eav/entity_attribute_source_table' === $this->getAttributeToSourceModelMap()[$attributeId] ?
            $this->loadOptionsForAttributeFromTable($attributeId) :
            $this->loadOptionsForAttributeFromSourceModel($attributeId);
    }

    /**
     * @param int $attributeId
     * @return string[]
     */
    private function loadOptionsForAttributeFromTable($attributeId)
    {
        static $tableOptions;
        $storeId = $this->getStoreId();
        if (null === $tableOptions || !isset($tableOptions[$storeId])) {
            /** @var Mage_Eav_Model_Resource_Entity_Attribute_Option_Collection $collection */
            $collection = Mage::getResourceModel('eav/entity_attribute_option_collection')
                ->setPositionOrder('asc')
                ->setStoreFilter($storeId);
            foreach ($collection->getData() as $row) {
                $tableOptions[$storeId][$row['attribute_id']][$row['option_id']] = $row['value'];
            }
        }
        return isset($tableOptions[$storeId][$attributeId]) ?
            $tableOptions[$storeId][$attributeId] :
            [];
    }

    /**
     * @param int $attributeId
     * @return mixed[]
     */
    private function loadOptionsForAttributeFromSourceModel($attributeId)
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
                if (!is_array($option['value'])) {
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
        $attribute = $this->getEavConfig()->getAttribute('catalog_product', $attributeId);
        $attribute->setStoreId($this->getStoreId());
        $sourceModel->setAttribute($attribute);
        return $sourceModel;
    }

    private function getAttributeToSourceModelMap()
    {
        static $attributeToSourceModelMap;
        if (null === $attributeToSourceModelMap) {
            $coreResource = $this->getCoreResource();
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
                $this->getEavConfig()->getEntityType('catalog_product')->getId());
            $select->where('frontend_input IN(?)', ['select', 'multiselect']);
            $attributeToSourceModelMap = $connection->fetchPairs($select);
        }
        return $attributeToSourceModelMap;
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
     */
    protected function _setItemAttributeValue($valueInfo)
    {
        $attributeId = $valueInfo['attribute_id'];
        $rawValue = $valueInfo['value'];
        $attributeCode = array_search($attributeId, $this->_selectAttributes);

        $values = $this->getValues($attributeId, $rawValue);

        if ('tax_class_id' === $attributeCode) {
            $this->_data[$valueInfo['entity_id']]['tax_class'] = $values;
            $this->_data[$valueInfo['entity_id']]['tax_class_id'] = $rawValue;
        } else {
            $this->_data[$valueInfo['entity_id']][$attributeCode] = $values;
        }

        return $this;
    }

    /**
     * @param int $attributeId
     * @param string|null $rawValue
     * @return string[]|string
     */
    private function getValues($attributeId, $rawValue)
    {
        if (null === $rawValue) {
            return '';
        }

        $options = array_values($this->getEavAttributeOptions($attributeId));

        if ([] === $options) {
            return $rawValue;
        }

        $values = explode(',', $rawValue);
        if (count($values) > 1) {
            return array_map(function ($optionId) use ($options) {
                return $this->getOptionValue($options, $optionId);
            }, $values);
        }

        return $this->getOptionValue($options, $rawValue);
    }

    /**
     * @param string[] $options
     * @param string $optionId
     * @return string
     */
    private function getOptionValue(array $options, $optionId)
    {
        return $options[$optionId] ?? $optionId;
    }

    private function getCoreResource(): Mage_Core_Model_Resource
    {
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        return $resource;
    }

    private function getEavConfig(): Mage_Eav_Model_Config
    {
        /** @var Mage_Eav_Model_Config $eavConfig */
        $eavConfig = Mage::getSingleton('eav/config');
        return $eavConfig;
    }

    /**
     * @return string[]
     */
    private function getRequiredAttributeCodes()
    {
        return ['sku', 'stock_qty', 'tax_class', 'tax_class_id', 'type_id', 'backorders'];
    }
}
