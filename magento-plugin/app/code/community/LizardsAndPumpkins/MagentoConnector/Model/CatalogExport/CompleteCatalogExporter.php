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

    public function __construct(
        ExportFileWriter $exportFileWriter,
        EntityIdCollector $entityIdCollector,
        ExportFilenameGenerator $exportFilenameGenerator,
        DataVersion $dataVersion,
        LizardsAndPumpkinsApi $api
    ) {
        $this->exportFileWriter = $exportFileWriter;
        $this->entityIdCollector = $entityIdCollector;
        $this->exportFilenameGenerator = $exportFilenameGenerator;
        $this->dataVersion = $dataVersion;
        $this->api = $api;
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
        $this->api->triggerCatalogImport($filename, $this->dataVersion->getTargetVersion());
    }
}
