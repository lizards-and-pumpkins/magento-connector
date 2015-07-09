<?php
/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

$tableName = $this->getTable('brera_magentoconnector/product_queue');

$this->getConnection()->addColumn($tableName, 'product_sku',
    [
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length' => 64,
        'nullable' => true,
        'comment' => 'Sku of Product'
    ]
);

$this->endSetup();
