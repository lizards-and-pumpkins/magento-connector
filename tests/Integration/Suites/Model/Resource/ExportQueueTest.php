<?php

use LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue as ExportQueue;
use LizardsAndPumpkins_MagentoConnector_Model_ExportQueue_Message as ExportQueueMessage;
use LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message_Collection as ExportQueueMessageCollection;

class LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueueTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Mage_Core_Model_Resource
     */
    private $resource;

    /**
     * @param string $sku
     * @return Mage_Catalog_Model_Product
     */
    private function createCatalogProduct($sku)
    {
        $product = Mage::getModel('catalog/product');
        $product->setData('sku', $sku);
        $product->setData('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $product->setData('type_id', Mage_Catalog_Model_Product_Type::TYPE_SIMPLE);
        $product->setData('attribute_set_id', $product->getDefaultAttributeSetId());
        $product->save();

        return $product;
    }

    /**
     * @param string $name
     * @return Mage_Catalog_Model_Category
     */
    private function createCatalogCategory($name)
    {
        Mage::app()->getStore()->getGroup()->getRootCategoryId();
        
        $category = Mage::getModel('catalog/category');
        $category->setData('name', $name);
        
        $category->setData('parent', Mage::app()->getStore()->getGroup()->getRootCategoryId());
        $category->setData('attribute_set_id', $category->getDefaultAttributeSetId());
        $category->setData('level', 2);
        $category->save();

        return $category;
    }

    /**
     * @param string $code
     * @return Mage_Core_Model_Website
     */
    private function createWebsite($code)
    {
        /** @var Mage_Core_Model_Website $website */
        $website = Mage::getModel('core/website');
        $website->setCode($code);
        $website->save();
        return $website;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param string $dataVersion
     * @param ExportQueueMessageCollection[] $queuedProductsByDataVersion
     */
    private function assertContainsProductWithDataVersion(
        Mage_Catalog_Model_Product $product,
        $dataVersion,
        array $queuedProductsByDataVersion
    ) {
        $productId = $product->getId();
        $type = ExportQueue::TYPE_PRODUCT;
        $this->assertContainsItemWithDataVersion($productId, $type, $dataVersion, $queuedProductsByDataVersion);
    }

    /**
     * @param Mage_Catalog_Model_Category $category
     * @param string $dataVersion
     * @param ExportQueueMessageCollection[] $queuedCategoriesByDataVersion
     */
    private function assertContainsCategoryWithDataVersion(
        Mage_Catalog_Model_Category $category,
        $dataVersion,
        array $queuedCategoriesByDataVersion
    ) {
        $categoryId = $category->getId();
        $type = ExportQueue::TYPE_CATEGORY;
        $this->assertContainsItemWithDataVersion($categoryId, $type, $dataVersion, $queuedCategoriesByDataVersion);
    }

    /**
     * @param int $objectId
     * @param string $type
     * @param string $dataVersion
     * @param ExportQueueMessageCollection[] $queuedItemsByDataVersion
     */
    private function assertContainsItemWithDataVersion(
        $objectId,
        $type,
        $dataVersion,
        array $queuedItemsByDataVersion
    ) {
        $queuedItems = $queuedItemsByDataVersion[$dataVersion];
        /** @var ExportQueueMessage $queueMessage */
        $queueMessage = $queuedItems->getItemByColumnValue(ExportQueueMessage::OBJECT_ID, $objectId);
        $this->assertInstanceOf(ExportQueueMessage::class, $queueMessage);
        $this->assertSame($dataVersion, $queueMessage->getDataVersion());
        $this->assertSame($type, $queueMessage->getType());
    }

    private function assertNotContainsProductWithDataVersion(
        Mage_Catalog_Model_Product $product,
        $targetVersion,
        array $queuedProductsByDataVersion
    ) {
        $queuedProducts = $queuedProductsByDataVersion[$targetVersion];
        $queueMessage = $queuedProducts->getItemByColumnValue(ExportQueueMessage::OBJECT_ID, $product->getId());
        $this->assertNull($queueMessage);
    }

    private function assertNotContainsCategoryWithDataVersion(
        Mage_Catalog_Model_Category $category,
        $targetVersion,
        array $queuedCategoriesByDataVersion
    ) {
        $queuedCategories = $queuedCategoriesByDataVersion[$targetVersion];
        $queueMessage = $queuedCategories->getItemByColumnValue(ExportQueueMessage::OBJECT_ID, $category->getId());
        $this->assertNull($queueMessage);
    }

    /**
     * @return ExportQueue
     */
    private function createExportQueue()
    {
        return Mage::getResourceModel('lizardsAndPumpkins_magentoconnector/exportQueue');
    }

    /**
     * @return \LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueueReader
     */
    private function createExportQueueReader()
    {
        return Mage::getResourceModel('lizardsAndPumpkins_magentoconnector/exportQueueReader');
    }

    protected function setUp()
    {
        $this->resource = Mage::getSingleton('core/resource');
        $this->resource->getConnection('default_setup')->beginTransaction();
        $tableName = $this->resource->getTableName('lizardsAndPumpkins_magentoconnector/queue');
        $this->resource->getConnection('default_setup')->delete($tableName);
    }

    protected function tearDown()
    {
        $this->resource->getConnection('default_setup')->rollBack();
    }

    public function testAddsAllProductsToTheUpdateQueue()
    {
        $targetVersion = 'baz';

        $product1 = $this->createCatalogProduct('foo');
        $product2 = $this->createCatalogProduct('bar');

        $this->createExportQueue()->addAllProductIdsToProductUpdateQueue($targetVersion);

        $queuedProductsByDataVersion = $this->createExportQueueReader()->getQueuedProductUpdatesGroupedByDataVersion();

        $this->assertInternalType('array', $queuedProductsByDataVersion);
        $this->assertContainsOnlyInstancesOf(ExportQueueMessageCollection::class, $queuedProductsByDataVersion);
     
        $this->assertContainsProductWithDataVersion($product1, $targetVersion, $queuedProductsByDataVersion);
        $this->assertContainsProductWithDataVersion($product2, $targetVersion, $queuedProductsByDataVersion);
    }

    public function testAddsAllProductsToTheUpdateQueueKeepingMultipleDataVersions()
    {
        $product = $this->createCatalogProduct('foo');
        
        $targetVersion1 = 'baz';
        $targetVersion2 = 'qux';
        $this->createExportQueue()->addAllProductIdsToProductUpdateQueue($targetVersion1);
        $this->createExportQueue()->addAllProductIdsToProductUpdateQueue($targetVersion2);

        $queuedProductsByDataVersion = $this->createExportQueueReader()->getQueuedProductUpdatesGroupedByDataVersion();

        $this->assertContainsProductWithDataVersion($product, $targetVersion1, $queuedProductsByDataVersion);
        $this->assertContainsProductWithDataVersion($product, $targetVersion2, $queuedProductsByDataVersion);
    }

    public function testAddsAGivenProductDataVersionToTheQueueOnlyOnce()
    {
        $product = $this->createCatalogProduct('foo');
        $targetVersion = 'baz';

        $this->createExportQueue()->addAllProductIdsToProductUpdateQueue($targetVersion);
        $this->createExportQueue()->addAllProductIdsToProductUpdateQueue($targetVersion);

        $queuedProductsByDataVersion = $this->createExportQueueReader()->getQueuedProductUpdatesGroupedByDataVersion();
        $queuedProducts = $queuedProductsByDataVersion[$targetVersion];
        
        $this->assertCount(1, $queuedProducts->getItemsByColumnValue(ExportQueueMessage::OBJECT_ID, $product->getId()));
    }

    public function testAddsProductsFromAGivenWebsiteToTheQueue()
    {
        $targetVersionWeb1 = 'qux1';
        $targetVersionWeb2 = 'qux2';
        
        $website1Id = $this->createWebsite('foo1')->getId();
        $website2Id = $this->createWebsite('foo2')->getId();

        $product1 = $this->createCatalogProduct('foo');
        $product2 = $this->createCatalogProduct('bar');
        
        $product1->setData('website_ids', [$website1Id, $website2Id]);
        $product2->setData('website_ids', [$website1Id]);
        $product1->save();
        $product2->save();

        $this->createExportQueue()->addAllProductIdsFromWebsiteToProductUpdateQueue($website1Id, $targetVersionWeb1);
        $this->createExportQueue()->addAllProductIdsFromWebsiteToProductUpdateQueue($website2Id, $targetVersionWeb2);

        $queuedProductsByDataVersion = $this->createExportQueueReader()->getQueuedProductUpdatesGroupedByDataVersion();

        $this->assertContainsProductWithDataVersion($product1, $targetVersionWeb1, $queuedProductsByDataVersion);
        $this->assertContainsProductWithDataVersion($product2, $targetVersionWeb1, $queuedProductsByDataVersion);
        
        $this->assertContainsProductWithDataVersion($product1, $targetVersionWeb2, $queuedProductsByDataVersion);
        $this->assertNotContainsProductWithDataVersion($product2, $targetVersionWeb2, $queuedProductsByDataVersion);
    }

    public function testAddsSpecifiedProductUpdatesToTheQueue()
    {
        $targetDataVersion = 'foo';

        $product1 = $this->createCatalogProduct('bar1');
        $product2 = $this->createCatalogProduct('bar2');
        $product3 = $this->createCatalogProduct('bar3');
        $productIds = [$product1->getId(), $product2->getId()];

        $this->createExportQueue()->addProductUpdatesToQueue($productIds, $targetDataVersion);

        $queuedProductsByDataVersion = $this->createExportQueueReader()->getQueuedProductUpdatesGroupedByDataVersion();

        $this->assertContainsProductWithDataVersion($product1, $targetDataVersion, $queuedProductsByDataVersion);
        $this->assertContainsProductWithDataVersion($product2, $targetDataVersion, $queuedProductsByDataVersion);
        $this->assertNotContainsProductWithDataVersion($product3, $targetDataVersion, $queuedProductsByDataVersion);
    }

    public function testAddSingleProductUpdateToTheQueue()
    {
        $targetDataVersion = 'baz';

        $product1 = $this->createCatalogProduct('foo');
        $product2 = $this->createCatalogProduct('bar');

        $this->createExportQueue()->addProductUpdateToQueue($product1->getId(), $targetDataVersion);

        $queuedProductsByDataVersion = $this->createExportQueueReader()->getQueuedProductUpdatesGroupedByDataVersion();

        $this->assertContainsProductWithDataVersion($product1, $targetDataVersion, $queuedProductsByDataVersion);
        $this->assertNotContainsProductWithDataVersion($product2, $targetDataVersion, $queuedProductsByDataVersion);
    }

    public function testAddsAllCategoriesToTheUpdateQueue()
    {
        $targetVersion = 'baz';
        
        $category1 = $this->createCatalogCategory('foo');
        $category2 = $this->createCatalogCategory('bar');

        $this->createExportQueue()->addAllCategoryIdsToCategoryQueue($targetVersion);

        $queuedCategoriesByDataVersion = $this->createExportQueueReader()
            ->getQueuedCategoryUpdatesGroupedByDataVersion();

        $this->assertInternalType('array', $queuedCategoriesByDataVersion);
        $this->assertCount(1, $queuedCategoriesByDataVersion);
        $this->assertContainsOnlyInstancesOf(ExportQueueMessageCollection::class, $queuedCategoriesByDataVersion);

        $this->assertContainsCategoryWithDataVersion($category1, $targetVersion, $queuedCategoriesByDataVersion);
        $this->assertContainsCategoryWithDataVersion($category2, $targetVersion, $queuedCategoriesByDataVersion);
    }

    public function testAddsSingleCategoryToTheUpdateQueue()
    {
        $targetDataVersion = 'baz';
        
        $category1 = $this->createCatalogCategory('foo');
        $category2 = $this->createCatalogCategory('bar');

        $this->createExportQueue()->addCategoryToQueue($category1->getId(), $targetDataVersion);

        $queuedCategoriesByDataVersion = $this->createExportQueueReader()
            ->getQueuedCategoryUpdatesGroupedByDataVersion();

        $this->assertContainsCategoryWithDataVersion($category1, $targetDataVersion, $queuedCategoriesByDataVersion);
        $this->assertNotContainsCategoryWithDataVersion($category2, $targetDataVersion, $queuedCategoriesByDataVersion);
    }

    public function testReturnsTheNumberOfProductUpdateMessagesOnTheQueue()
    {
        $targetDataVersion = 'baz';

        $product1 = $this->createCatalogProduct('foo');
        $product2 = $this->createCatalogProduct('bar');
        $productIds = [$product1->getId(), $product2->getId()];

        $this->createExportQueue()->addProductUpdatesToQueue($productIds, $targetDataVersion);
        
        $this->assertSame(2, $this->createExportQueueReader()->getProductQueueCount());
    }

    public function testReturnsTheNumberOfCategoryUpdateMessagesOnTheQueue()
    {
        $targetDataVersion = 'baz';

        $category1 = $this->createCatalogCategory('foo');
        $category2 = $this->createCatalogCategory('bar');

        $this->createExportQueue()->addCategoryToQueue($category1->getId(), $targetDataVersion);
        $this->createExportQueue()->addCategoryToQueue($category2->getId(), $targetDataVersion);
        
        $this->assertSame(2, $this->createExportQueueReader()->getCategoryQueueCount());
    }

    public function testDeletesTheSpecifiedMessagesFromTheQueue()
    {
        $targetDataVersion = 'baz';

        $product1 = $this->createCatalogProduct('foo');
        $product2 = $this->createCatalogProduct('bar');
        $productIds = [$product1->getId(), $product2->getId()];

        $this->createExportQueue()->addProductUpdatesToQueue($productIds, $targetDataVersion);
        
        $collections = $this->createExportQueueReader()->getQueuedProductUpdatesGroupedByDataVersion();
        $this->assertCount(1, $collections);
        
        foreach ($collections as $collection) {
            $this->createExportQueue()->removeMessages($collection->getAllIds());
        }

        $this->assertSame([], $this->createExportQueueReader()->getQueuedProductUpdatesGroupedByDataVersion());
        
        
    }
}
