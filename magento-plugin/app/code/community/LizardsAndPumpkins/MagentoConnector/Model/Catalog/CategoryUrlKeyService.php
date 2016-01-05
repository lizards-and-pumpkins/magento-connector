<?php

use LizardsAndPumpkins\MagentoConnector\Model\Catalog\Exception\InvalidCategoryIdException;

class LizardsAndPumpkins_MagentoConnector_Model_Catalog_CategoryUrlKeyService
{
    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Category_Collection
     */
    private $categories;

    /**
     * @param array[] $categoriesData
     */
    public function __construct(\LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Category_Collection $categories)
    {
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
        if ($intId === 0) {
            $message = sprintf('The category ID has to be an integer, got "%s"', $this->getType($categoryId));
            throw new InvalidCategoryIdException($message);
        }
        $categoriesData = $this->categories->getDataForStore($store);
        if (!isset($categoriesData[$intId])) {
            return [];
        }
        $parents = $this->findLayeredNavigationParents($intId, $categoriesData);
        return array_merge($parents, [$categoriesData[$categoryId]['url_key']]);
    }

    /**
     * @param int $categoryId
     * @param array[] $allCategories
     * @return string[]
     * @internal param mixed[] $categoryData
     */
    private function findLayeredNavigationParents($categoryId, array $allCategories)
    {
        return array_reduce($allCategories[$categoryId]['parent_ids'], function ($carry, $parentId) use ($allCategories) {
            return $allCategories[$parentId]['is_anchor'] ?
                array_merge($carry, [$allCategories[$parentId]['url_key']]) :
                $carry;
        }, []);
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
