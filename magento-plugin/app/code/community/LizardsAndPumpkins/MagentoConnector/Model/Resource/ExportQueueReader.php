<?php

use LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue as ExportQueue;
use LizardsAndPumpkins_MagentoConnector_Model_ExportQueue_Message as QueueMessage;
use LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message as QueueMessageResource;
use LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message_Collection as QueueMessageCollection;

class LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueueReader
{
    const MAX_QUEUE_MESSAGE_BATCH_SIZE = 1000;

    /**
     * @var Mage_Core_Model_Resource
     */
    private $resource;

    /**
     * @var Varien_Db_Adapter_Interface
     */
    private $connection;

    /**
     * @param Mage_Core_Model_Resource $resource
     * @param Varien_Db_Adapter_Interface $connection
     */
    public function __construct($resource = null, Varien_Db_Adapter_Interface $connection = null)
    {
        $this->resource = $resource ?: Mage::getSingleton('core/resource');
        $this->connection = $connection ?: $this->resource->getConnection('default_write');
    }
    
    /**
     * @return QueueMessageCollection[]
     */
    public function getQueuedProductUpdatesGroupedByDataVersion()
    {
        $versions = $this->getProductDataVersions();
        return $this->getQueuedItemsOfTypeGroupedByDataVersion(ExportQueue::TYPE_PRODUCT, $versions);
    }

    /**
     * @return QueueMessageCollection[]
     */
    public function getQueuedCategoryUpdatesGroupedByDataVersion()
    {
        $versions = $this->getCategoryDataVersions();
        return $this->getQueuedItemsOfTypeGroupedByDataVersion(ExportQueue::TYPE_CATEGORY, $versions);
    }

    /**
     * @return QueueMessageCollection[]
     */
    public function getQueuedCatalogUpdatesGroupedByDataVersion()
    {
        $versions = $this->getDataVersions();
        return $this->getQueuedItemsGroupedByDataVersion($versions);
    }

    /**
     * @param string $type
     * @param string[] $versions
     * @return QueueMessageCollection[]
     */
    private function getQueuedItemsOfTypeGroupedByDataVersion($type, array $versions)
    {
        $collections = [];
        foreach ($versions as $dataVersion) {
            $collection = $this->createQueuedItemTypeCollectionForDataVersion($dataVersion, $type);
            $collection->load();
            $collections[$dataVersion] = $collection;
        }
        return $collections;
    }

    /**
     * @param string $dataVersion
     * @param string $type
     * @return LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message_Collection
     */
    private function createQueuedItemTypeCollectionForDataVersion($dataVersion, $type)
    {
        $collection = $this->createQueuedItemCollectionForDataVersion($dataVersion);
        $collection->addFieldToFilter(QueueMessage::TYPE, $type);
        return $collection;
    }

    /**
     * @param string[] $versions
     * @return QueueMessageCollection[]
     */
    private function getQueuedItemsGroupedByDataVersion(array $versions)
    {
        $collections = [];
        foreach ($versions as $dataVersion) {
            $collections[$dataVersion] = $this->createQueuedItemCollectionForDataVersion($dataVersion);
        }
        return $collections;
    }

    /**
     * @param string $dataVersion
     * @return QueueMessageCollection
     */
    private function createQueuedItemCollectionForDataVersion($dataVersion)
    {
        $collection = $this->createCollection();
        $collection->addFieldToFilter(QueueMessage::DATA_VERSION, $dataVersion);
        return $collection;
    }

    /**
     * @return string
     */
    private function queueTable()
    {
        return $this->resource->getTableName('lizardsAndPumpkins_magentoconnector/queue');
    }

    /**
     * @return string[]
     */
    private function getProductDataVersions()
    {
        return $this->getDataVersionsByType(ExportQueue::TYPE_PRODUCT);
    }

    /**
     * @return string[]
     */
    private function getCategoryDataVersions()
    {
        return $this->getDataVersionsByType(ExportQueue::TYPE_CATEGORY);
    }

    /**
     * @return string[]
     */
    private function getDataVersions()
    {
        return $this->connection->fetchCol($this->getDataVersionsSelect());
    }

    /**
     * @param string $type
     * @return string[]
     */
    private function getDataVersionsByType($type)
    {
        $select = $this->getDataVersionsSelect()->where(QueueMessage::TYPE . '=?', $type);

        return $this->connection->fetchCol($select);
    }

    /**
     * @return Varien_Db_Select
     */
    private function getDataVersionsSelect()
    {
        $select = $this->connection->select();
        $select->distinct();
        $select->from($this->queueTable(), [QueueMessage::DATA_VERSION]);
        
        return $select;
    }

    /**
     * @return QueueMessageCollection
     */
    private function createCollection()
    {
        $collection = Mage::getResourceModel('lizardsAndPumpkins_magentoconnector/exportQueue_message_collection');
        $collection->setOrder(QueueMessageResource::ID_FIELD, QueueMessageCollection::SORT_ORDER_ASC);
        $collection->setPageSize(self::MAX_QUEUE_MESSAGE_BATCH_SIZE);
        $collection->setCurPage(1);

        return $collection;
    }

    /**
     * @return int
     */
    public function getProductQueueCount()
    {
        $collection = $this->createCollection();
        $collection->addFieldToFilter(QueueMessage::TYPE, ExportQueue::TYPE_PRODUCT);
        return $collection->getSize();
    }

    /**
     * @return int
     */
    public function getCategoryQueueCount()
    {
        $collection = $this->createCollection();
        $collection->addFieldToFilter(QueueMessage::TYPE, ExportQueue::TYPE_CATEGORY);
        return $collection->getSize();
    }
}
