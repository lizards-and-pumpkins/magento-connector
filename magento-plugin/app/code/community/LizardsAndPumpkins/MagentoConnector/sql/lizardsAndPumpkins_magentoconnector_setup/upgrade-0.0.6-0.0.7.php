<?php

/** @var Mage_Core_Model_Resource_Setup $this */

$this->startSetup();

$setup = new LizardsAndPumpkins_MagentoConnector_Model_Resource_Setup();
$setup->createQueueTable($this, $this->getConnection());

$this->endSetup();
