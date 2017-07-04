#!/usr/bin/env php
<?php

require 'app/Mage.php';
Mage::app();

class PollsExportQueue
{
    private static $iterationsUntilExit = 200;

    public static function run()
    {
        $iteration = 0;
        do {
            /** @var LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_Exporter $exporter */
            $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/catalogExport_exporter');
            $exporter->exportQueuedProductsAndCategories();
            sleep(10);
        } while ($iteration++ < self::$iterationsUntilExit);
    }
}

PollsExportQueue::run();
