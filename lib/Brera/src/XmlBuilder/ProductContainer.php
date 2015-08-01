<?php
namespace Brera\MagentoConnector\XmlBuilder;

class ProductContainer
{
    /**
     * @var \SimpleXMLElement
     */
    private $xml;

    /**
     * @param string $productXml
     */
    public function __construct($productXml)
    {
        $this->xml = new \DOMDocument();
        $this->xml->loadXML($productXml);
    }

    /**
     * @return string
     */
    public function getXml()
    {
        return $this->xml->saveXML($this->xml->documentElement);
    }
}
