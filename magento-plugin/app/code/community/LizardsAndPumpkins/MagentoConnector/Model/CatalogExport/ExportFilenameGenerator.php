<?php

use LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig as MagentoConfig;

class LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFilenameGenerator
{
    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig
     */
    private $config;

    public function __construct(MagentoConfig $config)
    {
        $this->config = $config;
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
