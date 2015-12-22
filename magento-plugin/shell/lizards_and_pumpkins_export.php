<?php

use LizardsAndPumpkins\MagentoConnector\Api\Api;

require '../vendor/autoload.php';
require_once 'abstract.php';

class LizardsAndPumpkins_Export extends Mage_Shell_Abstract
{
    protected function _applyPhpVariables()
    {
        return;
    }

    public function run()
    {
        /** @var LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter $exporter */
        $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_catalogExporter');
        if ($this->getArg('all-products')) {
            $filename = $this->exportProducts($exporter);
            $this->triggerCatalogUpdateApi($filename);
        } elseif ($this->getArg('queued-products')) {
            $filename = $exporter->exportProductsInQueue();
            $this->triggerCatalogUpdateApi($filename);
        } elseif ($this->getArg('queued-categories')) {
            $filename = $exporter->exportCategoriesInQueue();
            $this->triggerCatalogUpdateApi($filename);
        } elseif ($this->getArg('all-categories')) {
            $filename = $exporter->exportAllCategories();
            $this->triggerCatalogUpdateApi($filename);
        } elseif ($this->getArg('blocks')) {
            Mage::getModel('lizardsAndPumpkins_magentoconnector/export_content')->export();
        } elseif ($this->getArg('stats')) {
            $this->outputStatistics();
        } else {
            echo $this->usageHelp();
        }
    }

    /**
     * @param string $filename
     */
    private function triggerCatalogUpdateApi($filename)
    {
        $apiUrl = Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/api_url');
        (new Api($apiUrl))->triggerProductImport($filename);
    }

    /**
     * @return string
     */
    public function usageHelp()
    {
        $filename = basename(__FILE__);

        return <<<USAGE
Usage:  php $filename -- [options]

  --all-products                Export all products
  --queued-products             Export queued products
  --all-categories              Export all categories
  --queued-categories           Export queued categories
  --stats                       Show stats about queues
  help                          This help
USAGE;
    }

    private function outputStatistics()
    {
        $stats = new LizardsAndPumpkins_MagentoConnector_Model_Statistics(Mage::getSingleton('core/resource'));
        echo sprintf('%s queued products.' . "\n", $stats->getQueuedProductCount());
        echo sprintf('%s queued categories.' . "\n", $stats->getQueuedCategoriesCount());
    }

    private function exportProducts(LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter $exporter)
    {
        if ($store = $this->getStoreFromArguments()) {
            return $exporter->exportOneStore(Mage::app()->getStore($store));
        }

        if ($website = $this->getWebsiteFromArgument()) {
            return $exporter->exportOneWebsite(Mage::app()->getWebsite($website));
        }

        return $exporter->exportAllProducts();
    }

    /**
     * @return Mage_Core_Model_Store
     */
    private function getStoreFromArguments()
    {
        try {
            $store = $this->getArg('store');
            $this->validateStore($store);
        } catch (Mage_Core_Model_Store_Exception $e) {
            die(sprintf('Store "%s" doesn\'t exist.', $store));
        }

        return $store;
    }

    /**
     * @return Mage_Core_Model_Website
     */
    private function getWebsiteFromArgument()
    {
        try {
            $website = $this->getArg('website');
            $this->validateWebsite($website);
        } catch (Mage_Core_Exception $e) {
            die($e->getMessage());
        }
        return $website;
    }

    /**
     * @param $store
     */
    private function validateStore($store)
    {
        Mage::app()->getStore($store);
    }

    /**
     * @param $website
     */
    private function validateWebsite($website)
    {
        Mage::app()->getWebsite($website);
    }
}

$exporter = new LizardsAndPumpkins_Export();
$exporter->run();
