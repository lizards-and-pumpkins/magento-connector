<?php

class LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector
{
    /**
     * @var int[]
     */
    private $queuedProductIds;

    /**
     * @var Mage_Core_Model_Store
     */
    private $store;

    /**
     * @var Mage_Catalog_Model_Resource_Product_Collection
     */
    private $collection;

    /**
     * @var ArrayIterator
     */
    private $productIterator;

    /**
     * @var Zend_Queue_Message_Iterator
     */
    private $messageIterator;

    /**
     * @var Mage_Core_Model_Store[]
     */
    private $storesToExport;

    /**
     * @var Mage_Core_Model_Store[]
     */
    private $storesToExportTemplate;

    /**
     * @var Mage_Catalog_Model_Product[]
     */
    private $simpleProducts;

    /**
     * @var Mage_Catalog_Model_Product[][]
     */
    private $associatedSimpleProducts;

    /**
     * @var int[]
     */
    private $configurableProductIds;

    /**
     * @var string[]
     */
    private $configurableAttributeCodes;

    /**
     * @var array
     */
    private $configurableProductAttributes;

    /**
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {

        if ($this->existsNextProduct()) {
            return $this->productIterator->current();
        }

        $this->prepareNextBunchOfProducts();

        $this->store = array_pop($this->storesToExport);
        if (empty($this->queuedProductIds)) {
            return null;
        }

        $this->collection = $this->createCollection($this->store);
        $this->collection->addIdFilter($this->queuedProductIds);

        $this->addAdditionalData();

        $this->productIterator = $this->collection->getIterator();
        if ($this->productIterator->current() === null) {
            return $this->getProduct();
        }
        return $this->productIterator->current();
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
        $this->setAdminStoreToAvoidFlatTables();
        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection');
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
        /** @var LizardsAndPumpkins_MagentoConnector_Helper_Export $helper */
        $helper = Mage::helper('lizardsAndPumpkins_magentoconnector/export')->getProductUpdatesToExport();
        $this->messageIterator = $helper->getProductUpdatesToExport();
        $productIds = [];
        foreach ($this->messageIterator as $item) {
            /** @var $item Zend_Queue_Message */
            $productIds[] = $item->body;
        }
        if ($productIds) {
            $this->deleteMessages();
        }

        return $productIds;
    }

    private function deleteMessages()
    {
        $ids = [];
        foreach ($this->messageIterator as $message) {
            $ids[] = (int) $message->message_id;
        }

        $ids = implode(',', $ids);
        /** @var Mage_Core_Model_Resource $resouce */
        $resouce = Mage::getSingleton('core/resource');
        $resouce->getConnection('core_write')->delete('message', "message_id IN ($ids)");
    }

    private function prepareNextBunchOfProducts()
    {
        if (empty($this->storesToExport)) {
            $this->storesToExport = $this->getStoresToExport();
            $this->queuedProductIds = $this->getQueuedProductIds();
        }
    }

    private function setAdminStoreToAvoidFlatTables()
    {
        Mage::app()->setCurrentStore(Mage::app()->getStore(Mage_Core_Model_App::ADMIN_STORE_ID));
    }

    public function addAdditionalData()
    {
        $this->addStore();
        $this->addCategories();
        $this->addStockInformation($this->collection);
        $this->addMediaGalleryAttributeToCollection();
        $this->addAssociatedProductsToConfigurables();
    }

    private function addStore()
    {
        $this->collection->setDataToAll('store_id', $this->store->getId());
    }

    /**
     * @see  http://www.magentocommerce.com/boards/viewthread/17414/#t141830
     */
    private function addMediaGalleryAttributeToCollection()
    {
        if ($this->collection->count() == 0) {
            return;
        }
        $storeId = $this->store->getId();
        $mediaGalleryAttributeId = Mage::getSingleton('eav/config')
            ->getAttribute('catalog_product', 'media_gallery')
            ->getAttributeId();
        /* @var $coreResource Mage_Core_Model_Resource */
        $coreResource = Mage::getSingleton('core/resource');
        /* @var $readConnection Varien_Db_Adapter_Interface */
        $readConnection = $coreResource->getConnection('catalog_read');
        $mediaGalleryTable = $coreResource->getTableName('catalog/product_attribute_media_gallery');
        $mediaGalleryValueTable = $coreResource->getTableName('catalog/product_attribute_media_gallery_value');

        $productIds = $this->collection->getLoadedIds();
        $query
            = <<<SQL
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
            main.attribute_id =  {$readConnection->quote(
            $mediaGalleryAttributeId
        )} )
            AND (main.entity_id IN ({$readConnection->quote($productIds)} ))
        ORDER BY IF(value.position IS NULL, default_value.position, value.position) ASC
SQL;

        $mediaGalleryData = $readConnection->fetchAll($query);

        $mediaGalleryByProductId = [];
        foreach ($mediaGalleryData as $galleryImage) {
            $k = $galleryImage['entity_id'];
            unset($galleryImage['entity_id']);
            if (!isset($mediaGalleryByProductId[$k])) {
                $mediaGalleryByProductId[$k] = [];
            }
            $mediaGalleryByProductId[$k][] = $galleryImage;
        }
        unset($mediaGalleryData);

        foreach ($this->collection as $product) {
            $productId = $product->getData('entity_id');
            if (isset($mediaGalleryByProductId[$productId])) {
                $product->setData('media_gallery', ['images' => $mediaGalleryByProductId[$productId]]);
            }
        }
        unset($mediaGalleryByProductId);
    }

    private function addCategories()
    {
        $categoryIds = [];
        $this->collection->addCategoryIds();
        foreach ($this->collection as $product) {
            $categoryIds += array_flip($product->getCategoryIds());
        }

        $categoryIds = array_keys($categoryIds);

        /** @var $categoryCollection Mage_Catalog_Model_Resource_Category_Collection */
        $categoryCollection = Mage::getResourceModel('catalog/category_collection')
            ->setStore($this->store)
            ->addAttributeToSelect('url_path');
        $categoryCollection->addIdFilter($categoryIds);

        foreach ($this->collection as $product) {
            $categories = [];
            foreach ($product->getCategoryIds() as $categoryId) {
                /** @var $category Mage_Catalog_Model_Category */
                $category = $categoryCollection->getItemById($categoryId);
                $categories[] = $category->getUrlPath();
            }
            $product->setCategories($categories);
        }
    }

    /**
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     */
    private function addStockInformation($collection)
    {
        Mage::getSingleton('cataloginventory/stock')
            ->addItemsToProducts($collection);

        foreach ($collection as $product) {
            $stockItem = $product->getStockItem();
            $product->setStockQty($stockItem->getQty());
            $product->setBackorders($stockItem->getBackorders() ? 'true' : 'false');
        }
    }

    private function addAssociatedProductsToConfigurables()
    {
        $this->simpleProducts = [];
        $this->associatedSimpleProducts = [];
        $this->configurableProductIds = [];
        $this->configurableAttributeCodes = [];
        $this->configurableProductAttributes = [];

        $associatedProducts = $this->getAssociatedSimpleProducts();
        $configurableAttributes = $this->getConfigurableAttributeCodes();
        foreach ($this->collection as $product) {
            $productAttributeCodes = [];
            if ($product->getTypeId() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
                $configurableProductAttributes = explode(',', $this->configurableProductAttributes[$product->getId()]);
                foreach ($configurableProductAttributes as $attributeId) {
                    $productAttributeCodes[] = $configurableAttributes[$attributeId];
                }
                /* @var $product Mage_Catalog_Model_Product */
                $product->setConfigurableAttributes($productAttributeCodes);
                $product->setSimpleProducts($associatedProducts[$product->getId()]);
            }
        }
    }

    /**
     * Return all associated simple products for the configurable products in
     * the current product collection.
     * Array key is the configurable product
     *
     * @return array
     */
    private function getSimpleProducts()
    {
        if (!$this->simpleProducts) {
            $parentIds = $this->getConfigurableProductIds();
            /** @var Mage_Catalog_Model_Resource_Product_Type_Configurable_Product_Collection $simpleProductCollection */
            $simpleProductCollection = Mage::getResourceModel('catalog/product_type_configurable_product_collection');
            $simpleProductCollection->addAttributeToSelect(['parent_id', 'tax_class_id']);
            $simpleProductCollection->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
            $simpleProductCollection->addWebsiteFilter(
                Mage::app()->getStore($this->collection->getStoreId())->getWebsiteId()
            );
            $simpleProductCollection->getSelect()->where(
                'link_table.parent_id IN(?)', $parentIds
            );
            $simpleProductCollection->getSelect()->columns(
                new Zend_Db_Expr("GROUP_CONCAT(parent_id SEPARATOR ',') AS parent_ids")
            );
            $simpleProductCollection->groupByAttribute('entity_id');

            $attributeCodes = $this->getConfigurableAttributeCodes();
            $simpleProductCollection->addAttributeToSelect($attributeCodes);
            $this->addStockInformation($simpleProductCollection);

            foreach ($simpleProductCollection->getItems() as $product) {
                foreach (explode(',', $product->getParentIds()) as $parentId) {
                    /* @var $row Varien_Object */
                    $row = $product->getData();
                    $simpleId = $row['entity_id'];
                    $this->simpleProducts[$simpleId] = $row;
                    $this->associatedSimpleProducts[$parentId][] = $product;
                }
            }
        }

        return $this->simpleProducts;
    }

    /**
     * @return array[]
     */
    private function getAssociatedSimpleProducts()
    {
        $this->getSimpleProducts();
        return $this->associatedSimpleProducts;
    }

    /**
     * @return int[]
     */
    private function getConfigurableProductIds()
    {
        if (!$this->configurableProductIds) {
            $this->configurableProductIds = [];
            foreach ($this->collection as $product) {
                /* @var $product Mage_Catalog_Model_Product */
                if ($product->getTypeId() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
                    $this->configurableProductIds[] = $product->getId();
                }
            }
        }

        return $this->configurableProductIds;
    }

    /**
     * Return array of all configurable attributes in the current collection.
     * Array indexes are the attribute ids, array values the attribute code
     *
     * @return array
     */
    private function getConfigurableAttributeCodes()
    {
        if (!$this->configurableAttributeCodes) {
            // build list of all configurable attribute codes for the current collection
            $this->configurableAttributeCodes = [];
            foreach ($this->getConfigurableProductAttributes() as $attributes) {
                $attributes = explode(',', $attributes);
                foreach ($attributes as $attributeId) {
                    if ($attributeId && !isset($this->configurableAttributeCodes[$attributeId])) {
                        $attributeModel = Mage::getSingleton('eav/config')
                            ->getAttribute('catalog_product', $attributeId);
                        $this->configurableAttributeCodes[$attributeId] = $attributeModel->getAttributeCode();
                    }
                }
            }
        }

        return $this->configurableAttributeCodes;
    }

    /**
     * Load all configurable attributes used in the current product collection
     *
     * @return string[]
     */
    private function getConfigurableProductAttributes()
    {
        if (!$this->configurableProductAttributes) {
            $productIds = $this->getConfigurableProductIds();
            $attributes = $this->getConfigurableAttributesForProductsFromResource($productIds);
            $this->configurableProductAttributes = $attributes;
        }

        return $this->configurableProductAttributes;
    }

    /**
     * This method actually would belong into a resource model, but for easier
     * reference I dropped it into the helper here.
     *
     * @param int[] $productIds
     * @return string[]
     */
    private function getConfigurableAttributesForProductsFromResource(array $productIds)
    {
        /** @var Mage_Core_Model_Resource_Helper_Mysql4 $resourceHelper */
        $resourceHelper = Mage::getResourceHelper('core');
        $resource = Mage::getSingleton('core/resource');
        $adapter = $resource->getConnection('catalog_read');
        $select = $adapter->select()
            ->from(
                $resource->getTableName('catalog/product_super_attribute'),
                ['product_id']
            )
            ->group('product_id')
            ->where('product_id IN(?)', $productIds);
        $resourceHelper->addGroupConcatColumn($select, 'attribute_ids', 'attribute_id');
        $attributes = $adapter->fetchPairs($select);

        return $attributes;
    }

    /**
     * @return array
     */
    private function getStoresToExport()
    {
        if (!$this->storesToExportTemplate) {
            return Mage::app()->getStores();
        }
        return $this->storesToExportTemplate;
    }

    /**
     * @param Mage_Core_Model_Store[] $stores
     */
    public function setStoresToExport(array $stores)
    {
        $this->storesToExportTemplate = $stores;
    }
}
