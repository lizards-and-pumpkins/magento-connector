<?php

require_once __DIR__ . '/AbstractInitializableProductExportTest.php';

class TwoProductsExportTest extends AbstractInitializableProductExportTest
{
    private static $expectedXmlFile = __DIR__ . '/expected/two-products.xml';
    private static $productIdsFile = __DIR__ . '/expected/two-product-ids.php';

    /**
     * @var string
     */
    private $testExportFile;

    /**
     * @var string
     */
    private $productIdForInitialization;

    /**
     * @return string
     */
    public function getProductIdsForInitialization()
    {
        if (null === $this->productIdForInitialization) {
            /** @var Mage_Catalog_Model_Resource_Product_Collection $configurableProductCollection */
            $configurableProductCollection = Mage::getResourceModel('catalog/product_collection');
            $configurableProductCollection
                ->addAttributeToFilter('is_saleable', 1)
                ->setVisibility($this->getProductVisibleInCatalogValues());

            $select = $configurableProductCollection->getSelect();
            $select->reset(Zend_Db_Select::COLUMNS);
            $select->columns(['entity_id']);
            $select->limit(2);
            $this->productIdForInitialization = Mage::getSingleton('core/resource')
                ->getConnection('default_read')
                ->fetchCol($select);
        }
        return $this->productIdForInitialization;
    }

    /**
     * @return string
     */
    final protected function getProductIdsFixtureFileName()
    {
        return self::$productIdsFile;
    }

    /**
     * @return string
     */
    public function getExpectationFileName()
    {
        return self::$expectedXmlFile;
    }

    /**
     * @return array
     */
    private function getProductIds()
    {
        return require $this->getProductIdsFixtureFileName();
    }

    protected function setUp()
    {
        $this->testExportFile = sys_get_temp_dir() . '/lizards-and-pumpkins/magento-connector/two-products-test.xml';
    }

    protected function tearDown()
    {
        @unlink($this->testExportFile);
        @rmdir(dirname($this->testExportFile));
    }

    /**
     * @group fixture
     */
    public function testExportTwoProducts()
    {
        $this->exportToFile($this->testExportFile, $this->getProductIds());
        
        $this->assertFileEquals($this->getExpectationFileName(), $this->testExportFile);
    }
}
