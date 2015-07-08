<?php
/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

$tableName = $this->getTable('brera_magentoconnector/product_queue');

$this->getConnection()->addIndex(
    $tableName,
    $this->getIdxName($tableName, ['product_id', 'action'], Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE),
    ['product_id', 'action'],
    Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
);

$this->endSetup();
