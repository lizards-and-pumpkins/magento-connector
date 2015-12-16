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
        $products = $this->createProductCollectionForCurrentBatch();
        return $products->getData();
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection
     */
    private function createProductCollectionForCurrentBatch()
    {
        // todo: tax_class name
        $collection = $this->createCollection($this->currentStoreForExport);
        $collection->addIdFilter($this->currentBatchOfProductIds);
        return $collection;
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection
     */
    private function createCollection(Mage_Core_Model_Store $store)
    {
        /** @var $collection LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection */
        $collection = Mage::getResourceModel('lizardsAndPumpkins_magentoconnector/catalog_product_collection');
        $collection->setFlag(
            LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection::FLAG_LOAD_ASSOCIATED_PRODUCTS,
            true
        );
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
     * @return int[]
     */
    private function getQueuedProductIds()
    {
        return $this->productUpdatesQueueReader->getQueuedProductIds();
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
}
