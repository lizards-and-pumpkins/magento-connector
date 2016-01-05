<?php

class LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Category_CollectionTest
    extends \PHPUnit_Framework_TestCase
{
    private $testStore = 'admin';

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

    protected function setUp()
    {
        $this->collection = new LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Category_Collection();
    }

    public function testItExtendsTheCategoryEavCollection()
    {
        $this->assertInstanceOf(Mage_Catalog_Model_Resource_Category_Collection::class, $this->collection);
    }

    public function testItThrowsAnExceptionIfTheRegularLoadMethodIsCalled()
    {
        $this->setExpectedException(
            Mage_Core_Exception::class,
            'Do not use load(), use getDataForStore() instead'
        );
        $this->collection->load();
    }

    public function testItThrowsAnExceptionIfTheRegularGetDataMethodIsCalled()
    {
        $this->setExpectedException(
            Mage_Core_Exception::class,
            'Do not use getData(), use getDataForStore() instead'
        );
        $this->collection->getData();
    }

    public function testAllRecordsHaveTheRequiredAttributes()
    {
        $categoriesData = $this->collection->getDataForStore($this->testStore);

        $this->assertInternalType('array', $categoriesData);
        $this->assertGreaterThan(0, count($categoriesData));

        $this->assertAllCategoryRecordsHaveAttribute('is_anchor', $categoriesData);
        $this->assertAllCategoryRecordsHaveAttribute('name', $categoriesData);
    }

    public function testResultIsIndexedByEntityId()
    {
        $categoriesData = $this->collection->getDataForStore($this->testStore);

        array_map(function ($index, array $categoryData) {
            $this->assertEquals($index, $categoryData['entity_id'], 'The category data array not indexed by entity id');
        }, array_keys($categoriesData), $categoriesData);
    }

    public function testAllRecordsHaveAnArrayWithParentCategoryIds()
    {
        array_map(function (array $categoryData) {
            $this->assertArrayHasKey('parent_ids', $categoryData);
            $this->assertInternalType('array', $categoryData['parent_ids']);
        }, $this->collection->getDataForStore($this->testStore));
    }
}
