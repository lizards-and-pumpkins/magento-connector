<?php

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
        $urlSuffix = Mage::getStoreConfig('catalog/seo/category_url_suffix');
        $urlSuffix = '.' === $urlSuffix[0] ? $urlSuffix : '.' . $urlSuffix;
        $categoryUrlSuffixLength = strlen($urlSuffix);

        foreach ($productData['categories'] as $categoryUrlPath) {
            $categoryUrlKey = substr($categoryUrlPath, 0, -1 * $categoryUrlSuffixLength);
            $productInCategoryUrl = $categoryUrlKey . '/' . $productData['url_key'];
            $missingNonCanonicalUrlMessage = sprintf('Missing non_canonical_url_key %s', $productInCategoryUrl);
            $this->assertContains($productInCategoryUrl, $nonCanonicalUrlKeys, $missingNonCanonicalUrlMessage);
        }
    }

    public function testAttributeNullValuesAreSetAsEmptyStrings()
    {
        $testCollection = new StubCollection();
        
        $testCollection->publicSetItemAttributeValue([
            'entity_id' => 1,
            'attribute_id' => 123,
            'value' => null,
        ]);
        
        $this->assertSame('', $testCollection->getData()[1]['foo']);
    }

    public function testProductUrlKeySuffixIsAppendedToUrlKeySeparatedByADot()
    {
        $storeId = 1;
        $this->collection->setStore($storeId);
        $this->collection->addAttributeToSelect('url_key');
        $urlKeySuffix = 'html';
        Mage::app()->getStore($storeId)->setConfig('catalog/seo/product_url_suffix', $urlKeySuffix);
        $this->collection->setPageSize(5);
        
        foreach ($this->collection->getData() as $productData) {
            $this->assertStringEndsWith('.' . $urlKeySuffix, $productData['url_key']);
        }
    }

    public function testProductUrlPathIsSeparatedFromUrlKeySuffixByADot()
    {
        $storeId = 1;
        $this->collection->setStore($storeId);
        $this->collection->addAttributeToSelect('url_path');
        $urlKeySuffix = 'html';
        Mage::app()->getStore($storeId)->setConfig('catalog/seo/product_url_suffix', $urlKeySuffix);
        $this->collection->setPageSize(5);
        
        foreach ($this->collection->getData() as $productData) {
            $this->assertStringEndsWith('.' . $urlKeySuffix, $productData['url_key']);
        }
    }

    public function testRawCatalogProductViewPathIsUsedIfNoUrlKeyOrPathIsAddedToSelect()
    {
        $storeId = 1;
        $this->collection->setStore($storeId);
        Mage::app()->getStore($storeId)->setConfig('catalog/seo/product_url_suffix', 'html');
        $this->collection->setPageSize(10);
        
        foreach ($this->collection->getData() as $productData) {
            $this->assertSame('catalog/product/view/id/' . $productData['entity_id'], $productData['url_key']);
        }
    }

    public function testSetsTheCorrectTaxClassOnProducts()
    {
        $this->collection->setPageSize(10);

        foreach ($this->collection->getData() as $productData) {
            $this->assertSame('Taxable Goods', $productData['tax_class']);
        }
    }
}

class StubCollection extends LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection
{
    protected $_selectAttributes = ['foo' => 123];

    public function publicSetItemAttributeValue(array $valueInfo)
    {
        return $this->_setItemAttributeValue($valueInfo);
    }
}
