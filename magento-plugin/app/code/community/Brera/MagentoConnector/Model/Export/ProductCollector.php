<?php

class Brera_MagentoConnector_Model_Export_ProductCollector
{
    /**
     * @param Mage_Core_Model_Store $store
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function getAllQueuedProductUpdates($store)
    {
        $productUpdateAction = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_STOCK_UPDATE;
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->setStore($store);
        $collection->joinTable(
            'brera_magentoconnector/product_queue',
            'entity_id=product_id',
            '',
            'action=' . $productUpdateAction
        )
            ->addAttributeToSelect('*');

        Mage::getSingleton('cataloginventory/stock')
            ->addItemsToProducts($collection);

        return $collection;
    }

    public function getAllProducts($store)
    {
        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->setStore($store);
        $collection->addAttributeToSelect('*');

        Mage::getSingleton('cataloginventory/stock')
            ->addItemsToProducts($collection);

        return $collection;
    }

    public function getAllProductStockUpdates($store)
    {
        $stockUpdateAction = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_STOCK_UPDATE;

        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->setStore($store);
        $collection->joinTable(
            'brera_magentoconnector/product_queue',
            'entity_id=product_id',
            '',
            'action=' . $stockUpdateAction
        )
            ->addAttributeToSelect('*');

        Mage::getSingleton('cataloginventory/stock')
            ->addItemsToProducts($collection);

        return $collection;

    }
}
