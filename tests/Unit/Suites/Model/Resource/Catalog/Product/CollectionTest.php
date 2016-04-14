<?php

/**
 * @covers LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection
 */
class LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_CollectionTest
    extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection
     */
    private $collection;

    /**
     * @param array[] $productsData
     * @return mixed[]|null
     */
    private function findProductWithSeveralCategories(array $productsData)
    {
        foreach ($productsData as $productData) {
            if (isset($productData['categories']) && count($productData['categories']) > 1) {
                return $productData;
            }
        }
        
        return null;
    }

    protected function setUp()
    {
        $this->collection = new \LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection();
    }

    public function testExceptionIsThrownDuringAttemptToLoadCollectionViaLoadMethod()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This collection should only be used to load raw data via getData()');

        $this->collection->load();
    }

    public function testFlatCatalogIsAlwaysDisabled()
    {
        $this->assertFalse($this->collection->isEnabledFlat());
    }

    public function testItExtendsTheEavProductCollection()
    {
        $this->assertInstanceOf(Mage_Catalog_Model_Resource_Product_Collection::class, $this->collection);
    }

    public function testIncludesProductInCategoryUrlKeysAsNonCanonicalUrlKeys()
    {
        $this->collection->setPageSize(25);
        $this->collection->setFlag(
            LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection::FLAG_ADD_CATEGORY_IDS,
            true
        );
        $productData = $this->findProductWithSeveralCategories($this->collection->getData());
        if (null === $productData) {
            $this->markTestSkipped('No product with categories found within the first 25 products');
        }

        $missingKeyMessage = 'No non_canonical_url_keys found on product with category assignment';
        $this->assertArrayHasKey('non_canonical_url_key', $productData, $missingKeyMessage);

        $nonCanonicalUrlKeys = $productData['non_canonical_url_key'];
        $categoryUrlSuffixLength = strlen(Mage::getStoreConfig('catalog/seo/category_url_suffix')) + 1;
        
        foreach ($productData['categories'] as $categoryUrlPath) {
            $categoryUrlKey = substr($categoryUrlPath, 0, -1 * $categoryUrlSuffixLength);
            $productInCategoryUrl = $categoryUrlKey . '/' . $productData['url_key'];
            $missingNonCanonicalUrlMessage = sprintf('Missing non_canonical_url_key %s', $productInCategoryUrl);
            $this->assertContains($productInCategoryUrl, $nonCanonicalUrlKeys, $missingNonCanonicalUrlMessage);
            
        }
    }
}
