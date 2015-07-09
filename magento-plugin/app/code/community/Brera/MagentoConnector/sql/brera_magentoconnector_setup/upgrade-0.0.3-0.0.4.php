<?php
/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

$tableName = $this->getTable('brera_magentoconnector/product_queue');

$this->getConnection()->changeColumn($tableName, 'product_id', 'product_id',
    [
        'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'unsigned' => true,
        'nullable' => true,
        'comment' => 'Product ID',
    ]
);

$this->getConnection()->addForeignKey(
    $this->getIdxName(
        $tableName,
        [
            'product_sku'
        ]
    ),
    $tableName,
    'product_sku',
    'catalog/product',
    'sku'
);

$this->endSetup();
