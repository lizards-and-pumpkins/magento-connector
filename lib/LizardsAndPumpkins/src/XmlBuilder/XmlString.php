<?php
declare(strict_types=1);

namespace LizardsAndPumpkins\MagentoConnector\XmlBuilder;

class XmlString
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
        $this->xml->loadXML($this->removeControlCharacters($productXml));
        $this->xml->formatOutput = true;
    }

    /**
     * @return string
     */
    public function getXml()
    {
        return $this->xml->saveXML($this->xml->documentElement);
    }

    /**
     * @param string $productXml
     * @return string
     */
    private function removeControlCharacters($productXml)
    {
        return preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $productXml);
    }
}
