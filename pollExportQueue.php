#!/usr/bin/env php
<?php

use LizardsAndPumpkins\MagentoConnector\Api\Api;

require __DIR__ . '/vendor/autoload.php';
require 'app/Mage.php';
Mage::app();

class PollsExportQueue
{
    private static $sleepMicroSeconds = 500000;

    private static $iterationsUntilExit = 200;

    public static function run()
    {
        $iteration = 0;
        do {
            /** @var LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter $exporter */
            $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_catalogExporter');
            $filename = $exporter->exportProductsInQueue();
            if ($exporter->wasSomethingExported()) {
                sleep(10);
                /** @var \LizardsAndPumpkins_MagentoConnector_Helper_Factory $helper */
                $helper = Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
                $helper->createLizardsAndPumpkinsApi()->triggerProductImport($filename);
            }

            usleep(self::$sleepMicroSeconds);
        } while ($iteration++ < self::$iterationsUntilExit);
    }
}

PollsExportQueue::run();
