<?php

use LizardsAndPumpkins\MagentoConnector\Api\Api;
use LizardsAndPumpkins_MagentoConnector_Model_ExportQueue as ExportQueue;
use LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue as ExportQueueResource;
use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFileWriter as ExportFileWriter;
use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFilenameGenerator as ExportFilenameGenerator;
use LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message_Collection as ExportQueueMessageCollection;

class LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_Exporter
{
    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_ExportQueue
     */
    private $exportQueue;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFileWriter
     */
    private $exportWriter;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFilenameGenerator
     */
    private $filenameGenerator;

    public function __construct(
        ExportQueue $exportQueue,
        ExportFilenameGenerator $filenameGenerator,
        ExportFileWriter $exportWriter,
        Api $api
    ) {
        $this->exportQueue = $exportQueue;
        $this->filenameGenerator = $filenameGenerator;
        $this->exportWriter = $exportWriter;
        $this->api = $api;
    }

    public function exportQueuedProducts()
    {
        $productUpdatesGroupedByDataVersion = $this->exportQueue->popQueuedProductUpdatesGroupedByDataVersion();
        $this->processExportQueueMessagesGroupedByDataVersion($productUpdatesGroupedByDataVersion);
    }

    public function exportQueuedCategories()
    {
        $categoryUpdatesGroupedByDataVersion = $this->exportQueue->popQueuedCategoryUpdatesGroupedByDataVersion();
        $this->processExportQueueMessagesGroupedByDataVersion($categoryUpdatesGroupedByDataVersion);
    }

    public function exportQueuedProductsAndCategories()
    {
        $catalogUpdatesGroupedByDataVersion = $this->exportQueue->popQueuedUpdatesGroupedByDataVersion();
        $this->processExportQueueMessagesGroupedByDataVersion($catalogUpdatesGroupedByDataVersion);
    }

    /**
     * @param ExportQueueMessageCollection[] $exportMessageCollections
     */
    private function processExportQueueMessagesGroupedByDataVersion(array $exportMessageCollections)
    {
        foreach ($exportMessageCollections as $targetDataVersion => $queueMessageCollection) {
            $filename = $this->filenameGenerator->getNewFilename();
            $this->exportWriter->write(
                $this->getProductIds($queueMessageCollection),
                $this->getCategoryIds($queueMessageCollection),
                $filename
            );
            $this->api->triggerCatalogImport($filename, $targetDataVersion);
        }
    }

    /**
     * @param ExportQueueMessageCollection $queueMessageCollection
     * @return int[]
     */
    private function getProductIds(ExportQueueMessageCollection $queueMessageCollection)
    {
        return $queueMessageCollection->getObjectIdsByType(ExportQueueResource::TYPE_PRODUCT);
    }

    /**
     * @param ExportQueueMessageCollection $queueMessageCollection
     * @return int[]
     */
    private function getCategoryIds(ExportQueueMessageCollection $queueMessageCollection)
    {
        return $queueMessageCollection->getObjectIdsByType(ExportQueueResource::TYPE_CATEGORY);
    }
}
