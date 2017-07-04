<?php

class LizardsAndPumpkins_MagentoConnector_Model_ProductXmlUploader
    extends LizardsAndPumpkins_MagentoConnector_Model_XmlUploader
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

}
