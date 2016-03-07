<?php

class LizardsAndPumpkins_MagentoConnector_Helper_Export
    implements LizardsAndPumpkins_MagentoConnector_Helper_ProductsToUpdateQueueReader
{
    const QUEUE_STOCK_UPDATES = "stockUpdates";
    const QUEUE_PRODUCT_UPDATES = "productUpdates";
    const QUEUE_CATEGORY_UPDATES = 'categoryUpdates';

    const ALL_QUEUES = [
        self::QUEUE_PRODUCT_UPDATES,
        self::QUEUE_STOCK_UPDATES,
        self::QUEUE_CATEGORY_UPDATES,
    ];

    const MYSQL_DUPLICATE_ENTRY_ERROR_NUMBER = 23000;

    const QUEUE_MESSAGES_FETCHED_PER_REQUEST = 500;

    const TIMEOUT = 30;

    /**
     * @var Varien_Db_Adapter_Interface
     */
    private $connection;

    /**
     * @var Mage_Core_Model_Resource
     */
    private $resource;

    /**
     * @var Zend_Queue[]
     */
    private $_queues = [];

    public function __construct()
    {
        $this->resource = Mage::getSingleton('core/resource');
        $this->connection = $this->resource->getConnection('core_write');
        foreach (self::ALL_QUEUES as $queueName) {
            $this->getQueue($queueName);
        }
    }

    public function addAllProductIdsToProductUpdateQueue()
    {
        $queueId = $this->getQueueIdByName(self::QUEUE_PRODUCT_UPDATES);
        $query = <<<SQL
INSERT IGNORE INTO `message`
  (queue_id, created, body, md5)
  (SELECT $queueId, UNIX_TIMESTAMP(), entity_id, MD5(entity_id) FROM `catalog_product_entity`)
SQL;

        $this->connection->query($query)->execute();
    }

    public function addAllCategoryIdsToCategoryQueue()
    {
        $queueId = $this->getQueueIdByName(self::QUEUE_CATEGORY_UPDATES);

        $query = <<<SQL
INSERT IGNORE INTO `message`
  (queue_id, created, body, md5)
  (SELECT $queueId, UNIX_TIMESTAMP(), entity_id, MD5(entity_id) FROM `catalog_category_entity`)
SQL;

        $this->connection->query($query)->execute();
    }

    /**
     * @param Mage_Core_Model_Website $website
     */
    public function addAllProductIdsFromWebsiteToProductUpdateQueue(Mage_Core_Model_Website $website)
    {
        $queueId = $this->getQueueIdByName(self::QUEUE_PRODUCT_UPDATES);
        $productToWebsiteTable = $this->resource->getTableName('catalog/product_website');
        $productTable = $this->resource->getTableName('catalog/product');

        $query = <<<SQL
INSERT IGNORE INTO `message`
  (queue_id, created, body, md5)
  (
    SELECT $queueId, UNIX_TIMESTAMP(), entity_id, MD5(entity_id) FROM $productTable p
    INNER JOIN  $productToWebsiteTable p2w ON p.entity_id = p2w.product_id
    WHERE p2w.website_id = {$website->getId()}
  )
SQL;

        $this->connection->query($query)->execute();
    }

    /**
     * @param string $queueName
     * @return Zend_Queue
     */
    private function getQueue($queueName)
    {
        if (!isset($this->_queues[$queueName])) {
            $config = (array) Mage::getConfig()->getResourceConnectionConfig("default_setup");

            $queueOptions = [
                Zend_Queue::NAME => $queueName,
                'driverOptions'  => $config + [
                        Zend_Queue::TIMEOUT            => 1,
                        Zend_Queue::VISIBILITY_TIMEOUT => 1,
                    ],
            ];

            $this->_queues[$queueName] = new Zend_Queue('Db', $queueOptions);
            $this->_queues[$queueName]->createQueue($queueName);
        }
        return $this->_queues[$queueName];
    }

    /**
     * @param int[] $ids
     */
    public function addStockUpdatesToQueue(array $ids)
    {
        array_map([$this, 'addStockUpdateToQueue'], $ids);
    }

    /**
     * @param int $id
     */
    private function addStockUpdateToQueue($id)
    {
        $this->addToQueue($id, self::QUEUE_STOCK_UPDATES);
    }

    /**
     * @param int[] $ids
     */
    public function addProductUpdatesToQueue(array $ids)
    {
        array_map([$this, 'addProductUpdateToQueue'], $ids);
    }

    /**
     * @param int $id
     */
    private function addProductUpdateToQueue($id)
    {
        $this->addToQueue($id, self::QUEUE_PRODUCT_UPDATES);
    }

    /**
     * @param int $id
     */
    public function addCategoryToQueue($id)
    {
        $this->addToQueue($id, self::QUEUE_CATEGORY_UPDATES);
    }

    public function addAllProductIdsToStockExport()
    {
        array_map([$this, 'addStockUpdateToQueue'], $this->getAllProductIds());
    }

    /**
     * @return Zend_Queue_Message_Iterator
     */
    public function getStockUpdatesToExport()
    {
        $queue = $this->getQueue(self::QUEUE_STOCK_UPDATES);
        return $queue->receive(self::QUEUE_MESSAGES_FETCHED_PER_REQUEST, self::TIMEOUT);
    }

    /**
     * @return Zend_Queue_Message_Iterator
     */
    public function getProductUpdatesToExport()
    {
        $queue = $this->getQueue(self::QUEUE_PRODUCT_UPDATES);
        return $queue->receive(self::QUEUE_MESSAGES_FETCHED_PER_REQUEST, self::TIMEOUT);
    }

    /**
     * @return Zend_Queue_Message_Iterator
     */
    public function getCategoryUpdatesToExport()
    {
        $queue = $this->getQueue(self::QUEUE_CATEGORY_UPDATES);
        return $queue->receive(self::QUEUE_MESSAGES_FETCHED_PER_REQUEST, self::TIMEOUT);
    }

    /**
     * @param Zend_Queue_Message[] $messages
     */
    public function deleteStockMessages(array $messages)
    {
        $this->deleteMessages($messages, self::QUEUE_STOCK_UPDATES);
    }

    /**
     * @param Zend_Queue_Message[] $messages
     * @param string $queueName
     */
    private function deleteMessages(array $messages, $queueName)
    {
        array_map([$this->getQueue($queueName), 'deleteMessage'], $messages);
    }

    /**
     * @param int $id
     * @param string $queueName
     */
    private function addToQueue($id, $queueName)
    {
        $queue = $this->getQueue($queueName);
        try {
            $queue->send($id);
        } catch (Zend_Queue_Exception $e) {
            if ($e->getCode() != self::MYSQL_DUPLICATE_ENTRY_ERROR_NUMBER) {
                throw $e;
            }
        }
    }

    /**
     * @param string $queueName
     * @return string
     */
    public function getQueueIdByName($queueName)
    {
        $query = "SELECT queue_id FROM queue WHERE queue_name = :queueName";

        $queueId = $this->connection->fetchOne($query, [':queueName' => $queueName]);
        if (!$queueId) {
            Mage::throwException(sprintf('Queue "%s" not found.', $queueName));
        }
        return $queueId;
    }

    /**
     * @return string[]
     */
    public function getQueuedProductIds()
    {
        $messageIterator = $this->getProductUpdatesToExport();
        $productIds = [];
        foreach ($messageIterator as $item) {
            /** @var $item Zend_Queue_Message */
            $productIds[] = (string) $item->body;
        }
        if ($productIds) {
            $this->deleteMessages(iterator_to_array($messageIterator), self::QUEUE_PRODUCT_UPDATES);
        }

        return $productIds;
    }

    /**
     * @return int[]
     */
    private function getAllProductIds()
    {
        $collection = Mage::getResourceModel('catalog/product_collection');
        $select = $collection->getSelect()->reset(Zend_Db_Select::COLUMNS)->columns(['entity_id']);
        return $collection->getConnection()->fetchCol($select);
    }
}
