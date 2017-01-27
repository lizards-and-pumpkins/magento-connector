<?php

use LizardsAndPumpkins\MagentoConnector\Model\Catalog\Exception\InvalidCategoryIdException;

class LizardsAndPumpkins_MagentoConnector_Model_Catalog_CategoryUrlKeyServiceTest
    extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Catalog_CategoryUrlKeyService
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
        1 => ['parent_ids' => [], 'is_anchor' => 0, 'url_key' => 'one'],
        2 => ['parent_ids' => [1], 'is_anchor' => 0, 'url_key' => 'two'],
        3 => ['parent_ids' => [1], 'is_anchor' => 1, 'url_key' => 'three'],
        4 => ['parent_ids' => [1, 2], 'is_anchor' => 1, 'url_key' => 'four'],
        5 => ['parent_ids' => [1, 2], 'is_anchor' => 0, 'url_key' => 'five'],
        6 => ['parent_ids' => [1, 3], 'is_anchor' => 1, 'url_key' => 'six'],
        7 => ['parent_ids' => [1, 3], 'is_anchor' => 0, 'url_key' => 'seven'],
        8 => ['parent_ids' => [1, 2, 4], 'is_anchor' => 1, 'url_key' => 'eight'],
        9 => ['parent_ids' => [1, 2, 5], 'is_anchor' => 1, 'url_key' => 'nine'],
        10 => ['parent_ids' => [1, 3, 6], 'is_anchor' => 1, 'url_key' => 'ten'],
        11 => ['parent_ids' => [1, 3, 7], 'is_anchor' => 1, 'url_key' => 'eleven'],
    ];

    /**
     * @param array $categoriesData
     */
    private function createMockCategoryCollectionWithData(array $categoriesData)
    {
        $class = \LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Category_Collection::class;
        $this->stubCategoryCollection = $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->stubCategoryCollection->method('getDataForStore')->willReturn($categoriesData);
    }

    protected function setUp()
    {
        $this->createMockCategoryCollectionWithData($this->categoryFixture);
        
        $this->categories = new LizardsAndPumpkins_MagentoConnector_Model_Catalog_CategoryUrlKeyService(
            $this->stubCategoryCollection
        );
    }

    public function testItThrowsAnExceptionIfTheInputCategoryIdIsNotNumeric()
    {
        $this->expectException(InvalidCategoryIdException::class);
        $this->expectExceptionMessage('The category ID has to be an integer, got "string"');
        $this->categories->getCategoryUrlKeysByIdAndStore('foo', 'test');
    }

    public function testItReturnsAnEmptyArrayForACategoryWithNoParents()
    {
        $this->assertSame([], $this->categories->getCategoryUrlKeysByIdAndStore('1000000', 'test'));
    }

    public function testItTakesAStringOrIntCategoryId()
    {
        $this->assertSame([], $this->categories->getCategoryUrlKeysByIdAndStore('1000000', 'test'));
        $this->assertSame([], $this->categories->getCategoryUrlKeysByIdAndStore(1000000, 'test'));
    }

    /**
     * @param int|string $categoryId
     * @param int[] $expectedParents
     * @dataProvider layeredNavigationParentNamesProvider
     */
    public function testItReturnsTheParentCategoriesWithLayeredNavigation($categoryId, $expectedParents)
    {
        $categoriesData = $this->categories->getCategoryUrlKeysByIdAndStore($categoryId, 'test');
        $this->assertSame($expectedParents, $categoriesData);
    }

    public function layeredNavigationParentNamesProvider()
    {
        return [
            [1, ['one']],
            [2, ['one/two']],
            [3, ['one/three']],
            [4, ['one/two/four']],
            [5, ['one/two/five']],
            [6, ['one/three', 'one/three/six']],
            [7, ['one/three', 'one/three/seven']],
            [8, ['one/two/four', 'one/two/four/eight']],
            [9, ['one/two/five/nine']],
            [10, ['one/three', 'one/three/six', 'one/three/six/ten']],
            [11, ['one/three', 'one/three/seven/eleven']],
        ];
    }
}
