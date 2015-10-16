<?php
/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

$this->getConnection()->addIndex(
    'message',
    $this->getIdxName(
        'message',
        array('queue_id', 'md5'),
        Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
    ),
    array('queue_id', 'md5'),
    Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
);

$this->endSetup();
