<?php

class Brera_MagentoConnector_Helper_Data extends Mage_Core_Helper_Abstract
{
    const QUEUE_NAME = "lizardsAndPumpkins";
    const QUEUE_STOCK_UPDATES = "stockUpdates";
    const QUEUE_PRODUCT_UPDATES = "productUpdates";
    protected $_queue = null;
    const MYSQL_DUPLICATE_ENTRY_ERROR_NUMBER = 23000;


    /**
     * @return Zend_Queue
     */
    private function getQueue()
    {
        if (!$this->_queue) {
            $config = (array)Mage::getConfig()->getResourceConnectionConfig("default_setup");

            $queueOptions = array(
                Zend_Queue::NAME => self::QUEUE_NAME,
                'driverOptions' => $config + array(
                        Zend_Queue::TIMEOUT => 1,
                        Zend_Queue::VISIBILITY_TIMEOUT => 1
                    )
            );

            $this->_queue = new Zend_Queue('Db', $queueOptions);
        }
        return $this->_queue;
    }

    public function addStockUpdateToQueue($id)
    {
        $queue = $this->getQueue();
        $queue->createQueue(Brera_MagentoConnector_Helper_Data::QUEUE_STOCK_UPDATES);
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
}
