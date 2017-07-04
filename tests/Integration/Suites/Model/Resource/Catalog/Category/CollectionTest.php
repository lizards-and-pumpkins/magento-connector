<?php

/**
 * @covers LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Category_Collection
 */
class LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Category_CollectionTest
    extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $testStoreCode;

    /**
     * @var int
     */
    private $testStoreRootCategoryPath;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Category_Collection
     */
    private $collection;

    /**
     * @param string $attributeCode
     * @param string[] $categoriesData
     */
    private function assertAllCategoryRecordsHaveAttribute($attributeCode, array $categoriesData)
    {
        array_map(function (array $categoryData) use ($attributeCode) {
            $message = sprintf('Category record is missing attribute "%s"', $attributeCode);
            $this->assertArrayHasKey($attributeCode, $categoryData, $message);
        }, $categoriesData);
    }

    /**
     * @return Mage_Core_Model_Store
     */
    private function getFrontendStoreInstance()
    {
        $stores = Mage::app()->getStores();
        /** @var Mage_Core_Model_Store $testStore */
        $testStore = reset($stores);
        return $testStore;
    }

    protected function setUp()
    {
        $testStore = $this->getFrontendStoreInstance();
        $this->testStoreCode = $testStore->getCode();
        $rootCategory = Mage::getModel('catalog/category')->load($testStore->getRootCategoryId());
        $this->testStoreRootCategoryPath = $rootCategory->getPath();

        $this->collection = new LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Category_Collection();
    }

    public function testItExtendsTheCategoryEavCollection()
    {
        $this->assertInstanceOf(Mage_Catalog_Model_Resource_Category_Collection::class, $this->collection);
    }

    public function testItThrowsAnExceptionIfTheRegularLoadMethodIsCalled()
    {
        $this->expectException(Mage_Core_Exception::class);
        $this->expectExceptionMessage('Do not use load(), use getDataForStore() instead');
        $this->collection->load();
    }

    public function testItThrowsAnExceptionIfTheRegularGetDataMethodIsCalled()
    {
        $this->expectException(Mage_Core_Exception::class);
        $this->expectExceptionMessage('Do not use getData(), use getDataForStore() instead');
        $this->collection->getData();
    }

    public function testAllRecordsHaveTheRequiredAttributes()
    {
        $categoriesData = $this->collection->getDataForStore($this->testStoreCode);

        $this->assertInternalType('array', $categoriesData);
        $this->assertGreaterThan(0, count($categoriesData));

        $this->assertAllCategoryRecordsHaveAttribute('is_anchor', $categoriesData);
        $this->assertAllCategoryRecordsHaveAttribute('url_key', $categoriesData);
    }

    public function testResultIsIndexedByEntityId()
    {
        $categoriesData = $this->collection->getDataForStore($this->testStoreCode);

        array_map(function ($index, array $categoryData) {
            $this->assertEquals($index, $categoryData['entity_id'], 'The category data array not indexed by entity id');
        }, array_keys($categoriesData), $categoriesData);
    }

    public function testAllRecordsHaveAnArrayWithParentCategoryIds()
    {
        array_map(function (array $categoryData) {
            $this->assertArrayHasKey('parent_ids', $categoryData);
            $this->assertInternalType('array', $categoryData['parent_ids']);
        }, $this->collection->getDataForStore($this->testStoreCode));
    }

    public function testItIncludesOnlyChildCategoriesOfTheRootCategoryForTheGivenStore()
    {
        $categoriesData = $this->collection->getDataForStore($this->testStoreCode);
        $rootPathLength = strlen($this->testStoreRootCategoryPath);
        $expected = $this->testStoreRootCategoryPath . '/';
        $this->assertGreaterThan(0, count($categoriesData));
        foreach ($categoriesData as $categoryData) {
            $this->assertSame($expected, substr($categoryData['path'], 0, $rootPathLength + 1));
        }
    }
}
