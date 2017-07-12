<?php

class LizardsAndPumpkins_MagentoConnector_Model_Resource_CatalogExport_CatalogEntityIdCollector
{
    /**
     * @var Mage_Core_Model_Resource
     */
    private $resource;

    /**
     * @var Mage_Core_Model_App
     */
    private $app;

    /**
     * @param Mage_Core_Model_Resource $resource
     * @param Mage_Core_Model_App $app
     */
    public function __construct($resource = null, Mage_Core_Model_App $app = null)
    {
        $this->resource = $resource ?: Mage::getSingleton('core/resource');
        $this->app = $app ?: Mage::app();
    }

    /**
     * @return int[]
     */
    public function getAllProductIds()
    {
        return $this->getProductIdsForWebsiteIds(array_keys($this->app->getWebsites()));
    }

    /**
     * @param string $websiteCode
     * @return int[]
     */
    public function getProductIdsForWebsite($websiteCode)
    {
        return $this->getProductIdsForWebsiteIds([$this->app->getWebsite($websiteCode)->getId()]);
    }

    /**
     * @param int[] $websiteIds
     * @return array
     */
    private function getProductIdsForWebsiteIds(array $websiteIds)
    {
        /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->addAttributeToFilter('status', ['in' => \Mage_Catalog_Model_Product_Status::STATUS_ENABLED]);
        $collection->addWebsiteFilter($websiteIds);

        return $this->fetchEntityIds($collection);
    }

    /**
     * @return int[]
     */
    public function getAllCategoryIds()
    {
        /** @var Mage_Catalog_Model_Resource_Category_Collection $collection */
        $collection = Mage::getResourceModel('catalog/category_collection');
        $collection->addFieldToFilter('level', ['gt' => 0]);

        return $this->fetchEntityIds($collection);
    }

    /**
     * @param string $websiteCode
     * @return int[]
     */
    public function getCategoryIdsForWebsite($websiteCode)
    {
        $website = $this->app->getWebsite($websiteCode);
        $rootCategoriesPaths = $this->getRootCategoriesPaths($website);

        /** @var Mage_Catalog_Model_Resource_Category_Collection $collection */
        $collection = Mage::getResourceModel('catalog/category_collection');
        $collection->addPathsFilter($rootCategoriesPaths);

        return $this->fetchEntityIds($collection);
    }

    /**
     * @param $website
     * @return string[]
     */
    private function getRootCategoriesPaths($website)
    {
        $rootCategories = $this->getRootCategories($website);

        return array_map(function (Mage_Catalog_Model_Category $category) {
            return $category->getData('path');
        }, $rootCategories);
    }

    /**
     * @param Mage_Core_Model_Website $website
     * @return Mage_Catalog_Model_Category[]
     */
    private function getRootCategories(Mage_Core_Model_Website $website)
    {
        /** @var Mage_Catalog_Model_Resource_Category_Collection rootCategories */
        $rootCategories = Mage::getResourceModel('catalog/category_collection');
        $rootCategories->addIdFilter($this->getRootCategoryIdsForWebsite($website));

        return $rootCategories->getItems();
    }

    /**
     * @param Mage_Core_Model_Website $website
     * @return int[]
     */
    private function getRootCategoryIdsForWebsite(Mage_Core_Model_Website $website)
    {
        return array_map(function (Mage_Core_Model_Store_Group $group) {
            return $group->getRootCategoryId();
        }, $website->getGroups());
    }

    /**
     * @param Mage_Catalog_Model_Resource_Collection_Abstract $collection
     * @return int[]
     */
    private function fetchEntityIds(Mage_Catalog_Model_Resource_Collection_Abstract $collection)
    {
        $select = $collection->getSelect();
        $select->reset(Varien_Db_Select::COLUMNS);
        $select->columns([$collection->getResource()->getIdFieldName()]);

        return $this->resource->getConnection('default_setup')->fetchCol($select);
    }
}
