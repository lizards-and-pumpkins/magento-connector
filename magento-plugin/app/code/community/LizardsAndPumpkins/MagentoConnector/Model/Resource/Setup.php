<?php

use LizardsAndPumpkins_MagentoConnector_Model_ExportQueue_Message as ExportQueue;
use LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message as ExportQueueResource;

class LizardsAndPumpkins_MagentoConnector_Model_Resource_Setup
{
    public function createQueueTable(Mage_Core_Model_Resource_Setup $setup, Varien_Db_Adapter_Interface $connection)
    {
        $tableName = $setup->getTable(ExportQueueResource::TABLE);
        $table = $connection->newTable($tableName);

        $table->addColumn(ExportQueueResource::ID_FIELD, Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
            'primary'  => true,
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
        ]);
        $table->addColumn(ExportQueue::TYPE, Varien_Db_Ddl_Table::TYPE_VARCHAR, 128, ['nullable' => false]);
        $table->addColumn(ExportQueue::DATA_VERSION, Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, ['nullable' => true]);
        $table->addColumn(ExportQueue::OBJECT_ID, Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
            'unsigned' => true,
            'nullable' => true,
        ]);
        $table->addColumn(ExportQueue::CREATED_AT, Varien_Db_Ddl_Table::TYPE_DATETIME, null, ['nullable' => false]);
        $table->addIndex(
            $setup->getIdxName(
                $tableName,
                [ExportQueue::TYPE, ExportQueue::DATA_VERSION, ExportQueue::OBJECT_ID],
                Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
            ),
            [ExportQueue::TYPE, ExportQueue::DATA_VERSION, ExportQueue::OBJECT_ID],
            ['type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE]
        );
        $connection->createTable($table);
    }
}
