<?php

namespace Brera\MagentoConnector\XmlBuilder;

require_once('ProductContainer.php');

class ProductMerge
{
    /**
     * @var \XmlWriter
     */
    private $xml;

    private $started = false;

    public function __construct()
    {
        $this->xml = new \XMLWriter();
        $this->xml->openMemory();
        $this->xml->startDocument('1.0', 'UTF-8');
        $this->startXml();
    }

    public function addProduct(ProductContainer $product)
    {
        $this->xml->writeRaw($product->getXml());
    }

    /**
     * @return string
     */
    public function finish()
    {
        $this->endXml();

        return $this->getPartialXmlString();
    }

    /**
     * @return string
     */
    public function getPartialXmlString()
    {
        return $this->xml->flush();
    }

    private function startXml()
    {
        if ($this->started) {
            return;
        }
        $this->started = true;
        $this->xml->startElement('catalog');

        $attributes = [
            'xmlns' => 'http://brera.io',
            'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation' => 'http://brera.io ../../schema/catalog.xsd'
        ];

        foreach ($attributes as $attribute => $value) {
            $this->xml->writeAttribute($attribute, $value);
        }

        $this->xml->startElement('products');
    }

    private function endXml()
    {
        $this->xml->endElement();
        $this->xml->endElement();
    }
}
