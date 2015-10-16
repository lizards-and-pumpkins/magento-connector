<?php

class Brera_MagentoConnector_Helper_Data extends Mage_Core_Helper_Abstract
{
    const QUEUE_NAME = "lizardsAndPumpkins";
    protected $_queue = null;


    /**
     * @return Zend_Queue_Adapter_Db
     */
    public function getQueue()
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
}
