<?php

require dirname(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '.') . '/abstract.php';

class LizardsAndPumpkins_Export extends Mage_Shell_Abstract
{
    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_QueueExporter
     */
    private $queueExporter;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_CmsExport_BlockExport
     */
    private $contentExporter;

    /**
     * @var false|LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_CompleteCatalogExporter
     */
    private $catalogExporter;

    public function __construct()
    {
        parent::__construct();
        $this->contentExporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/cmsExport_blockExport');
        $this->queueExporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/catalogExport_queueExporter');
        $this->catalogExporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/catalogExport_completeCatalogExporter');
    }

    protected function _applyPhpVariables()
    {
        return;
    }

    public function run()
    {
        if ($this->getArg('all-products')) {
            $this->exportProducts();
        } elseif ($this->getArg('queued-products')) {
            $this->queueExporter->exportQueuedProducts();
        } elseif ($this->getArg('queued-categories')) {
            $this->queueExporter->exportQueuedCategories();
        } elseif ($this->getArg('all-categories')) {
            $this->exportAllCategories();
        } elseif ($this->getArg('blocks')) {
            $this->contentExporter->export();
        } elseif ($this->getArg('stats')) {
            $this->outputStatistics();
        } else {
            echo $this->usageHelp();
        }
    }

    /**
     * @return string
     */
    private function exportProducts()
    {
        if ($this->getStoreFromArguments()) {
            $website = $this->getStoreFromArguments()->getWebsite();
            $this->catalogExporter->exportProductsForWebsite($website);
        } elseif ($this->getWebsiteFromArgument()) {
            $website = $this->getWebsiteFromArgument();
            $this->catalogExporter->exportProductsForWebsite($website);
        } else {
            $this->catalogExporter->exportAllProducts();
        }
    }

    private function exportAllCategories()
    {
        if ($this->getStoreFromArguments()) {
            $website = $this->getStoreFromArguments()->getWebsite();
            $this->catalogExporter->exportCategoriesForWebsite($website);
        } elseif ($this->getWebsiteFromArgument()) {
            $website = $this->getWebsiteFromArgument();
            $this->catalogExporter->exportCategoriesForWebsite($website);
        } else {
            $this->catalogExporter->exportAllCategories();
        }
    }

    private function outputStatistics()
    {
        $queue = $this->getFactory()->createExportQueue();
        echo sprintf('%s queued products.' . "\n", $queue->getProductQueueCount());
        echo sprintf('%s queued categories.' . "\n", $queue->getCategoryQueueCount());
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

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Helper_Factory
     */
    private function getFactory()
    {
        return Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
    }
}

$exporter = new LizardsAndPumpkins_Export();
$exporter->run();
