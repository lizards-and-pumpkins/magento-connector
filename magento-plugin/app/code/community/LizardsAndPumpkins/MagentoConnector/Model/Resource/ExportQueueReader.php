<?php

use LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue as ExportQueue;
use LizardsAndPumpkins_MagentoConnector_Model_ExportQueue_Message as QueueMessage;
use LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message_Collection as QueueMessageCollection;

class LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueueReader
{
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

    private function getQueuedItemsOfTypeGroupedByDataVersion($type, $versions)
    {
        $collections = [];
        foreach ($versions as $dataVersion) {
            $collections[$dataVersion] = $this->createQueuedItemCollectionForDataVersion($dataVersion, $type);
        }
        return $collections;
    }

    /**
     * @param string $dataVersion
     * @param string $type
     * @return LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message_Collection
     */
    private function createQueuedItemCollectionForDataVersion($dataVersion, $type)
    {
        $collection = $this->createCollection();
        $collection->addFieldToFilter(QueueMessage::DATA_VERSION, $dataVersion);
        $collection->addFieldToFilter(QueueMessage::TYPE, $type);
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
     * @param string $type
     * @return string[]
     */
    private function getDataVersionsByType($type)
    {
        $select = $this->connection->select();
        $select->distinct();
        $select->from($this->queueTable(), [QueueMessage::DATA_VERSION]);
        $select->where(QueueMessage::TYPE . '=?', $type);

        return $this->connection->fetchCol($select);
    }

    /**
     * @return QueueMessageCollection
     */
    private function createCollection()
    {
        return Mage::getResourceModel('lizardsAndPumpkins_magentoconnector/exportQueue_message_collection');
    }

    public function getProductQueueCount()
    {
        $collection = $this->createCollection();
        $collection->addFieldToFilter(QueueMessage::TYPE, ExportQueue::TYPE_PRODUCT);
        return $collection->getSize();
    }

    public function getCategoryQueueCount()
    {
        $collection = $this->createCollection();
        $collection->addFieldToFilter(QueueMessage::TYPE, ExportQueue::TYPE_CATEGORY);
        return $collection->getSize();
    }
}
