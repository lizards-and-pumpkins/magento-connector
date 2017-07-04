<?php

abstract class AbstractInitializableProductExportTest
    extends \PHPUnit_Framework_TestCase
    implements InitializableCatalogProductExportTest
{
    public function initTestExpectations()
    {
        $entityIds = $this->getProductIdsForInitialization();
        $this->exportToFile($this->getExpectationFileName(), is_array($entityIds) ? $entityIds : [$entityIds]);
        $this->saveProductIdsToTest($entityIds);
    }

    /**
     * @return int[]
     */
    final protected function getProductVisibleInCatalogValues()
    {
        return [
            \Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
            \Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG,
            \Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH,
        ];
    }

    /**
     * @return string
     */
    protected abstract function getProductIdsFixtureFileName();

    /**
     * @param string $exportFile
     * @param string[] $productIds
     */
    protected function exportToFile($exportFile, array $productIds)
    {
        $this->prepareTestExportDirectory(dirname($exportFile));

        $exportFileWriter = $this->getFactory()->createExportFileWriter();
        $exportFileWriter->write($productIds, $categoryIds = [], 'file://' . $exportFile);
    }
    
    protected function saveProductIdsToTest($productIds)
    {
        file_put_contents(
            $this->getProductIdsFixtureFileName(),
            '<?php return ' . var_export($productIds, true) . ';'
        );
    }

    /**
     * @param string $directory
     */
    protected function prepareTestExportDirectory($directory)
    {
        if (!file_exists($directory)) {
            mkdir($directory, 0700, true);
        }
    }

    /**
     * @before
     */
    protected function enforceFreshEavMetadataMemoization()
    {
        MagentoIntegrationTest::reset();
    }

    /**
     * @before
     */
    protected function checkTestIsInitialized()
    {
        if (!file_exists($this->getExpectationFileName())) {
            $this->markTestSkipped('Run tests/Integration/util/initExpectedXml.php first');
        }
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Helper_Factory
     */
    public function getFactory()
    {
        return Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
    }
}
