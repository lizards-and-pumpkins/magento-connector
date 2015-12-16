<?php

use LizardsAndPumpkins\MagentoConnector\Api\Api;

require  __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../../../shell/abstract.php';

class LizardsAndPumpkins_Export extends Mage_Shell_Abstract
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function _applyPhpVariables()
    {
        return;
    }

    public function run()
    {
        /** @var LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter $exporter */
        $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_catalogExporter');
        if ($this->getArg('all-products')) {
            $filename = $exporter->exportAllProducts();
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
}

$exporter = new LizardsAndPumpkins_Export();
$exporter->run();
