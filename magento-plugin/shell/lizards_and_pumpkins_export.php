<?php
require_once 'abstract.php';
require '../lib/autoload_lizards_and_pumpkins.php';

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
            $exporter->exportAllProducts();
        } elseif ($this->getArg('queued-products')) {
            $exporter->exportProductsInQueue();
        } elseif ($this->getArg('queued-categories')) {
            $exporter->exportCategoriesInQueue();
        } elseif ($this->getArg('all-categories')) {
            $exporter->exportAllCategories();
        } elseif ($this->getArg('stats')) {
            $this->outputStatistics();
        } else {
            echo $this->usageHelp();
        }
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
