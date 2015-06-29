<?php
/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

$tableName = $this->getTable('brera_magentoconnector/product_queue');

$table = $this->getConnection()
    ->newTable($tableName)
    ->addColumn(
        'product_queue_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary' => true,
        ),
        'Order of inserted product ids'
    )->addColumn(
        'product_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'unsigned' => true,
            'nullable' => false,
        ),
        'Product ID'
    )->addColumn(
        'action',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        10,
        array(),
        'Type of action: update/create, delete'
    )->addForeignKey(
        $this->getConnection()->getForeignKeyName(
            $tableName,
            'product_id',
            $this->getTable('catalog/product'),
            'entity_id'
        ),
        'product_id',
        $this->getTable('catalog/product'),
        'entity_id'
    );

$this->getConnection()->createTable($table);

$this->endSetup();
