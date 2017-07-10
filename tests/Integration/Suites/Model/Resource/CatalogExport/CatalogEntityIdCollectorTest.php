<?php

use LizardsAndPumpkins_MagentoConnector_Model_Resource_CatalogExport_CatalogEntityIdCollector as CatalogEntityIdCollector;

/**
 * @covers LizardsAndPumpkins_MagentoConnector_Model_Resource_CatalogExport_CatalogEntityIdCollector
 */
class LizardsAndPumpkins_MagentoConnector_Model_Resource_CatalogExport_CatalogEntityIdCollectorTest
    extends \PHPUnit\Framework\TestCase
{
    private $sampleDataActiveProductIdCount = 306;
    private $sampleDataCategoryIdCount = 28;

    /**
     * @var Mage_Core_Model_Resource
     */
    private $resource;

    /**
     * @param string $websiteCode
     * @param string $name
     * @return Mage_Core_Model_Website
     */
    private function createWebsite($websiteCode, $name)
    {
        $website = Mage::getModel('core/website');
        $website->setCode($websiteCode);
        $website->setName($name);
        $website->setIsDefault(0);
        $website->setSortOrder(100);
        $website->setData('is_staging', 0);
        $website->save();
        
        return $website;
    }

    /**
     * @param string $sku
     * @param string $name
     * @param int $websiteId
     * @return Mage_Catalog_Model_Product
     */
    private function createProduct($sku, $name, $websiteId)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel('catalog/product');
        $product->setData('type_id', 'simple');
        $product->setData('sku', $sku);
        $product->setData('name', $name);
        $product->setData('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $product->setData('website_ids', [$websiteId]);
        $product->setData('attribute_set_id', $product->getDefaultAttributeSetId());
        $product->save();

        return $product;
    }

    /**
     * @param string $name
     * @param string $path
     * @return Mage_Catalog_Model_Category
     */
    private function createCategory($name, $path)
    {
        /** @var Mage_Catalog_Model_Category $category */
        $category = Mage::getModel('catalog/category');
        $category->setData('name', $name);
        $category->setData('path', rtrim($path, '/'));
        $category->setData('is_active', 1);
        $category->save();

        return $category;
    }

    protected function setUp()
    {
        $this->resource = Mage::getSingleton('core/resource');
        $this->resource->getConnection('default_setup')->beginTransaction();
    }

    protected function tearDown()
    {
        $this->resource->getConnection('default_setup')->rollBack();
        \MagentoIntegrationTest::reset();
    }

    public function testFetchesAllActiveProductIds()
    {
        $productIds = (new CatalogEntityIdCollector())->getAllProductIds();
        
        $this->assertInternalType('array', $productIds);
        $this->assertCount($this->sampleDataActiveProductIdCount, $productIds);
    }

    public function testFetchesActiveProductIdsForGivenWebsite()
    {
        $website = $this->createWebsite(uniqid('test_'), 'Temp. Test Website');
        
        $product1 = $this->createProduct(uniqid('test-'), 'Temp. Test Product 1', $website->getId());
        $product2 = $this->createProduct(uniqid('test-'), 'Temp. Test Product 2', $website->getId());

        $productIds = (new CatalogEntityIdCollector())->getProductIdsForWebsite($website->getCode());
        
        $this->assertInternalType('array', $productIds);
        $this->assertCount(2, $productIds);
        $this->assertContains($product1->getId(), $productIds);
        $this->assertContains($product2->getId(), $productIds);
    }

    public function testFetchesAllCategoryIds()
    {
        $categoryIds = (new CatalogEntityIdCollector())->getAllCategoryIds();
        
        $this->assertInternalType('array', $categoryIds);
        $this->assertCount($this->sampleDataCategoryIdCount, $categoryIds);
    }

    public function testFetchesAllCategoryIdsForGivenWebsite()
    {
        $newRootCategory = $this->createCategory('Temp. Test Root Category', '1/');
        $subCategory1 = $this->createCategory('Temp. Test Sub Category 1', '1/' . $newRootCategory->getId());
        $subCategory2 = $this->createCategory('Temp. Test Sub Category 2', '1/' . $newRootCategory->getId());

        $website = Mage::app()->getWebsite();
        $storeGroup = $website->getDefaultGroup();
        $storeGroup->setRootCategoryId($newRootCategory->getId());
        $storeGroup->save();

        $categoryIds = (new CatalogEntityIdCollector())->getCategoryIdsForWebsite($website->getCode());
        
        $this->assertInternalType('array', $categoryIds);
        $this->assertCount(3, $categoryIds);
        $this->assertContains($newRootCategory->getId(), $categoryIds);
        $this->assertContains($subCategory1->getId(), $categoryIds);
        $this->assertContains($subCategory2->getId(), $categoryIds);
    }
}
