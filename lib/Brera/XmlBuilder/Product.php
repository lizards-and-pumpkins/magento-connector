<?php
namespace Brera\MagentoConnector\Product;

class XmlBuilder
{
    const ATTRIBUTE_TYPES = [
        'type',
        'sku',
        'visibility',
        'tax_class_id',
    ];

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
            if ($this->isAttributeProductAttribute($attributeName)) {
                $attribute = $this->xml->createAttribute($attributeName);
                $attribute->value = $value;
                $product->appendChild($attribute);
            } else {
                $node = $this->xml->createElement($attributeName);
                $node->nodeValue = $value;
                $product->appendChild($node);
            }
        }
        $this->xml->appendChild($product);
    }

    /**
     * @param string $attribute
     * @return bool
     */
    private function isAttributeProductAttribute($attribute)
    {
        return in_array($attribute, self::ATTRIBUTE_TYPES);
    }

}
