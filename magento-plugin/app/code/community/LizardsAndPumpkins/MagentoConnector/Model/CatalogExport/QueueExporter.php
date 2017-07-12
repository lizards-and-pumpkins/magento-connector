<?php

use LizardsAndPumpkins\MagentoConnector\Api\Api;
use LizardsAndPumpkins_MagentoConnector_Model_ExportQueue as ExportQueue;
use LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue as ExportQueueResource;
use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFileWriter as ExportFileWriter;
use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFilenameGenerator as ExportFilenameGenerator;
use LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message_Collection as ExportQueueMessageCollection;

class LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_QueueExporter
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

    /**
     * @param LizardsAndPumpkins_MagentoConnector_Model_ExportQueue $exportQueue
     * @param LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFilenameGenerator $filenameGenerator
     * @param LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFileWriter $exportWriter
     * @param Api $api
     */
    public function __construct(
        $exportQueue,
        ExportFilenameGenerator $filenameGenerator = null,
        ExportFileWriter $exportWriter = null,
        Api $api = null
    ) {
        $this->exportQueue = $exportQueue ?: Mage::helper('lizardsAndPumpkins_magentoconnector/factory')->createExportQueue();
        $this->filenameGenerator = $filenameGenerator ?: Mage::getModel('lizardsAndPumpkins_magentoconnector/catalogExport_exportFilenameGenerator');
        $this->exportWriter = $exportWriter ?: Mage::helper('lizardsAndPumpkins_magentoconnector/factory')->createExportFileWriter();
        $this->api = $api ?: Mage::helper('lizardsAndPumpkins_magentoconnector/factory')->createLizardsAndPumpkinsApi();
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
            $productIds = $this->getProductIds($queueMessageCollection);
            $categoryIds = $this->getCategoryIds($queueMessageCollection);
            $this->exportProductsAndCategoriesWithVersion($productIds, $categoryIds, $targetDataVersion);
        }
    }

    /**
     * @param int[] $productIds
     * @param int[] $categoryIds
     * @param string $targetDataVersion
     */
    private function exportProductsAndCategoriesWithVersion(array $productIds, array $categoryIds, $targetDataVersion)
    {
        if (count($productIds) + count($categoryIds) === 0) {
            return;
        }
        $filename = $this->filenameGenerator->getNewFilename();
        $this->exportWriter->write($productIds, $categoryIds, $filename);
        $this->api->triggerCatalogImport(basename($filename), $targetDataVersion);
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
