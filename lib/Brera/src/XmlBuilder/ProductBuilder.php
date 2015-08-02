<?php
namespace Brera\MagentoConnector\XmlBuilder;

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
     * @var \XMLWriter
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
        $this->xml = new \XMLWriter();
        $this->xml->openMemory();
        $this->xml->startDocument('1.0', 'UTF-8');
        $this->parseProduct();
    }

    /**
     * @return ProductContainer
     */
    public function getProductContainer()
    {
        return new ProductContainer($this->xml->flush());
    }

    private function parseProduct()
    {
        $this->xml->startElement('product');
        foreach ($this->productData as $attributeName => $value) {
            $this->checkAttributeName($attributeName);
            if ($attributeName == 'images') {
                $this->createImageNodes($value);
            } elseif ($this->isAttributeProductAttribute($attributeName)) {
                $this->createAttribute($attributeName, $value);
            } else {
                $this->createNode($attributeName, $value);
            }
        }
        $this->xml->endElement();
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
     */
    private function createImageNode($image)
    {
        if (!is_array($image)) {
            throw new InvalidImageDefinitionException('images must be an array of image definitions.');
        }
        $this->checkValidImageValues($image);
        $this->xml->startElement('image');

        $this->xml->writeElement('main', isset($image['main']) && $image['main'] ? 'true' : 'false');
        $this->xml->writeElement('file', $image['file']);
        $this->xml->writeElement('label', $image['label']);

        $this->xml->endElement();
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
        if (!is_string($label) && $label !== null) {
            throw new InvalidImageDefinitionException('"label" must be a string.');
        }
    }

    /**
     * @param string $attributeName
     * @param string $value
     */
    private function createNode($attributeName, $value)
    {
        if (is_string($value)) {
            $this->xml->startElement($attributeName);
            $this->addContextAttributes();
            $this->xml->text($value);
            $this->xml->endElement();
        }
    }

    /**
     * @param string $attributeName
     * @param string $value
     */
    private function createAttribute($attributeName, $value)
    {
        $this->xml->startAttribute($attributeName);
        $this->xml->text($value);
        $this->xml->endAttribute();
    }

    /**
     * @param string[][] $images
     */
    private function createImageNodes($images)
    {
        foreach ($images as $image) {
            $this->createImageNode($image);
        }
    }

    private function addContextAttributes()
    {
        foreach ($this->context as $key => $value) {
            $this->xml->startAttribute($key);
            $this->xml->text($value);
            $this->xml->endAttribute();
        }
    }
}
