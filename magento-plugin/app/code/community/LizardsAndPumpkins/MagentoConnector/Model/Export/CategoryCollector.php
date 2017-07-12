<?php

/**
 * @deprecated 
 * @see \LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_CategoryDataCollectionFactory
 */
class LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryCollector implements IteratorAggregate
{
    /**
     * @var Mage_Catalog_Model_Resource_Category_Collection
     */
    private $collection;

    /**
     * @var ArrayIterator
     */
    private $categoryIterator;

    /**
     * @var Mage_Core_Model_Store[]
     */
    private $storesToExportTemplate;

    /**
     * @var Mage_Core_Model_Store[]
     */
    private $storesToExportInCurrentLoop;

    /**
     * @var Mage_Core_Model_Store
     */
    private $store;

    /**
     * @var int[]
     */
    private $categoriesToExport;

    /**
     * @var string
     */
    private $categoryUrlSuffix;

    /**
     * @var int[]
     */
    private $categoryIdsToExport;

    /**
     * @param int[] $categoryIdsToExport
     * @param string $categoryUrlSuffix
     */
    final public function __construct(array $categoryIdsToExport, $categoryUrlSuffix)
    {
        if ($categoryUrlSuffix[0] !== '.') {
            $categoryUrlSuffix = '.' . $categoryUrlSuffix;
        }
        $this->categoryUrlSuffix = $categoryUrlSuffix;
        $this->categoryIdsToExport = $categoryIdsToExport;
    }

    /**
     * @return Mage_Catalog_Model_Category
     */
    public function getCategory()
    {
        if ($this->existsNextCategory()) {
            return $this->categoryIterator->current();
        }

        $this->prepareNextBunchOfCategories();

        $this->setNextStoreToExport();
        if (! $this->isCategoryLeftForExport()) {
            return null;
        }

        $this->createCollectionWithIdFilter();
        $this->addAdditionalData();
        $this->categoryIterator = $this->collection->getIterator();
        if ($this->categoryIterator->current() === null) {
            return $this->getCategory();
        }

        return $this->categoryIterator->current();
    }

    private function addAdditionalData()
    {
        $this->addStoreToCollection();
        $this->addUrlPath();
    }

    private function addUrlPath()
    {
        /** @var $category Mage_Catalog_Model_Category */
        foreach ($this->collection as $category) {
            try {
                $ancestorIds = explode('/', $category->getData('path'));
                unset($ancestorIds[0], $ancestorIds[1]);
                $categoryIdsReplacedWithUrlKeys = array_map(function ($categoryId) {
                    $category = $this->collection->getItemById($categoryId);
                    if (! $category) {
                        throw new RuntimeException('Category or parent is disabled. Removing from collection.', 404);
                    }

                    return $category->getData('url_key');
                }, $ancestorIds);

                $urlPath = array_filter($categoryIdsReplacedWithUrlKeys);
                $category->setData('url_path', implode('/', $urlPath) . $this->categoryUrlSuffix);
            } catch (RuntimeException $e) {
                if ($e->getCode() === 404) {
                    unset($category);
                    continue;
                }
                throw $e;
            }
        }
    }

    private function addStoreToCollection()
    {
        /** @var $category Mage_Catalog_Model_Category */
        foreach ($this->collection as $category) {
            $category->setData('store_id', $this->store->getId());
        }
    }

    /**
     * @return bool
     */
    private function existsNextCategory()
    {
        if ($this->categoryIterator) {
            $this->categoryIterator->next();

            return $this->categoryIterator->valid();
        }

        return false;
    }

    private function prepareNextBunchOfCategories()
    {
        if (empty($this->storesToExportInCurrentLoop)) {
            $this->storesToExportInCurrentLoop = $this->getStoresToExport();
            $this->categoriesToExport = $this->categoryIdsToExport;
        }
    }

    /**
     * @return Mage_Core_Model_Store[]
     */
    private function getStoresToExport()
    {
        if (! $this->storesToExportTemplate) {
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

    /**
     * @return Mage_Catalog_Model_Resource_Category_Collection
     */
    private function createCollection()
    {
        /** @var $collection Mage_Catalog_Model_Resource_Category_Collection */
        $collection = Mage::getResourceModel('catalog/category_collection');
        $collection->setStoreId($this->store);
        $collection->setStore($this->store);
        $collection->addAttributeToSelect('*');
        $collection->addFieldToFilter('path', ['like' => "1/{$this->store->getRootCategoryId()}/%"]);
        $collection->addAttributeToFilter('is_active', 1);
        $collection->addAttributeToFilter('level', ['gt' => 1]);

        return $collection;
    }

    private function setNextStoreToExport()
    {
        $this->store = array_pop($this->storesToExportInCurrentLoop);
    }

    private function createCollectionWithIdFilter()
    {
        $this->collection = $this->createCollection();
        $this->collection->addIdFilter($this->categoriesToExport);
    }

    /**
     * @return bool
     */
    private function isCategoryLeftForExport()
    {
        return ! empty($this->categoriesToExport);
    }

    public function getIterator()
    {
        while ($category = $this->getCategory()) {
            yield $category;
        }
    }
}
