<?php
require_once 'abstract.php';
require '../lib/autoload_brera.php';

class Brera_Export extends Mage_Shell_Abstract
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
        if ($this->getArg('all-products')) {
            /** @var Brera_MagentoConnector_Model_Export_ProductExporter $exporter */
            $exporter = Mage::getModel('brera_magentoconnector/export_productExporter');
            $exporter->exportAllProducts();
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
Usage:  php -f $filename -- [options]

  --all-products                Export all products
  --stock                       Export stock updates
  --products                    Export product updates
  --cms                         Export CMS pages
  help                          This help

USAGE;
    }
}

$exporter = new Brera_Export();
$exporter->run();

