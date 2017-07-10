<?php

class LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_CategoryDataCollectionFactory
    implements LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_CatalogDataCollectionFactory
{
    /**
     * @var string
     */
    private $categoryUrlSuffix;

    /**
     * @param string $categoryUrlSuffix
     */
    public function __construct($categoryUrlSuffix = null)
    {
        $this->categoryUrlSuffix = $categoryUrlSuffix ?: Mage::helper('lizardsAndPumpkins_magentoconnector/factory')
            ->getConfig()
            ->getCategoryUrlSuffix();
    }

    public function createCollection(Mage_Core_Model_Store $store, array $categoryIdsToExport)
    {
        /** @var $collection Mage_Catalog_Model_Resource_Category_Collection */
        $collection = Mage::getResourceModel('catalog/category_collection');
        $collection->setStoreId($store);
        $collection->setStore($store);
        $collection->addAttributeToSelect('*');
        $collection->addFieldToFilter('path', ['like' => "1/{$store->getRootCategoryId()}/%"]);
        $collection->addAttributeToFilter('is_active', 1);
        $collection->addAttributeToFilter('level', ['gt' => 1]);
        $collection->addIdFilter($categoryIdsToExport);

        foreach ($collection as $category) {
            $category->setData('store_id', $store->getId());
        }
        $this->addUrlPath($collection);
        
        return $collection;
    }

    private function addUrlPath(Mage_Catalog_Model_Resource_Category_Collection $collection)
    {
        /** @var $category Mage_Catalog_Model_Category */
        foreach ($collection as $category) {
            try {
                $ancestorIds = explode('/', $category->getData('path'));
                unset($ancestorIds[0], $ancestorIds[1]);
                $categoryIdsReplacedWithUrlKeys = array_map(function ($categoryId) use ($collection) {
                    $category = $collection->getItemById($categoryId);
                    if (! $category) {
                        throw new RuntimeException('Category or parent is disabled. Removing from collection.', 404);
                    }

                    return $category->getData('url_key');
                }, $ancestorIds);

                $urlPath = array_filter($categoryIdsReplacedWithUrlKeys);
                $category->setData('url_path', implode('/', $urlPath) . '.' .  $this->categoryUrlSuffix);
            } catch (RuntimeException $e) {
                if ($e->getCode() === 404) {
                    unset($category);
                    continue;
                }
                throw $e;
            }
        }
    }
}
