<?php

use LizardsAndPumpkins\MagentoConnector\Model\Catalog\Product\Exception\InvalidCategoryDataException;
use LizardsAndPumpkins\MagentoConnector\Model\Catalog\Product\Exception\InvalidCategoryIdException;

class LizardsAndPumpkins_MagentoConnector_Model_Catalog_Product_CategoriesTest
    extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Catalog_Product_Categories
     */
    private $categories;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Category_Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stubCategoryCollection;
    
    /**
     * @var array[]
     * 
     *                    1 (no)
     *           /                  \
     *         2 (no)                3 (yes)
     *        /    \                /     \
     *     4 (yes)  5 (no)       6 (yes)   7 (no)
     *    /          \          /          \
     * 8 (yes)        9 (yes)  10 (yes)      11 (yes)
     */
    private $categoryFixture = [
        1 => ['parent_ids' => [], 'is_anchor' => 0, 'name' => 'one'],
        2 => ['parent_ids' => [1], 'is_anchor' => 0, 'name' => 'two'],
        3 => ['parent_ids' => [1], 'is_anchor' => 1, 'name' => 'three'],
        4 => ['parent_ids' => [1, 2], 'is_anchor' => 1, 'name' => 'four'],
        5 => ['parent_ids' => [1, 2], 'is_anchor' => 0, 'name' => 'five'],
        6 => ['parent_ids' => [1, 3], 'is_anchor' => 1, 'name' => 'six'],
        7 => ['parent_ids' => [1, 3], 'is_anchor' => 0, 'name' => 'seven'],
        8 => ['parent_ids' => [1, 2, 4], 'is_anchor' => 1, 'name' => 'eight'],
        9 => ['parent_ids' => [1, 2, 5], 'is_anchor' => 1, 'name' => 'nine'],
        10 => ['parent_ids' => [1, 3, 6], 'is_anchor' => 1, 'name' => 'ten'],
        11 => ['parent_ids' => [1, 3, 7], 'is_anchor' => 1, 'name' => 'eleven'],
    ];

    /**
     * @param array $categoriesData
     */
    private function createMockCategoryCollectionWithData(array $categoriesData)
    {
        $class = \LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Category_Collection::class;
        $this->stubCategoryCollection = $this->getMock($class, [], [], '', false);
        $this->stubCategoryCollection->method('getCategoryNamesByStore')->willReturn($categoriesData);
    }

    protected function setUp()
    {
        $this->createMockCategoryCollectionWithData($this->categoryFixture);
        
        $this->categories = new LizardsAndPumpkins_MagentoConnector_Model_Catalog_Product_Categories(
            $this->stubCategoryCollection
        );
    }

    public function testItThrowsAnExceptionIfTheInputCategoryIdIsNotNumeric()
    {
        $this->setExpectedException(
            InvalidCategoryIdException::class,
            'The category ID has to be an integer, got "string"'
        );
        $this->categories->getLayeredNavigationEnabledParentsByCategoryId('foo', 'test');
    }

    public function testItReturnsAnEmptyArrayForACategoryWithNoParents()
    {
        $this->assertSame([], $this->categories->getLayeredNavigationEnabledParentsByCategoryId('1000000', 'test'));
    }

    public function testItTakesAStringOrIntCategoryId()
    {
        $this->assertSame([], $this->categories->getLayeredNavigationEnabledParentsByCategoryId('1000000', 'test'));
        $this->assertSame([], $this->categories->getLayeredNavigationEnabledParentsByCategoryId(1000000, 'test'));
    }

    /**
     * @param int|string $categoryId
     * @param int[] $expectedParents
     * @dataProvider layeredNavigationParentNamesProvider
     */
    public function testItReturnsTheParentCategoriesWithLayeredNavigation($categoryId, $expectedParents)
    {
        $categoriesData = $this->categories->getLayeredNavigationEnabledParentsByCategoryId($categoryId, 'test');
        $this->assertSame($expectedParents, $categoriesData);
    }

    public function layeredNavigationParentNamesProvider()
    {
        return [
            [1, []],
            [2, []],
            [3, []],
            [4, []],
            [5, []],
            [6, ['three']],
            [7, ['three']],
            [8, ['four']],
            [9, []],
            [10, ['three', 'six']],
            [11, ['three']],
        ];
    }
}
