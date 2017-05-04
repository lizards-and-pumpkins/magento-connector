<?php

/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

$this->getConnection()->dropTable('lizardsAndPumpkins_product_queue');

$zendQueueInstallQuery = file_get_contents(Mage::getBaseDir('base') . '/lib/Zend/Queue/Adapter/Db/mysql.sql');

$this->getConnection()->multiQuery($zendQueueInstallQuery);

$this->endSetup();
