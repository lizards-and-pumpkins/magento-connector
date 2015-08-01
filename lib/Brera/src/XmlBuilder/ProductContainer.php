<?php
namespace Brera\MagentoConnector\XmlBuilder;

class ProductContainer
{
    private $xml;

    /**
     * @param string $productXml
     */
    public function __construct($productXml)
    {
        $this->xml = $productXml;
    }

    /**
     * @return string
     */
    public function getXml()
    {
        return $this->xml;
    }


}
