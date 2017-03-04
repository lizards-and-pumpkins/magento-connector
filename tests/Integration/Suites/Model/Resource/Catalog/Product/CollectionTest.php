<?php

declare(strict_types = 1);

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

    public function testItExtendsTheEavProductCollection()
    {
        $this->assertInstanceOf(Mage_Catalog_Model_Resource_Product_Collection::class, $this->collection);
    }

    public function testIncludesProductInCategoryUrlKeysAsNonCanonicalUrlKeys()
    {
        $this->collection->setStore(1);
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
        $categoryUrlSuffixLength = strlen(Mage::getStoreConfig('catalog/seo/category_url_suffix'));

        foreach ($productData['categories'] as $categoryUrlPath) {
            $categoryUrlKey = substr($categoryUrlPath, 0, -1 * $categoryUrlSuffixLength);
            $productInCategoryUrl = $categoryUrlKey . '/' . $productData['url_key'];
            $missingNonCanonicalUrlMessage = sprintf('Missing non_canonical_url_key %s', $productInCategoryUrl);
            $this->assertContains($productInCategoryUrl, $nonCanonicalUrlKeys, $missingNonCanonicalUrlMessage);
        }
    }

    public function testAttributeNullValuesAreSetAsEmptyStrings()
    {
        $testCollection = new class
            extends LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection
        {
            protected $_selectAttributes = ['foo' => 123];
            
            public function publicSetItemAttributeValue(array $valueInfo)
            {
                return $this->_setItemAttributeValue($valueInfo);
            }
        };
        
        $testCollection->publicSetItemAttributeValue([
            'entity_id' => 1,
            'attribute_id' => 123,
            'value' => null,
        ]);
        
        $this->assertSame('', $testCollection->getData()[1]['foo']);
    }
}
