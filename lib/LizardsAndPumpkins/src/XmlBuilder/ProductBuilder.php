<?php

declare(strict_types = 1);

namespace LizardsAndPumpkins\MagentoConnector\XmlBuilder;

class ProductBuilder
{
    private static $productNodeAttributesMap = [
        'type_id'   => 'type',
        'sku'       => 'sku',
        'tax_class' => 'tax_class',
    ];

    /**
     * @var \XMLWriter
     */
    private $xml;

    /**
     * @var string[]
     */
    private $context;

    /**
     * @param mixed[]  $productData
     * @param string[] $context
     */
    public function __construct(array $productData, array $context)
    {
        $this->validateContext($context);
        $this->context = $context;
        $this->xml = new \XMLWriter();
        $this->xml->openMemory();
        $this->xml->setIndent(true);

        $this->xml->startDocument('1.0', 'UTF-8');
        $this->buildProductXml($productData);
    }

    /**
     * @return XmlString
     */
    public function getXmlString()
    {
        return new XmlString($this->xml->flush());
    }

    /**
     * @param mixed[] $productXml
     */
    private function buildProductXml(array $productXml)
    {
        $this->xml->startElement('product');
        $this->addProductNodeAttributes($productXml);

        $this->createImagesNodes($productXml);
        if (isset($productXml['associated_products']) && count($productXml['associated_products']) > 0) {
            $this->createAssociatedProductsNodes($productXml);
        }
        $this->createVariations($productXml);

        $this->xml->startElement('attributes');
        if (isset($productXml['attributes'])) {
            foreach ($productXml['attributes'] as $attributeName => $value) {
                $this->checkAttributeName($attributeName);
                if (!$this->shouldNodeBeChildOfAttributesNode($attributeName)) {
                    continue;
                }

                $attributeNodeName = 'categories' === $attributeName ? 'category' : $attributeName;
                $this->createAttributeNode($attributeNodeName, $value);
            }
        }
        $this->xml->endElement(); // attributes
        $this->xml->endElement(); // product
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
    private function shouldNodeBeChildOfAttributesNode($attribute)
    {
        return !in_array($attribute, array_keys(self::$productNodeAttributesMap));
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
     * @param string          $attributeName
     * @param string|string[] $attributeValue
     */
    private function createAttributeNode(string $attributeName, $attributeValue)
    {
        $values = !is_array($attributeValue) ? [$attributeValue] : $attributeValue;

        foreach ($values as $value) {
            $value = (string)$value;
            $this->xml->startElement('attribute');
            $this->xml->writeAttribute('name', $attributeName);
            $this->addContextAttributes();
            if ($this->isCDataNeeded($value)) {
                $value = str_replace(']]>', ']]]]><![CDATA[', $value);
                $this->xml->writeCdata($value);
            } else {
                $this->xml->text($value);
            }
            $this->xml->endElement();
        }
    }

    private function isCDataNeeded(string $value): bool
    {
        $xmlUnsafeCharacters = ['&', '<', '"', "'", '>'];

        foreach ($xmlUnsafeCharacters as $string) {
            if (strpos($value, $string) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param mixed[] $productXml
     */
    private function createImagesNodes(array $productXml)
    {
        if (!isset($productXml['images'])) {
            return;
        }
        $this->xml->startElement('images');
        foreach ($productXml['images'] as $image) {
            $this->createImageNode($image);
        }
        $this->xml->endElement();
    }

    /**
     * @param string[] $image
     */
    private function createImageNode(array $image)
    {
        $this->checkValidImageValues($image);
        $this->xml->startElement('image');

        $this->xml->writeElement('main', isset($image['main']) && $image['main'] ? 'true' : 'false');
        $this->xml->writeElement('file', $image['file']);
        $this->xml->writeElement('label', $image['label']);

        $this->xml->endElement();
    }

    private function addContextAttributes()
    {
        foreach ($this->context as $key => $value) {
            $this->xml->startAttribute($key);
            $this->xml->text($value);
            $this->xml->endAttribute();
        }
    }

    /**
     * @param string[] $context
     */
    private function validateContext(array $context)
    {
        if (!isset($context['locale'])) {
            throw new \InvalidArgumentException('Locale is missing on context.');
        }
        if (!is_string($context['locale'])) {
            throw new \InvalidArgumentException(
                sprintf('Locale on context must be string, %s passed.', gettype($context['locale']))
            );
        }
        if (!preg_match('#[a-z]{2}_[A-Z]{2}#', $context['locale'])) {
            throw new \InvalidArgumentException(
                sprintf('Locale must be of format de_DE, "%s" passed.', $context['locale'])
            );
        }
    }

    /**
     * @param mixed[] $productData
     */
    private function addProductNodeAttributes(array $productData)
    {
        foreach (self::$productNodeAttributesMap as $magentoAttribute => $xmlNodeAttribute) {
            if (isset($productData[$magentoAttribute])) {
                $this->xml->writeAttribute($xmlNodeAttribute, (string)$productData[$magentoAttribute]);
            }
        }
    }

    /**
     * @param mixed[] $productXml
     */
    private function createAssociatedProductsNodes(array $productXml)
    {
        /** @var $associatedProductsData mixed[] */
        $associatedProductsData = $productXml['associated_products'];
        $this->validateAssociatedProducts($associatedProductsData);
        $xml = $this->xml;
        $xml->startElement('associated_products');
        foreach ($associatedProductsData as $associatedProduct) {
            $this->buildProductXml($associatedProduct);
        }
        $xml->endElement(); // associated_products
    }

    /**
     * @param mixed[] $associatedProductsData
     */
    private function validateAssociatedProducts(array $associatedProductsData)
    {
        foreach ($associatedProductsData as $associatedProductData) {
            if (isset($associatedProductData['attributes']) && !is_array($associatedProductData['attributes'])) {
                throw new \InvalidArgumentException('Attributes need to be an array');
            }
            if (!isset($associatedProductData['sku'])) {
                throw new \InvalidArgumentException('SKU is missing on associated product.');
            }
            if (!isset($associatedProductData['attributes']['stock_qty'])) {
                throw new \InvalidArgumentException(
                    sprintf('Stock qty is missing on product %s', $associatedProductData['sku'])
                );
            }
        }
    }

    /**
     * @param mixed[] $productXml
     */
    private function createVariations(array $productXml)
    {
        if (isset($productXml['variations'])) {
            $this->createVariationsNodes($productXml);
        }
    }

    /**
     * @param mixed[] $productXml
     */
    private function createVariationsNodes(array $productXml)
    {
        $this->xml->startElement('variations');
        foreach ($productXml['variations'] as $attributeCode) {
            $this->xml->writeElement('attribute', $attributeCode);
        }
        $this->xml->endElement();
    }
}
