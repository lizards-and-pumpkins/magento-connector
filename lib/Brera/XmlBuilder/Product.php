<?php
namespace Brera\MagentoConnector\Product;

class XmlBuilder
{

    /**
     * @var \DOMDocument
     */
    private $xml;

    /**
     * @var array
     */
    private $productData;
    /**
     * @var array
     */
    private $context;

    function __construct(array $productData, array $context)
    {
        $this->productData = $productData;
        $this->context = $context;
        $this->xml = new \DOMDocument('1.0', 'utf-8');
    }

    public function getXmlString()
    {
        $this->parseProduct();

        $this->xml->formatOutput = true;

        return trim($this->xml->saveXML());
    }

    private function parseProduct()
    {
        $product = $this->xml->createElement('product');
        foreach ($this->productData as $attributeName => $value) {
            $attribute = $this->xml->createAttribute($attributeName);
            $attribute->value = $value;
            $product->appendChild($attribute);
        }
        $this->xml->appendChild($product);
    }
}
