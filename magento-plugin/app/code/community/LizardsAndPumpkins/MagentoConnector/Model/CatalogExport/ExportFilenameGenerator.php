<?php

use LizardsAndPumpkins_MagentoConnector_Model_MagentoConfig as MagentoConfig;

class LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFilenameGenerator
{
    /**
     * @var MagentoConfig
     */
    private $config;

    /**
     * @param MagentoConfig
     */
    public function __construct($config)
    {
        $this->config = $config ? $config : Mage::helper('lizardsAndPumpkins_magentoconnector/factory')->getConfig();
    }

    /**
     * @return string
     */
    public function getNewFilename()
    {
        $xmlPath = $this->config->getLocalPathForProductExport();
        $xmlFilename = $this->config->getLocalFilename();
        $xmlPath = $xmlFilename !== '' && substr($xmlPath, -1) !== '/' ? $xmlPath . '/' : $xmlPath;

        return $xmlPath . $xmlFilename;
    }
}
