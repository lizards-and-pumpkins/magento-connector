<?php
namespace Brera\MagentoConnector\Xml\Product;

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
    function __construct(array $productData, array $context)
    {
        $this->productData = $productData;
        $this->context = $context;
        $this->xml = new \DOMDocument('1.0', 'utf-8');
        $this->parseProduct();
    }

    /**
     * @return ProductContainer
     */
    public function getDomDocument()
    {
        return new ProductContainer($this->xml);
    }

    private function parseProduct()
    {
        $product = $this->xml->createElement('product');
        foreach ($this->productData as $attributeName => $value) {
            $this->checkAttributeName($attributeName);
            if ($attributeName == 'images') {
                foreach ($value as $image) {
                    $imageNode = $this->createImageNode($image);
                    $product->appendChild($imageNode);
                }
            } elseif ($this->isAttributeProductAttribute($attributeName)) {
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

}
