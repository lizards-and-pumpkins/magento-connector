<?php

class LizardsAndPumpkins_MagentoConnector_Model_Catalog_CategoryUrlKeyService
{
    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Category_Collection
     */
    private $categories;

    /**
     * @param LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Category_Collection $categories
     */
    public function __construct(
        LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Category_Collection $categories
    ) {
        $this->categories = $categories;
    }

    /**
     * @param int|string $categoryId
     * @param int|string|Mage_Core_Model_Store $store
     * @return string[]
     */
    public function getCategoryUrlKeysByIdAndStore($categoryId, $store)
    {
        $intId = (int) $categoryId;
        if (0 === $intId) {
            $message = sprintf('The category ID has to be an integer, got "%s"', $this->getType($categoryId));
            throw new LizardsAndPumpkins_MagentoConnector_Model_Catalog_Exception_InvalidCategoryIdException($message);
        }
        $categoriesData = $this->categories->getDataForStore($store);
        if (!isset($categoriesData[$intId])) {
            return [];
        }
        $parentIds = $this->findLayeredNavigationParentIds($intId, $categoriesData);
        return $this->buildUrlKeysFor(array_merge($parentIds, [$categoryId]), $categoriesData);
    }

    /**
     * @param int $categoryId
     * @param array[] $allCategories
     * @return int[]
     */
    private function findLayeredNavigationParentIds($categoryId, array $allCategories)
    {
        return array_reduce($allCategories[$categoryId]['parent_ids'],
            function ($carry, $parentId) use ($allCategories) {
                return $allCategories[$parentId]['is_anchor'] ?
                    array_merge($carry, [$parentId]) :
                    $carry;
            }, []);
    }

    /**
     * @param int[] $categoryIds
     * @param array[] $categoriesData
     * @return string[]
     */
    private function buildUrlKeysFor(array $categoryIds, array $categoriesData)
    {
        return array_map(function ($categoryId) use ($categoriesData) {
            if (! isset($categoriesData[$categoryId]['_url_path'])) {
                $categoriesData[$categoryId]['_url_path'] = $this->buildCategoryUrlKey($categoryId, $categoriesData);
            }
            return $categoriesData[$categoryId]['_url_path'];
        }, $categoryIds);
    }

    /**
     * @param int $categoryId
     * @param array[] $categoriesData
     * @param string[] $urlKeys
     * @return string
     */
    private function buildCategoryUrlKey($categoryId, array $categoriesData, array $urlKeys = [])
    {
        return [] === $categoriesData[$categoryId]['parent_ids'] ?
            implode('/', $this->prependUrlKey($urlKeys, $categoriesData[$categoryId])) :
            $this->buildCategoryUrlKey(
                end($categoriesData[$categoryId]['parent_ids']),
                $categoriesData,
                $this->prependUrlKey($urlKeys, $categoriesData[$categoryId])
            );
    }

    /**
     * @param string[] $urlKeys
     * @param mixed[] $categoryData
     * @return string[]
     */
    private function prependUrlKey(array $urlKeys, array $categoryData)
    {
        return array_merge([$categoryData['url_key']], $urlKeys);
    }

    /**
     * @param mixed $var
     * @return string mixed
     */
    private function getType($var)
    {
        return is_object($var) ?
            get_class($var) :
            gettype($var);
    }
}
