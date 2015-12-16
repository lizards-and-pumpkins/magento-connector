<?php

class LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector implements \IteratorAggregate
{
    /**
     * @var LizardsAndPumpkins_MagentoConnector_Helper_ProductsToUpdateQueueReader
     */
    private $productUpdatesQueueReader;

    /**
     * @var ArrayIterator
     */
    private $productIterator;

    /**
     * @var int[]
     */
    private $currentBatchOfProductIds;

    /**
     * @var Mage_Core_Model_Store[]
     */
    private $remainingStoresToExportForCurrentProductBatch;

    /**
     * @var Mage_Core_Model_Store
     */
    private $currentStoreForExport;

    /**
     * @var Mage_Core_Model_Store[]
     */
    private $limitExportToStores;

    public function __construct(LizardsAndPumpkins_MagentoConnector_Helper_ProductsToUpdateQueueReader $queueReader)
    {
        $this->productUpdatesQueueReader = $queueReader;
    }

    /**
     * @return Generator
     */
    public function getIterator()
    {
        while ($product = $this->getProduct()) {
            yield $product;
        }
    }

    /**
     * @return mixed[]
     */
    public function getProduct()
    {
        if ($this->existsNextProduct()) {
            return $this->productIterator->current();
        }

        if (empty($this->remainingStoresToExportForCurrentProductBatch)) {
            $this->remainingStoresToExportForCurrentProductBatch = $this->getStoresToExport();
            $this->currentBatchOfProductIds = $this->getQueuedProductIds();
        }

        $this->currentStoreForExport = array_pop($this->remainingStoresToExportForCurrentProductBatch);
        if (empty($this->currentBatchOfProductIds)) {
            return null;
        }

        $this->disableFlatCatalogForProductCollections();

        $products = $this->getProductDataForCurrentBatch();

        $this->productIterator = new \ArrayIterator($products);

        return $this->hasIteratorProductForCurrentStore() ?
            $this->productIterator->current() :
            $this->getProduct();
    }

    /**
     * @return array[]
     */
    private function getProductDataForCurrentBatch()
    {
        $products = $this->loadProductData();
        $associatedProductData = $this->loadAssociatedSimpleProductData();
        $mediaGalleryData = $this->loadMediaGalleryData();

        return $this->mergeAdditionalDataIntoProductsArray($products, $mediaGalleryData, $associatedProductData);
    }

    /**
     * @param array[] $products
     * @param array[] $mediaGalleryData
     * @param array[] $associatedProductData
     * @return array[]
     */
    private function mergeAdditionalDataIntoProductsArray(
        array $products,
        array $mediaGalleryData,
        array $associatedProductData
    ) {
        foreach ($products as $productId => $productData) {
            $products[$productId]['media_gallery'] = isset($mediaGalleryData[$productId]) ?
                $mediaGalleryData[$productId] :
                [];
            $products[$productId]['associated_products'] = isset($associatedProductData[$productId]) ?
                $associatedProductData[$productId] :
                [];
        }
        return $products;
    }

    /**
     * @return array[]
     */
    private function loadProductData()
    {
        // todo: eav attributes, category names, tax_class name
        $collection = $this->createCollection($this->currentStoreForExport);
        $collection->addIdFilter($this->currentBatchOfProductIds);
        $this->addCategoryIds($collection);
        $this->addStockItemData($collection);
        $this->addConfigurableAttributeCodes($collection);
        $productData = [];
        foreach ($collection->getData() as $row) {
            $productData[$row['entity_id']] = array_merge(
                $row,
                ['category_ids' => array_unique(explode(',', $row['category_ids']))],
                [
                    'configurable_attributes' => array_map(function ($configurableAttributeId) {
                        return $this->loadConfigurableAttributes()[$configurableAttributeId];
                    }, array_unique(explode(',', $row['configurable_attribute_ids']))),
                ]
            );
        }
        return $productData;
    }

    /**
     * @return bool
     */
    private function hasIteratorProductForCurrentStore()
    {
        return $this->productIterator->current() !== null;
    }

    /**
     * @return bool
     */
    private function existsNextProduct()
    {
        if ($this->productIterator) {
            $this->productIterator->next();
            return $this->productIterator->valid();
        }
        return false;
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    private function createCollection($store)
    {
        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getResourceModel('lizardsAndPumpkins_magentoconnector/catalog_product_collection');
        $collection->setStore($store);
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter(
            'visibility',
            ['neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE]
        );
        $collection->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $collection->addWebsiteFilter($store->getWebsiteId());
        return $collection;
    }

    /**
     * @return int[]
     */
    private function getQueuedProductIds()
    {
        return $this->productUpdatesQueueReader->getQueuedProductIds();
    }

    /**
     * @see  http://www.magentocommerce.com/boards/viewthread/17414/#t141830
     * @return array[]
     */
    private function loadMediaGalleryData()
    {
        $storeId = $this->currentStoreForExport->getId();
        $mediaGalleryAttributeId = Mage::getSingleton('eav/config')
            ->getAttribute('catalog_product', 'media_gallery')
            ->getAttributeId();
        /* @var $coreResource Mage_Core_Model_Resource */
        $coreResource = Mage::getSingleton('core/resource');
        /* @var $readConnection Varien_Db_Adapter_Interface */
        $readConnection = $coreResource->getConnection('default_read');
        $mediaGalleryTable = $coreResource->getTableName('catalog/product_attribute_media_gallery');
        $mediaGalleryValueTable = $coreResource->getTableName('catalog/product_attribute_media_gallery_value');

        // todo: use Zend_Db_Select instead of raw sql
        $query = <<<SQL
        SELECT
            main.entity_id, `main`.`value_id`, `main`.`value` AS `file`,
            `value`.`label`, `value`.`position`, `value`.`disabled`, `default_value`.`label` AS `label_default`,
            `default_value`.`position` AS `position_default`,
            `default_value`.`disabled` AS `disabled_default`
        FROM `$mediaGalleryTable` AS `main`
            LEFT JOIN `$mediaGalleryValueTable` AS `value`
                ON main.value_id=value.value_id AND value.store_id=' . $storeId . '
            LEFT JOIN `$mediaGalleryValueTable` AS `default_value`
                ON main.value_id=default_value.value_id AND default_value.store_id=0
        WHERE (
            main.attribute_id = {$readConnection->quote($mediaGalleryAttributeId)}
            )
            AND (main.entity_id IN ({$readConnection->quote($this->currentBatchOfProductIds)} ))
        ORDER BY IF(value.position IS NULL, default_value.position, value.position) ASC
SQL;
        $mediaGalleryData = [];
        foreach ($readConnection->fetchAll($query) as $row) {
            $mediaGalleryData[$row['entity_id']]['images'][] = $row;
        }
        return $mediaGalleryData;
    }

    /**
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     */
    private function addCategoryIds(Mage_Catalog_Model_Resource_Product_Collection $collection)
    {
        $table = Mage::getSingleton('core/resource')->getTableName('catalog/category_product');
        $select = $collection->getSelect();
        $columnValue = new Zend_Db_Expr("GROUP_CONCAT(category_id SEPARATOR ',')");
        $select->joinLeft(
            ['categories' => $table],
            'e.entity_id=categories.product_id',
            ['category_ids' => $columnValue]
        );
        $this->groupSelectBy($collection->getSelect(), 'e.entity_id');
    }

    private function addConfigurableAttributeCodes(Mage_Catalog_Model_Resource_Product_Collection $collection)
    {
        $collection->getSelect()->joinLeft(
            ['configurable_attribute' => $collection->getResource()->getTable('catalog/product_super_attribute')],
            "e.entity_id=configurable_attribute.product_id",
            ['configurable_attribute_ids' => new Zend_Db_Expr("GROUP_CONCAT(configurable_attribute.attribute_id SEPARATOR ',')")]
        );
        $this->groupSelectBy($collection->getSelect(), 'e.entity_id');
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
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     */
    private function addStockItemData(Mage_Catalog_Model_Resource_Product_Collection $collection)
    {
        $defaultBackOrders = Mage::getStoreConfig('cataloginventory/item_options/backorders') ? 'true' : 'false';

        $table = Mage::getSingleton('core/resource')->getTableName('cataloginventory/stock_item');

        $stockItemBackordersIf = new Zend_Db_Expr(
            "IF(stock_item.backorders > 0, 'true', 'false')"
        );
        $configBackordersIf = new Zend_Db_Expr(
            "IF(use_config_backorders > 0, '{$defaultBackOrders}', {$stockItemBackordersIf})"
        );

        $select = $collection->getSelect();
        $select->join(
            ['stock_item' => $table],
            'e.entity_id=stock_item.product_id',
            ['stock_qty' => 'qty', 'backorders' => $configBackordersIf]
        );
    }

    private function loadAssociatedSimpleProductData()
    {
        $coreResource = Mage::getSingleton('core/resource');
        $connection = $coreResource->getConnection('default_read');

        /** @var Mage_Catalog_Model_Resource_Product_Collection $simpleProducts */
        $simpleProducts = Mage::getResourceModel('catalog/product_collection');
        $simpleProducts->addAttributeToSelect('sku');
        $simpleProducts->addAttributeToFilter('type_id', Mage_Catalog_Model_Product_Type::TYPE_SIMPLE);
        $simpleProducts->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $this->addStockItemData($simpleProducts);

        $configurableAttributes = $this->loadConfigurableAttributes();
        $this->joinAttributeTables($simpleProducts, $configurableAttributes);

        $select = $simpleProducts->getSelect();
        $select->joinInner(
            ['link' => $coreResource->getTableName('catalog/product_super_link')],
            "e.entity_id=link.product_id AND link.parent_id IN ({$connection->quote($this->currentBatchOfProductIds)})",
            ['parent_id' => 'link.parent_id']
        );

        $simpleProductData = [];
        foreach ($connection->fetchAll($select) as $row) {
            foreach ($configurableAttributes as $attributeCode) {
                $row[$attributeCode] = $this->loadConfigurableAttributeOptions()[$row[$attributeCode]];
            }
            $simpleProductData[$row['parent_id']][] = $row;
        }
        return $simpleProductData;
    }

    /**
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     * @param string[] $attributes
     * @throws Mage_Core_Exception
     */
    private function joinAttributeTables(Mage_Catalog_Model_Resource_Product_Collection $collection, $attributes)
    {
        foreach ($attributes as $attributeId => $attributeCode) {
            $attributeModel = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $attributeCode);
            $tableAlias = $attributeCode . '_table';
            $collection->getSelect()->join(
                [$tableAlias => $attributeModel->getBackend()->getTable()],
                "{$tableAlias}.entity_id=e.entity_id AND {$tableAlias}.attribute_id={$attributeId}",
                [$attributeCode => "{$tableAlias}.value"]
            );
        }
    }

    /**
     * @return array
     */
    private function getStoresToExport()
    {
        if (!$this->limitExportToStores) {
            return Mage::app()->getStores();
        }
        return $this->limitExportToStores;
    }

    /**
     * @param Mage_Core_Model_Store[] $stores
     */
    public function setStoresToExport(array $stores)
    {
        $this->limitExportToStores = $stores;
    }

    /**
     * @return string[]
     */
    private function loadConfigurableAttributes()
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
     * @return string[]
     */
    private function loadConfigurableAttributeOptions()
    {
        static $configurableAttributeOptions;
        $storeId = $this->currentStoreForExport->getId();
        if (null === $configurableAttributeOptions || ! isset($configurableAttributeOptions[$storeId])) {
            $coreResource = Mage::getSingleton('core/resource');
            $connection = $coreResource->getConnection('default_read');
            $optionTable = $coreResource->getTableName('eav/attribute_option');
            $optionValueTable = $coreResource->getTableName('eav/attribute_option_value');
            
            $columns = ['o.option_id', 'label' => new Zend_Db_Expr('IFNULL(ovs.value, ovd.value)')];
            $select = $connection->select()->from(['o' => $optionTable], $columns);
            $select->joinInner(
                ['ovd' => $optionValueTable],
                "o.option_id=ovd.option_id AND ovd.store_id=0",
                []
            );
            $select->joinLeft(
                ['ovs' => $optionValueTable],
                $connection->quoteInto("o.option_id=ovd.option_id AND ovd.store_id=?", $storeId),
                []
            );
            $select->where('o.attribute_id IN (?)', array_keys($this->loadConfigurableAttributes()));

            $configurableAttributeOptions[$storeId] = $connection->fetchPairs($select);
        }
        return $configurableAttributeOptions[$storeId];
    }

    private function disableFlatCatalogForProductCollections()
    {
        Mage::app()->setCurrentStore('admin');
    }
}
