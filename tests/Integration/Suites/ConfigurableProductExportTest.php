<?php

declare(strict_types = 1);

require_once __DIR__ . '/AbstractInitializableEntityExportTest.php';

class ConfigurableEntityExportTest extends AbstractInitializableEntityExportTest
{
    private static $expectedXmlFile = __DIR__ . '/expected/configurable-product.xml';
    private static $configurableProductIdFile = __DIR__ . '/expected/configurable-product-id.php';

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
            /** @var Mage_Catalog_Model_Resource_Product_Collection $configurableProductCollection */
            $configurableProductCollection = Mage::getResourceModel('catalog/product_collection');
            $configurableProductCollection
                ->addAttributeToFilter('type_id', \Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
                ->addAttributeToFilter('is_saleable', 1)
                ->setVisibility($this->getProductVisibleInCatalogValues());

            $select = $configurableProductCollection->getSelect();
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
        return require $this->getEntityIdsFixtureFileName();
    }

    protected function setUp()
    {
        $this->testExportFile = sys_get_temp_dir() . '/lizards-and-pumpkins/magento-connector/configurable-product-test.xml';
        $imagesDir = sys_get_temp_dir() . '/lizards-and-pumpkins/magento-connector/product-images';
        if (! file_exists($imagesDir)) {
            mkdir($imagesDir, 0700, true);
        }
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
