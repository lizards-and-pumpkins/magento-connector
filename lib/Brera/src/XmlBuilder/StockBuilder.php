<?php

namespace Brera\MagentoConnector\XmlBuilder;

class StockBuilder
{
    /**
     * @var \XMLWriter
     */
    private $xml;

    public function __construct()
    {
        $this->xml = new \XMLWriter();
        $this->xml->openMemory();
        $this->xml->startDocument('1.0', 'UTF-8');
    }

    public function addStockData($sku, $qty)
    {
        $this->xml->startElement('stock');
        $this->xml->writeElement('sku', $sku);
        $this->xml->writeElement('quantity', $qty);
        $this->xml->endElement(); // stock
    }

    public function getXml()
    {
        return $this->xml->flush();
    }
}
