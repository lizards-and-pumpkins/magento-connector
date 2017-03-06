<?php

declare(strict_types = 1);

require_once __DIR__ . '/AbstractInitializableEntityExportTest.php';

class SimpleEntityExportTest extends AbstractInitializableEntityExportTest
{
    private static $expectedXmlFile = __DIR__ . '/expected/simple-product.xml';
    private static $simpleProductIdFile = __DIR__ . '/expected/simple-product-id.php';

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
    public function getEntityIdsForInitialization()
    {
        if (null === $this->productIdForInitialization) {
            /** @var Mage_Catalog_Model_Resource_Product_Collection $simpleProductCollection */
            $simpleProductCollection = Mage::getResourceModel('catalog/product_collection');
            $simpleProductCollection
                ->addAttributeToFilter('type_id', \Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
                ->addAttributeToFilter('is_saleable', 1)
                ->setVisibility($this->getProductVisibleInCatalogValues());

            $select = $simpleProductCollection->getSelect();
            $select->reset(Zend_Db_Select::COLUMNS);
            $select->columns(['entity_id']);
            $select->limit(1);
            $this->productIdForInitialization = Mage::getSingleton('core/resource')
                ->getConnection('default_read')
                ->fetchOne($select);
        }
        return $this->productIdForInitialization;
    }

    /**
     * @return string
     */
    final protected function getEntityIdsFixtureFileName()
    {
        return self::$simpleProductIdFile;
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
    private function getSimpleProductId()
    {
        return require $this->getEntityIdsFixtureFileName();
    }

    protected function setUp()
    {
        $this->testExportFile = sys_get_temp_dir() . '/lizards-and-pumpkins/magento-connector/simple-product-test.xml';
    }

    protected function tearDown()
    {
        @unlink($this->testExportFile);
        @rmdir(dirname($this->testExportFile));
    }

    public function testExportConfigurableProduct()
    {
        $this->exportToFile($this->testExportFile, [$this->getSimpleProductId()]);
        
        $this->assertFileEquals($this->getExpectationFileName(), $this->testExportFile);
    }

    /**
     * @param LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter $exporter
     * @param int[] $entityIds
     * @return mixed
     */
    public function exportEntities(
        LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter $exporter,
        $entityIds
    ) {
        $exporter->exportProducts($this->createProductCollectorForIds($entityIds));
    }
}
