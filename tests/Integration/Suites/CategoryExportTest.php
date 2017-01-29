<?php
declare(strict_types=1);

require_once __DIR__ . '/AbstractInitializableEntityExportTest.php';

class CategoryExportTest extends AbstractInitializableEntityExportTest implements InitializableCatalogEntityExportTest
{
    private static $expectedXmlFile = __DIR__ . '/expected/category.xml';
    private static $categoryIdsFile = __DIR__ . '/expected/category.php';

    /**
     * @var string
     */
    private $testExportFile;

    /**
     * @var string
     */
    private $categoryIdForInitialization;
    
    /**
     * @return string
     */
    public function getExpectationFileName()
    {
        return self::$expectedXmlFile;
    }

    /**
     * @return string[]|int[]
     */
    public function getEntityIdsForInitialization()
    {
        if (null === $this->categoryIdForInitialization) {
            /** @var Mage_Catalog_Model_Resource_Category_Collection $categoryCollection */
            $categoryCollection = Mage::getResourceModel('catalog/category_collection');
            $categoryCollection->addFieldToFilter('level', ['gt' => 2]);

            $select = $categoryCollection->getSelect();
            $select->reset(Zend_Db_Select::COLUMNS);
            $select->columns(['entity_id']);
            $select->limit(1);
            $this->categoryIdForInitialization = Mage::getSingleton('core/resource')
                ->getConnection('default_read')
                ->fetchOne($select);
        }
        return $this->categoryIdForInitialization;
    }

    /**
     * @return string
     */
    final protected function getEntityIdsFixtureFileName()
    {
        return self::$categoryIdsFile;
    }

    /**
     * @return string
     */
    private function getCategoryId()
    {
        return require $this->getEntityIdsFixtureFileName();
    }

    protected function setUp()
    {
        $this->testExportFile = sys_get_temp_dir() . '/lizards-and-pumpkins/magento-connector/category-test.xml';
    }

    protected function tearDown()
    {
        @unlink($this->testExportFile);
        @rmdir(dirname($this->testExportFile));
    }

    public function testExportCategory()
    {
        $this->exportToFile($this->testExportFile, [$this->getCategoryId()]);

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
        $collector = $this->getEntityCollectorForIds($entityIds);
        $exporter->exportCategories($collector);
    }

    /**
     * @param int[] $entityIds
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryCollector
     */
    protected function getEntityCollectorForIds($entityIds)
    {
        /** @var LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig $config */
        $config = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_magentoConfig');
        $categoryCollector = new LizardsAndPumpkins_MagentoConnector_Model_Export_SpecificCategoryCollector($config);
        $categoryCollector->setCategoryIdsToExport($entityIds);
        return $categoryCollector;
        
    }
}
