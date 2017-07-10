<?php

use LizardsAndPumpkins\MagentoConnector\Api\Api as LizardsAndPumpkinsApi;
use LizardsAndPumpkins_MagentoConnector_Helper_DataVersion as DataVersion;
use LizardsAndPumpkins_MagentoConnector_Model_Resource_CatalogExport_CatalogEntityIdCollector as EntityIdCollector;
use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFilenameGenerator as ExportFilenameGenerator;
use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFileWriter as ExportFileWriter;

class LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_CompleteCatalogExporter
{
    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFileWriter
     */
    private $exportFileWriter;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Resource_CatalogExport_CatalogEntityIdCollector
     */
    private $entityIdCollector;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFilenameGenerator
     */
    private $exportFilenameGenerator;

    /**
     * @var LizardsAndPumpkinsApi
     */
    private $api;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Helper_DataVersion
     */
    private $dataVersion;

    /**
     * @param ExportFileWriter $exportFileWriter
     * @param EntityIdCollector $entityIdCollector
     * @param ExportFilenameGenerator $exportFilenameGenerator
     * @param DataVersion $dataVersion
     * @param LizardsAndPumpkinsApi $api
     */
    public function __construct(
        $exportFileWriter,
        EntityIdCollector $entityIdCollector = null,
        ExportFilenameGenerator $exportFilenameGenerator = null,
        DataVersion $dataVersion = null,
        LizardsAndPumpkinsApi $api = null
    ) {
        $this->exportFileWriter = $exportFileWriter ? $exportFileWriter : Mage::helper('lizardsAndPumpkins_magentoconnector/factory')->createExportFileWriter();
        $this->entityIdCollector = $entityIdCollector ? $entityIdCollector : Mage::getModel('lizardsAndPumpkins_magentoconnector/resource_catalogExport_catalogEntityIdCollector');
        $this->exportFilenameGenerator = $exportFilenameGenerator ? $exportFilenameGenerator : Mage::getModel('lizardsAndPumpkins_magentoconnector/catalogExport_exportFilenameGenerator');
        $this->dataVersion = $dataVersion ? $dataVersion : Mage::helper('lizardsAndPumpkins_magentoconnector/dataVersion');
        $this->api = $api ? $api : Mage::helper('lizardsAndPumpkins_magentoconnector/factory')->createLizardsAndPumpkinsApi();
    }

    public function exportAllProducts()
    {
        $this->export($this->entityIdCollector->getAllProductIds(), $categoryIds = []);
    }

    public function exportAllCategories()
    {
        $this->export($productIds = [], $this->entityIdCollector->getAllCategoryIds());
    }

    /**
     * @param string $websiteCode
     */
    public function exportProductsForWebsite($websiteCode)
    {
        $this->export($this->entityIdCollector->getProductIdsForWebsite($websiteCode), $categoryIds = []);
    }

    /**
     * @param string $websiteCode
     */
    public function exportCategoriesForWebsite($websiteCode)
    {
        $this->export($productIds = [], $this->entityIdCollector->getCategoryIdsForWebsite($websiteCode));
    }

    /**
     * @param int[] $productIds
     * @param int[] $categoryIds
     */
    private function export(array $productIds, array $categoryIds)
    {
        $filename = $this->exportFilenameGenerator->getNewFilename();
        $this->exportFileWriter->write($productIds, $categoryIds, $filename);
        $this->api->triggerCatalogImport(basename($filename), $this->dataVersion->getTargetVersion());
    }
}
