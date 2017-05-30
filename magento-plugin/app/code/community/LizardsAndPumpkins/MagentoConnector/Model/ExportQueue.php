<?php

use LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message_Collection as ExportQueueMessageCollection;

class LizardsAndPumpkins_MagentoConnector_Model_ExportQueue
{
    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue
     */
    private $resourceModel;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueueReader
     */
    private $exportQueueReader;

    /**
     * @param LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue $exportQueue
     * @param LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueueReader $exportQueueReader
     */
    public function __construct($exportQueue = null, $exportQueueReader = null)
    {
        $this->resourceModel = $exportQueue ?: Mage::getResourceModel('lizardsAndPumpkins_magentoconnector/exportQueue');
        $this->exportQueueReader = $exportQueueReader ?: Mage::getResourceModel('lizardsAndPumpkins_magentoconnector/exportQueueReader');
    }

    /**
     * @param string $targetDataVersion
     */
    public function addAllProductIdsToProductUpdateQueue($targetDataVersion)
    {
        $this->resourceModel->addAllProductIdsToProductUpdateQueue((string) $targetDataVersion);
    }

    /**
     * @param Mage_Core_Model_Website $website
     * @param string $targetDataVersion
     */
    public function addAllProductIdsFromWebsiteToProductUpdateQueue(
        Mage_Core_Model_Website $website,
        $targetDataVersion
    ) {
        $this->resourceModel->addAllProductIdsFromWebsiteToProductUpdateQueue(
            (int) $website->getId(),
            (string) $targetDataVersion
        );
    }

    /**
     * @param int[] $productIds
     * @param string $targetDataVersion
     */
    public function addProductUpdatesToQueue(array $productIds, $targetDataVersion)
    {
        $this->resourceModel->addProductUpdatesToQueue($productIds, (string) $targetDataVersion);
    }

    /**
     * @param int $productId
     * @param string $targetDataVersion
     */
    public function addProductUpdateToQueue($productId, $targetDataVersion)
    {
        $this->resourceModel->addProductUpdateToQueue((int) $productId, (string) $targetDataVersion);
    }

    /**
     * @param string $targetDataVersion
     */
    public function addAllCategoryIdsToCategoryQueue($targetDataVersion)
    {
        $this->resourceModel->addAllCategoryIdsToCategoryQueue((string) $targetDataVersion);
    }

    /**
     * @param int $categoryId
     * @param string $targetDataVersion
     */
    public function addCategoryToQueue($categoryId, $targetDataVersion)
    {
        $this->resourceModel->addCategoryToQueue((int) $categoryId, (string) $targetDataVersion);
    }

    /**
     * @return ExportQueueMessageCollection[]
     */
    public function getQueuedProductUpdatesGroupedByDataVersion()
    {
        return $this->exportQueueReader->getQueuedProductUpdatesGroupedByDataVersion();
    }

    /**
     * @return ExportQueueMessageCollection[]
     */
    public function popQueuedProductUpdatesGroupedByDataVersion()
    {
        $productUpdatesGroupedByDataVersion = $this->getQueuedProductUpdatesGroupedByDataVersion();

        $this->removeMessages(...$productUpdatesGroupedByDataVersion);
        
        return $productUpdatesGroupedByDataVersion;
    }

    public function popQueuedCategoryUpdatesGroupedByDataVersion()
    {
        $categoryUpdatesGroupedByDataVersion = $this->getQueuedCategoryUpdatesGroupedByDataVersion();

        $this->removeMessages(...$categoryUpdatesGroupedByDataVersion);

        return $categoryUpdatesGroupedByDataVersion;
    }

    /**
     * @return ExportQueueMessageCollection[]
     */
    public function getQueuedCategoryUpdatesGroupedByDataVersion()
    {
        return $this->exportQueueReader->getQueuedCategoryUpdatesGroupedByDataVersion();
    }

    /**
     * @return int
     */
    public function getProductQueueCount()
    {
        return $this->exportQueueReader->getProductQueueCount();
    }

    /**
     * @return int
     */
    public function getCategoryQueueCount()
    {
        return $this->exportQueueReader->getCategoryQueueCount();
    }

    private function removeMessages(ExportQueueMessageCollection ...$queueMessageCollections)
    {
        $allMessageIds = array_merge(...array_map(function (ExportQueueMessageCollection $collection) {
            return $collection->getAllIds();
        }, $queueMessageCollections));

        $this->resourceModel->removeMessages($allMessageIds);
    }
}
