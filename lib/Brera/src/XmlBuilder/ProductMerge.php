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

    function __construct()
    {
        $this->xml = new \DOMDocument('1.0', 'utf-8');
        $this->productsNode = $this->xml->createElement('products');
        $this->xml->appendChild($this->productsNode);
    }

    public function addProduct(ProductContainer $product)
    {
        $this->productsNode->appendChild($product->getProductDomDocument());
    }
}
