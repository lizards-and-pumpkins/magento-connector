<?php

require_once __DIR__ . '/AbstractInitializableProductExportTest.php';

class ConfigurableProductExportTest extends AbstractInitializableProductExportTest
{
    private static $expectedXmlFile = __DIR__ . '/expected/configurable-product.xml';
    private static $configurableProductIdFile = __DIR__ . '/expected/configurable-product-id.php';

    /**
     * @var string
     */
    private $testExportFile;

    /**
     * @return string[]|string
     */
    public function getProductIdsForInitialization()
    {
        /** @var Mage_Catalog_Model_Resource_Product_Collection $configurableProductCollection */
        $configurableProductCollection = Mage::getResourceModel('catalog/product_collection');
        $configurableProductCollection
            ->addAttributeToFilter('type_id', \Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE)
            ->addAttributeToFilter('is_saleable', 1)
            ->setPageSize(1);

        $select = $configurableProductCollection->getSelect();
        $select->reset(Zend_Db_Select::COLUMNS);
        $select->columns(['entity_id']);
        return Mage::getSingleton('core/resource')->getConnection('default_read')->fetchOne($select);

    }

    /**
     * @return string
     */
    protected function getProductIdsFixtureFileName()
    {
        return self::$configurableProductIdFile;
    }

    /**
     * @return string
     */
    public function getExpectationFileName()
    {
        return self::$expectedXmlFile;
    }

    /**
     * @return string
     */
    private function getConfigurableProductId()
    {
        return require $this->getProductIdsFixtureFileName();
    }

    protected function setUp()
    {
        $this->testExportFile = sys_get_temp_dir() . '/lizards-and-pumpkins/magento-connector/configurable-product-test.xml';
        $this->prepareTestExportDirectory(dirname($this->testExportFile));
    }

    protected function tearDown()
    {
        @unlink($this->testExportFile);
        @rmdir(dirname($this->testExportFile));
    }

    public function testExportConfigurableProduct()
    {
        $this->exportToFile($this->testExportFile, [$this->getConfigurableProductId()]);
        
        $this->assertFileEquals($this->getExpectationFileName(), $this->testExportFile);
    }
}
