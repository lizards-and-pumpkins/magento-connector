<?php
require_once 'abstract.php';

class Brera_Export extends Mage_Shell_Abstract
{
    public function __construct()
    {
        parent::__construct();
        require 'autoload_brera.php';
    }

    protected function _applyPhpVariables()
    {
        return;
    }

    public function run()
    {
        if ($this->getArg('all-products')) {
            /** @var Brera_MagentoConnector_Model_Export_Exporter $exporter */
            $exporter = Mage::getModel('brera_magentoconnector/export_exporter');
            $exporter->exportAllProducts();
        } else {
            echo $this->usageHelp();
        }
    }

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

