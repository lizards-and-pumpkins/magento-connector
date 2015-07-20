<?php

namespace Brera\MagentoConnector\Xml\Product;

class ProductMerge
{
    /**
     * @var \DOMDocument
     */
    private $xml;

    /**
     * @var \DOMElement
     */
    private $productsNode;

    public function __construct()
    {
        $this->xml = new \DOMDocument('1.0', 'utf-8');
        $catalogNode = $this->createCatalogNode();

        $this->productsNode = $this->xml->createElement('products');
        $catalogNode->appendChild($this->productsNode);
        $this->xml->appendChild($catalogNode);
    }

    public function addProduct(ProductContainer $product)
    {
        $node = $this->xml->importNode($product->getProductDomDocument()->firstChild, true);
        $this->productsNode->appendChild($node);
    }

    /**
     * @return string
     */
    public function getXmlString()
    {
        $this->xml->formatOutput = true;

        return $this->xml->saveXML();
    }

    /**
     * @return \DOMElement
     */
    private function createCatalogNode()
    {
        $catalogNode = $this->xml->createElement('catalog');
        $attributes = [
            'xmlns' => 'http://brera.io',
            'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation' => 'http://brera.io ../../schema/catalog.xsd'
        ];

        foreach ($attributes as $attribute => $value) {
            $attribute = $this->xml->createAttribute($attribute);
            $attribute->value = $value;
            $catalogNode->appendChild($attribute);
        }

        return $catalogNode;
    }
}
