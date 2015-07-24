<?php
namespace Brera\MagentoConnector\Xml\Product;

require_once('ProductContainer.php');
require_once('InvalidImageDefinitionException.php');

class ProductBuilder
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
     * @var string[][][]
     */
    private $productData;
    /**
     * @var string[]
     */
    private $context;

    /**
     * @param string[] $productData
     * @param string[] $context
     */
    public function __construct(array $productData, array $context)
    {
        $this->productData = $productData;
        $this->context = $context;
        $this->xml = new \DOMDocument('1.0', 'utf-8');
        $this->parseProduct();
    }

    /**
     * @return ProductContainer
     */
    public function getProductContainer()
    {
        return new ProductContainer($this->xml);
    }

    private function parseProduct()
    {
        /** @var $product \DOMElement */
        $product = $this->xml->createElement('product');
        foreach ($this->productData as $attributeName => $value) {
            $this->checkAttributeName($attributeName);
            if ($attributeName == 'images') {
                $this->createImageNodes($value, $product);
            } elseif ($this->isAttributeProductAttribute($attributeName)) {
                $this->createAttribute($attributeName, $value, $product);
            } else {
                $this->createNode($attributeName, $value, $product);
            }
        }
        $this->xml->appendChild($product);
    }

    /**
     * @param string $attributeName
     * @return bool
     */
    private function checkAttributeName($attributeName)
    {
        new \DOMElement($attributeName);

        return true;

    }

    /**
     * @param string $attribute
     * @return bool
     */
    private function isAttributeProductAttribute($attribute)
    {
        return in_array($attribute, self::ATTRIBUTE_TYPES);
    }

    /**
     * @param string[] $image
     * @return \DOMElement
     */
    private function createImageNode($image)
    {
        if (!is_array($image)) {
            throw new InvalidImageDefinitionException('images must be an array of image definitions.');
        }
        $this->checkValidImageValues($image);
        $imageNode = $this->xml->createElement('image');

        $mainNode = $this->xml->createElement('main');
        $mainNode->nodeValue = isset($image['main']) && $image['main'] ? 'true' : 'false';
        $imageNode->appendChild($mainNode);

        $fileNode = $this->xml->createElement('file');
        $fileNode->nodeValue = $image['file'];
        $imageNode->appendChild($fileNode);

        $labelNode = $this->xml->createElement('label');
        $labelNode->nodeValue = $image['label'];
        $imageNode->appendChild($labelNode);

        return $imageNode;
    }

    /**
     * @param string[] $image
     */
    private function checkValidImageValues(array $image)
    {
        $main = isset($image['main']) ? $image['main'] : null;
        if (!is_bool($main) && $main !== null) {
            throw new InvalidImageDefinitionException('"main" must be either "true" or "false".');
        }

        $file = $image['file'];
        if (!is_string($file)) {
            throw new InvalidImageDefinitionException('"file" must be a string.');
        }

        $label = $image['label'];
        if (!is_string($label)) {
            throw new InvalidImageDefinitionException('"label" must be a string.');
        }

    }

    /**
     * @param string $attributeName
     * @param string $value
     * @param \DOMElement $product
     */
    private function createNode($attributeName, $value, $product)
    {
        $node = $this->xml->createElement($attributeName);
        $node->nodeValue = $value;
        $product->appendChild($node);

        $this->addContextAttributes($node);
    }

    /**
     * @param string $attributeName
     * @param string $value
     * @param \DOMElement $product
     */
    private function createAttribute($attributeName, $value, $product)
    {
        $attribute = $this->xml->createAttribute($attributeName);
        $attribute->value = $value;
        $product->appendChild($attribute);
    }

    /**
     * @param string[][] $images
     * @param \DOMElement $product
     */
    private function createImageNodes($images, $product)
    {
        foreach ($images as $image) {
            $imageNode = $this->createImageNode($image);
            $product->appendChild($imageNode);
        }
    }

    /**
     * @param \DomElement $node
     */
    private function addContextAttributes($node)
    {
        foreach ($this->context as $key => $value) {
            $attributeNode = $this->xml->createAttribute($key);
            $attributeNode->value = $value;
            $node->appendChild($attributeNode);
        }
    }

}
