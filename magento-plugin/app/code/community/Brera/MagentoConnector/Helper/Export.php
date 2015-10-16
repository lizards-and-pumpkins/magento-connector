<?php

class Brera_MagentoConnector_Helper_Export
{
    const QUEUE_STOCK_UPDATES = "stockUpdates";
    const QUEUE_PRODUCT_UPDATES = "productUpdates";

    const MYSQL_DUPLICATE_ENTRY_ERROR_NUMBER = 23000;

    const MAX_MESSAGES = 10;
    const TIMEOUT = 30;
    /**
     * @var Zend_Queue[]
     */
    protected $_queues = array();


    /**
     * @param string $queueName
     * @return Zend_Queue
     * @throws Zend_Queue_Exception
     */
    private function getQueue($queueName)
    {
        if (!isset($this->_queues[$queueName])) {
            $config = (array)Mage::getConfig()->getResourceConnectionConfig("default_setup");

            $queueOptions = array(
                Zend_Queue::NAME => $queueName,
                'driverOptions' => $config + array(
                        Zend_Queue::TIMEOUT => 1,
                        Zend_Queue::VISIBILITY_TIMEOUT => 1
                    )
            );

            $this->_queues[$queueName] = new Zend_Queue('Db', $queueOptions);
            $this->_queues[$queueName]->createQueue($queueName);
        }
        return $this->_queues[$queueName];
    }

    private function addStockUpdateToQueue($id)
    {
        $queue = $this->getQueue(self::QUEUE_STOCK_UPDATES);
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

    public function addAllProductIdsToStockExport()
    {
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
        $queue = $this->getQueue($queueName);
        foreach ($messages as $message) {
            $queue->deleteMessage($message);
        }
    }
}
