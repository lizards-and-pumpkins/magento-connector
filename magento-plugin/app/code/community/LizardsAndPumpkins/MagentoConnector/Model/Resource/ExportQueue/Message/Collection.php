<?php

use LizardsAndPumpkins_MagentoConnector_Model_ExportQueue_Message as ExportQueueMessage;

class LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * @var string
     */
    private $messageType;
    
    protected function _construct()
    {
        $this->_init('lizardsAndPumpkins_magentoconnector/exportQueue_message');
    }
    
    /**
     * @param string $type
     */
    private function setMessageType($type)
    {
        if (null !== $this->messageType) {
            $message = sprintf('The queue message type is already set to "%s"', $this->messageType);
            throw new \LogicException($message);
        }
        $this->messageType = (string) $type;
    }

    /**
     * @return string|null
     */
    public function getMessageType()
    {
        return $this->messageType;
    }

    /**
     * @param string|mixed[] $field
     * @param mixed $condition
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if (ExportQueueMessage::TYPE === $field) {
            $this->setMessageType($condition);
        }
        return parent::addFieldToFilter($field, $condition);
    }
    
    /**
     * @param string $type
     * @return int[]
     */
    public function getObjectIdsByType($type)
    {
        $messagesMatchingType = array_filter($this->getItems(), function (ExportQueueMessage $message) use ($type) {
            return $message->getType() === $type;
        });
        return array_values(array_map(function (ExportQueueMessage $message) {
            return (int) $message->getObjectId();
        }, $messagesMatchingType));
    }
}
