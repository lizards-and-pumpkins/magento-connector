<?php

use LizardsAndPumpkins\MagentoConnector\Model\Catalog\Product\Exception\InvalidCategoryDataException;
use LizardsAndPumpkins\MagentoConnector\Model\Catalog\Product\Exception\InvalidCategoryIdException;

class LizardsAndPumpkins_MagentoConnector_Model_Catalog_Product_Categories
{
    /**
     * @var array[]
     */
    private $categoriesData;

    /**
     * @param array[] $categoriesData
     */
    public function __construct(array $categoriesData)
    {
        $this->validateCategoriesData($categoriesData);
        $this->categoriesData = $categoriesData;
    }

    /**
     * @param array[] $categoriesData
     */
    private function validateCategoriesData(array $categoriesData)
    {
        array_map([$this, 'validateCategoryData'], array_keys($categoriesData), $categoriesData);
    }

    /**
     * @param int $categoryId
     * @param mixed[] $categoryData
     */
    private function validateCategoryData($categoryId, array $categoryData)
    {
        $this->validateRequiredArrayKeysArePresent($categoryId, $categoryData);

        if (!is_array($categoryData['parent_ids'])) {
            $type = $this->getType($categoryData['parent_ids']);
            $message = sprintf('The Category %s parent_ids are not an array (got a %s)', $categoryId, $type);
            throw new InvalidCategoryDataException($message);
        }
    }

    /**
     * @param int $categoryId
     * @param mixed[] $categoryData
     */
    private function validateRequiredArrayKeysArePresent($categoryId, array $categoryData)
    {
        array_map(function ($requiredKey) use ($categoryId, $categoryData) {
            if (!isset($categoryData[$requiredKey])) {
                $message = sprintf('The Category %s has is missing the "%s" array key', $categoryId, $requiredKey);
                throw new InvalidCategoryDataException($message);
            }
        }, ['parent_ids', 'is_anchor', 'name']);
    }

    /**
     * @param int|string $categoryId
     * @return string[]
     */
    public function getLayeredNavigationEnabledParentsByCategoryId($categoryId)
    {
        $intId = (int) $categoryId;
        if ($intId === 0) {
            $message = sprintf('The category ID has to be an integer, got "%s"', $this->getType($categoryId));
            throw new InvalidCategoryIdException($message);
        }
        if (!isset($this->categoriesData[$intId])) {
            return [];
        }
        return $this->findIsAnchorParents($intId);
    }

    /**
     * @param int $categoryId
     * @return string[]
     */
    private function findIsAnchorParents($categoryId)
    {
        return array_reduce($this->categoriesData[$categoryId]['parent_ids'], function ($carry, $parentId) {
            return isset($this->categoriesData[$parentId]) && $this->categoriesData[$parentId]['is_anchor'] ?
                array_merge($carry, [$this->categoriesData[$parentId]['name']]) :
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
