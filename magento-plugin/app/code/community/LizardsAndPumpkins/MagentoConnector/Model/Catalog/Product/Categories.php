<?php

use LizardsAndPumpkins\MagentoConnector\Model\Catalog\Product\Exception\InvalidCategoryIdException;

class LizardsAndPumpkins_MagentoConnector_Model_Catalog_Product_Categories
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
    public function getLayeredNavigationEnabledParentsByCategoryId($categoryId, $store)
    {
        $intId = (int) $categoryId;
        if ($intId === 0) {
            $message = sprintf('The category ID has to be an integer, got "%s"', $this->getType($categoryId));
            throw new InvalidCategoryIdException($message);
        }
        $categoriesData = $this->categories->getCategoryNamesByStore($store);
        if (!isset($categoriesData[$intId])) {
            return [];
        }
        return $this->findLayeredNavigationParents($intId, $categoriesData);
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
                array_merge($carry, [$allCategories[$parentId]['name']]) :
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
