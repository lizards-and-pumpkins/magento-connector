<?php

class Brera_MagentoConnector_Model_ProductXmlUploader extends Brera_MagentoConnector_Model_XmlUploader
{
    /**
     * @var string
     */
    private $filename;

    public function __construct()
    {
        $xmlPath = Mage::getStoreConfig('brera/magentoconnector/local_path_for_product_export');
        $xmlFilename = strftime(Mage::getStoreConfig('brera/magentoconnector/local_filename_template'));
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
