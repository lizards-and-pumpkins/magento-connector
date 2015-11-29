<?php

class Brera_MagentoConnector_Helper_Export
{
    const QUEUE_STOCK_UPDATES = "stockUpdates";
    const QUEUE_PRODUCT_UPDATES = "productUpdates";

    const MYSQL_DUPLICATE_ENTRY_ERROR_NUMBER = 23000;

    const MAX_MESSAGES = 500;
    const TIMEOUT = 30;
    private $connection;

    /**
     * @var Zend_Queue[]
     */
    protected $_queues = [];

    public function __construct()
    {
        $resource = Mage::getSingleton('core/resource');
        $this->connection = $resource->getConnection('core_write');
    }


    public function addAllProductIdsToProductUpdateQueue()
    {
        $queueId = $this->getQueueIdByName(self::QUEUE_PRODUCT_UPDATES);
        $time = time();

        $query = <<<SQL
INSERT IGNORE INTO `message`
  (queue_id, created, body, md5)
  (SELECT $queueId, $time, entity_id, MD5(entity_id) FROM `catalog_product_entity`)
SQL;

        $this->connection->query($query)->execute();
    }

    /**
     * @param Mage_Core_Model_Website $website
     */
    public function addAllProductIdsFromWebsiteToProductUpdateQueue(Mage_Core_Model_Website $website)
    {
        $queueId = $this->getQueueIdByName(self::QUEUE_PRODUCT_UPDATES);
        $time = time();

        $productToWebsiteTable = $this->connection->getTableName('catalog/product_website');
        $productTable = $this->connection->getTableName('catalog/product');

        $query = <<<SQL
INSERT IGNORE INTO `message`
  (queue_id, created, body, md5)
  (
    SELECT $queueId, $time, entity_id, MD5(entity_id) FROM $productTable p
    INNER JOIN  $productToWebsiteTable p2w ON p.entity_id = p2w.product_id
    WHERE p2w = {$website->getId()}
  )
SQL;

        $this->connection->query($query)->execute();
    }

    /**
     * @param string $queueName
     *
     * @return Zend_Queue
     * @throws Zend_Queue_Exception
     */
    private function getQueue($queueName)
    {
        if (!isset($this->_queues[$queueName])) {
            $config = (array)Mage::getConfig()->getResourceConnectionConfig("default_setup");

            $queueOptions = [
                Zend_Queue::NAME => $queueName,
                'driverOptions'  => $config + [
                        Zend_Queue::TIMEOUT            => 1,
                        Zend_Queue::VISIBILITY_TIMEOUT => 1
                    ]
            ];

            $this->_queues[$queueName] = new Zend_Queue('Db', $queueOptions);
            $this->_queues[$queueName]->createQueue($queueName);
        }
        return $this->_queues[$queueName];
    }

    /**
     * @param int[] $ids
     *
     * @throws Zend_Queue_Exception
     */
    public function addStockUpdatesToQueue(array $ids)
    {
        foreach ($ids as $id) {
            $this->addStockUpdateToQueue($id);
        }
    }

    /**
     * @param int $id
     *
     * @throws Zend_Queue_Exception
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
        foreach ($ids as $id) {
            $this->addProductUpdateToQueue($id);
        }
    }

    /**
     * @param int $id
     *
     * @throws Zend_Queue_Exception
     */
    private function addProductUpdateToQueue($id)
    {
        $this->addToQueue($id, self::QUEUE_PRODUCT_UPDATES);
    }

    public function addAllProductIdsToStockExport()
    {
        /** @var int[] $ids */
        $ids = Mage::getResourceModel('catalog/product_collection')->getAllIds();
        foreach ($ids as $id) {
            $this->addStockUpdateToQueue($id);
        }
    }

    /**
     * @return Zend_Queue_Message_Iterator
     * @throws Zend_Queue_Exception
     */
    public function getStockUpdatesToExport()
    {
        $queue = $this->getQueue(self::QUEUE_STOCK_UPDATES);
        return $queue->receive(self::MAX_MESSAGES, self::TIMEOUT);
    }

    /**
     * @return Zend_Queue_Message_Iterator
     * @throws Zend_Queue_Exception
     */
    public function getProductUpdatesToExport()
    {
        $queue = $this->getQueue(self::QUEUE_PRODUCT_UPDATES);
        return $queue->receive(self::MAX_MESSAGES, self::TIMEOUT);
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
     * @param string               $queueName
     */
    private function deleteMessages(array $messages, $queueName)
    {
        $queue = $this->getQueue($queueName);
        foreach ($messages as $message) {
            $queue->deleteMessage($message);
        }
    }

    /**
     * @param int    $id
     * @param string $queue
     *
     * @throws Zend_Queue_Exception
     */
    private function addToQueue($id, $queue)
    {
        $queue = $this->getQueue($queue);
        try {
            $queue->send($id);
        } catch (Zend_Queue_Exception $e) {
            if ($e->getCode() == self::MYSQL_DUPLICATE_ENTRY_ERROR_NUMBER) {
                // do nothing
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param string $queueName
     *
     * @return string
     */
    private function getQueueIdByName($queueName)
    {
        $query = "SELECT queue_id FROM queue WHERE queue_name = :queueName";

        $result = $this->connection->query($query, [':queueName' => $queueName]);
        $queueId = $result->fetchColumn();
        if (!$queueId) {
            Mage::throwException('Queue not found.');
        }
        return $queueId;
    }
}
