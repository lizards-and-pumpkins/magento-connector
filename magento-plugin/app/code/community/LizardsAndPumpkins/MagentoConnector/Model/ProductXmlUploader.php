<?php

class LizardsAndPumpkins_MagentoConnector_Model_ProductXmlUploader
    extends LizardsAndPumpkins_MagentoConnector_Model_XmlUploader
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig
     */
    private $config;

    public function __construct(LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig $config = null)
    {
        if ($config instanceof LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig) {
            $this->config = $config;
        } else {
            $this->config = new LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig();
        }
        $xmlPath = $this->config->getLocalPathForProductExport();
        $xmlFilename = $this->config->getLocalFilenameTemplate();
        $this->filename = $xmlFilename;
        $xmlPath = $this->suffixPathWithDirectorySeperatorIfNeeded($xmlPath);
        parent::__construct($xmlPath . $xmlFilename);
    }

    /**
     * @param string $xmlPath
     * @return string
     */
    private function suffixPathWithDirectorySeperatorIfNeeded($xmlPath)
    {
        if (substr($xmlPath, -1) !== '/') {
            $xmlPath .= '/';
            return $xmlPath;
        }
        return $xmlPath;
    }

    public function getFilename()
    {
        return $this->filename;
    }

}
