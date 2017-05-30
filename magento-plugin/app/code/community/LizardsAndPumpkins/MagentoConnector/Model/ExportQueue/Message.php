<?php

class LizardsAndPumpkins_MagentoConnector_Model_ExportQueue_Message
    extends Mage_Core_Model_Abstract
{
    const TYPE = 'type';
    const DATA_VERSION = 'data_version';
    const OBJECT_ID = 'object_id';
    const CREATED_AT = 'created_at';
    
    protected function _construct()
    {
        $this->_init('lizardsAndPumpkins_magentoconnector/exportQueue_message');
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->setData(self::TYPE , $type);
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->_getData(self::TYPE);
    }

    /**
     * @param string $dataVersion
     */
    public function setDataVersion($dataVersion)
    {
        $this->setData(self::DATA_VERSION, $dataVersion);
    }

    /**
     * @return string|null
     */
    public function getDataVersion()
    {
        return $this->_getData(self::DATA_VERSION);
    }

    /**
     * @param int $objectId
     */
    public function setObjectId($objectId)
    {
        $this->setData(self::OBJECT_ID, $objectId);
    }

    /**
     * @return int|null
     */
    public function getObjectId()
    {
        $objectId = $this->_getData(self::OBJECT_ID);

        return isset($objectId) ? (int) $objectId : null;
    }

    /**
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->_getData('created_at');
    }

    protected function _beforeSave()
    {
        if ($this->isObjectNew()) {
            $this->setData(self::CREATED_AT, date('Y-m-d H:i:s'));
        }
        return parent::_beforeSave();
    }

}
