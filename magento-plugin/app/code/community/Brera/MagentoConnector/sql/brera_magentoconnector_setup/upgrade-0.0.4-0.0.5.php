<?php
/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

$this->getConnection()->dropTable('brera_product_queue');

$zendQueueInstallQuery = file_get_contents('lib/Zend/Queue/Adapter/Db/mysql.sql');

$this->getConnection()->multiQuery($zendQueueInstallQuery);

$this->endSetup();
