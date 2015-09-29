<?php

class Brera_MagentoConnector_Model_Export_ProductCollector
{
    private $queuedProductUpdates;

    /**
     * @param Mage_Core_Model_Store $store
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function getAllQueuedProductUpdates(Mage_Core_Model_Store $store)
    {
        $queuedProductUpdates = $this->getQueuedProductUpdates();

        $filter = array();
        if (!empty($queuedProductUpdates['skus'])) {
            $filter[] = array('attribute' => 'sku',
                              'in'        => $queuedProductUpdates['skus']);
        }
        if (!empty($queuedProductUpdates['ids'])) {
            $filter[] = array('attribute' => 'entity_id',
                              'in'        => $queuedProductUpdates['ids']);
        }

        if (empty($filter)) {
            Mage::throwException('No queued updates to export.');
        }

        return $this->getAllProductsCollection($store)
            ->addAttributeToFilter($filter);
    }

    /**
     * @param Mage_Core_Model_Store $store
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function getAllProductsCollection($store)
    {
        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->setStore($store);
        $collection->addAttributeToSelect('*');

        return $collection;
    }

    public function getAllProductStockUpdates($store)
    {
        $stockUpdateAction
            = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_STOCK_UPDATE;

        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->setStore($store);
        $collection->joinTable(
            'brera_magentoconnector/product_queue',
            'entity_id=product_id',
            '',
            'action=' . $stockUpdateAction
        )
            ->addAttributeToSelect('*');

        return $collection;
    }

    /**
     * @return string[][]
     */
    public function getQueuedProductUpdates()
    {
        if (!$this->queuedProductUpdates) {
            $collection = Mage::getResourceModel(
                'brera_magentoconnector/product_queue_item_collection'
            )
                ->addFieldToFilter(
                    'action',
                    Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE
                );

            $queuedProductUpdates = array('skus' => array(), 'ids' => array());
            /** @var $item Brera_MagentoConnector_Model_Product_Queue_Item */
            foreach ($collection as $item) {
                if ($item->getId()) {
                    $queuedProductUpdates['ids'][] = $item->getProductId();
                }
                if ($item->getSku()) {
                    $queuedProductUpdates['skus'][] = $item->getSku();
                }
            }

            $this->queuedProductUpdates = $queuedProductUpdates;
        }

        return $this->queuedProductUpdates;
    }


    /**
     * add media gallery images to collection
     *
     * @param Mage_Catalog_Model_Resource_Product_Collection $productCollection
     * @param Mage_Core_Model_Store                          $store
     *
     * @see  http://www.magentocommerce.com/boards/viewthread/17414/#t141830
     */
    private function addMediaGalleryAttributeToCollection(
        Mage_Catalog_Model_Resource_Product_Collection $productCollection,
        Mage_Core_Model_Store $store
    ) {
        if ($productCollection->count() == 0) {
            return;
        }
        $storeId = $store->getId();
        $mediaGalleryAttributeId = Mage::getSingleton('eav/config')
            ->getAttribute('catalog_product', 'media_gallery')
            ->getAttributeId();
        /* @var $coreResource Mage_Core_Model_Resource */
        $coreResource = Mage::getSingleton('core/resource');
        /* @var $readConnection Varien_Db_Adapter_Interface */
        $readConnection = $coreResource->getConnection('catalog_read');
        $mediaGalleryTable = $coreResource->getTableName(
            'catalog/product_attribute_media_gallery'
        );
        $mediaGalleryValueTable = $coreResource->getTableName(
            'catalog/product_attribute_media_gallery_value'
        );

        $productIds = $productCollection->getLoadedIds();
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

        $mediaGalleryByProductId = array();
        foreach ($mediaGalleryData as $galleryImage) {
            $k = $galleryImage['entity_id'];
            unset($galleryImage['entity_id']);
            if (!isset($mediaGalleryByProductId[$k])) {
                $mediaGalleryByProductId[$k] = array();
            }
            $mediaGalleryByProductId[$k][] = $galleryImage;
        }
        unset($mediaGalleryData);

        foreach ($productCollection as $product) {
            $productId = $product->getData('entity_id');
            if (isset($mediaGalleryByProductId[$productId])) {
                $product->setData(
                    'media_gallery',
                    array('images' => $mediaGalleryByProductId[$productId])
                );
            }
        }
        unset($mediaGalleryByProductId);
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function addStockItemsCategoriesAndMediaGallery(
        Mage_Catalog_Model_Resource_Product_Collection $collection,
        Mage_Core_Model_Store $store
    ) {
        $this->addCategories($collection, $store);
        $this->addStockInformation($collection);
        $this->addMediaGalleryAttributeToCollection($collection, $store);
        $helper = Mage::helper('brera_magentoconnector/productCollection');
        $helper->setProductCollection($collection);

        return $collection;
    }

    private function addCategories(
        Mage_Catalog_Model_Resource_Product_Collection $collection,
        Mage_Core_Model_Store $store
    ) {
        $categoryIds = array();
        $collection->addCategoryIds();
        foreach ($collection as $product) {
            $categoryIds += array_flip($product->getCategoryIds());
        }

        $categoryIds = array_keys($categoryIds);

        /** @var $categoryCollection Mage_Catalog_Model_Resource_Category_Collection */
        $categoryCollection = Mage::getResourceModel(
            'catalog/category_collection'
        )
            ->setStore($store)
            ->addAttributeToSelect('url_key');
        $categoryCollection->addIdFilter($categoryIds);

        foreach ($collection as $product) {
            $categories = array();
            foreach ($product->getCategoryIds() as $categoryId) {
                $categories[] = $categoryCollection->getItemById($categoryId)
                    ->getUrlKey();
            }
            $product->setCategories($categories);
        }
    }


    /**
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     */
    private function addStockInformation(Mage_Catalog_Model_Resource_Product_Collection $collection
    ) {
        Mage::getSingleton('cataloginventory/stock')
            ->addItemsToProducts($collection);

        foreach ($collection as $product) {
            $stockItem = $product->getStockItem();
            $product->setStockQty($stockItem->getQty());
            $product->setBackorders(
                $stockItem->getBackorders() ? 'true' : 'false'
            );
        }
    }
}
