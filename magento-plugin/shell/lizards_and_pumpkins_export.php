<?php

require 'abstract.php';

class LizardsAndPumpkins_Export extends Mage_Shell_Abstract
{
    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter
     */
    private $catalogExporter;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Export_Content
     */
    private $contentExporter;

    public function __construct()
    {
        parent::__construct();
        $this->contentExporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_content');
        $this->catalogExporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_catalogExporter');
    }

    protected function _applyPhpVariables()
    {
        return;
    }

    public function run()
    {
        /** @var LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter $exporter */
        if ($this->getArg('all-products')) {
            $filename = $this->exportProducts();
            $this->triggerCatalogUpdateApiIfSomethingWasExported($filename);
        } elseif ($this->getArg('queued-products')) {
            $filename = $this->catalogExporter->exportProductsInQueue();
            $this->triggerCatalogUpdateApiIfSomethingWasExported($filename);
        } elseif ($this->getArg('queued-categories')) {
            $filename = $this->catalogExporter->exportCategoriesInQueue();
            $this->triggerCatalogUpdateApiIfSomethingWasExported($filename);
        } elseif ($this->getArg('all-categories')) {
            $filename = $this->catalogExporter->exportAllCategories();
            $this->triggerCatalogUpdateApiIfSomethingWasExported($filename);
        } elseif ($this->getArg('blocks')) {
            $this->contentExporter->export();
        } elseif ($this->getArg('stats')) {
            $this->outputStatistics();
        } else {
            echo $this->usageHelp();
        }
    }

    /**
     * @param string $filename
     */
    private function triggerCatalogUpdateApiIfSomethingWasExported($filename)
    {
        if (!$this->catalogExporter->wasSomethingExported()) {
            return;
        }
        /** @var \LizardsAndPumpkins_MagentoConnector_Helper_Factory $helper */
        $helper = Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
        $helper->createLizardsAndPumpkinsApi()->triggerProductImport($filename);
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
  --blocks                      Export cms and non-cms blocks
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

    /**
     * @param string|int $store
     */
    private function validateStore($store)
    {
        Mage::app()->getStore($store);
    }

    /**
     * @param string|int $website
     */
    private function validateWebsite($website)
    {
        Mage::app()->getWebsite($website);
    }

    /**
     * @return string
     */
    private function exportProducts()
    {
        if ($store = $this->getStoreFromArguments()) {
            return $this->catalogExporter->exportOneStore(Mage::app()->getStore($store));
        }

        if ($website = $this->getWebsiteFromArgument()) {
            return $this->catalogExporter->exportOneWebsite(Mage::app()->getWebsite($website));
        }

        return $this->catalogExporter->exportAllProducts();
    }

    /**
     * @return Mage_Core_Model_Store
     */
    private function getStoreFromArguments()
    {
        $store = '';
        try {
            $store = $this->getArg('store');
            $this->validateStore($store);
        } catch (Mage_Core_Model_Store_Exception $e) {
            printf('Store "%s" doesn\'t exist.%s', $store, PHP_EOL);
            exit(2);
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
            echo "Error: {$e->getMessage()}" . PHP_EOL;
            exit(2);
        }
        return $website;
    }
}

$exporter = new LizardsAndPumpkins_Export();
$exporter->run();
