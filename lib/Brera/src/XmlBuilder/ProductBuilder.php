<?php
namespace Brera\MagentoConnector\XmlBuilder;

require_once('ProductContainer.php');
require_once('InvalidImageDefinitionException.php');

class ProductBuilder
{
    const NO_NODE_TYPES = [
        'images',
        'variations',
        'associated_products',
    ];

    const ATTRIBUTE_TYPES = [
        // magento_type   => LaP type
        'type_id'      => 'type',
        'sku'          => 'sku',
        'tax_class_id' => 'tax_class_id',
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
        $this->validateContext($context);
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
        $this->createProductAttributes();

        $this->createImageNodes();
        $this->createAssociatedProductsNode();
        $this->createVariationsNode();

        $this->xml->startElement('attributes');
        foreach ($this->productData as $attributeName => $value) {
            $this->checkAttributeName($attributeName);
            if (!$this->isANodeRequiredForAttribute($attributeName)) {
                continue;
            }

            if ($attributeName == 'categories') {
                $this->createCategoryNodes($value);
            } else {
                $this->createNode($attributeName, $value);
            }
        }
        $this->xml->endElement(); // attributes
        $this->xml->endElement(); // product
    }

    private function createVariations()
    {
        $attributes = $this->productData['variations'];
        $this->xml->startElement('variations');
        foreach ($attributes as $attribute) {
            $this->xml->writeElement('attribute', $attribute);
        }
        $this->xml->endElement(); // variations
    }

    /**
     * @param string[] $categories
     */
    private function createCategoryNodes(array $categories)
    {
        foreach ($categories as $category) {
            $this->createNode('category', $category);
        }
    }

    /**
     * @param string $attributeName
     *
     * @return bool
     */
    private function checkAttributeName($attributeName)
    {
        new \DOMElement($attributeName);

        return true;
    }

    /**
     * @param string $attribute
     *
     * @return bool
     */
    private function isANodeRequiredForAttribute($attribute)
    {
        return !in_array($attribute, self::NO_NODE_TYPES) && !in_array($attribute, array_keys(self::ATTRIBUTE_TYPES));
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
        if (!$this->isCastableToString($value)) {
            return;
        }
        $this->xml->startElement($attributeName);
        $this->addContextAttributes();
        $this->xml->text($value);
        $this->xml->endElement();
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    private function isCastabletoString($value)
    {
        if (is_array($value)) {
            return false;
        }

        if (!is_object($value) && settype($value, 'string') !== false) {
            return true;
        }

        return (is_object($value) && method_exists($value, '__toString'));
    }

    private function createImageNodes()
    {
        if (!isset($this->productData['images'])) {
            return;
        }
        $this->xml->startElement('images');
        foreach ($this->productData['images'] as $image) {
            $this->createImageNode($image);
        }
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

    private function createAssociatedProductNodes()
    {
        /** @var $products string[] */
        $products = $this->productData['associated_products'];
        $this->validateAssociatedProducts($products);
        $xml = $this->xml;
        $xml->startElement('associated_products');
        foreach ($products as $product) {
            $xml->startElement('product');
            $xml->writeAttribute('sku', $product['sku']);
            $xml->writeAttribute('visible', $product['visible'] ? 'true' : 'false');
            $xml->writeAttribute('tax_class_id', $product['tax_class_id']);
            $xml->startElement('attributes');
            $xml->writeElement('stock_qty', $product['stock_qty']);
            foreach ($product['attributes'] as $attributeName => $value) {
                $locale = isset($this->context['locale']) ? $this->context['locale'] : '';
                $xml->startElement($attributeName);
                $xml->writeAttribute('locale', $locale);
                $xml->text($value);
                $xml->endElement(); // $attributeName
            }
            $xml->endElement(); // attributes
            $xml->endElement(); // product
        }
        $xml->endElement(); // associated_products
    }

    /**
     * @param string[][] $products
     */
    private function validateAssociatedProducts(array $products)
    {
        // TODO implement, make sure $products[]['attributes'] is an array
    }

    /**
     * @param string[] $context
     */
    private function validateContext(array $context)
    {
        // TODO make sure locale exists
    }

    private function createProductAttributes()
    {
        foreach (self::ATTRIBUTE_TYPES as $magentoAttribute => $lpAttribute) {
            if (isset($this->productData[$magentoAttribute])) {
                $this->xml->writeAttribute($lpAttribute, $this->productData[$magentoAttribute]);
            }
        }
    }

    private function createAssociatedProductsNode()
    {
        if (isset($this->productData['associated_products'])) {
            $this->createAssociatedProductNodes();
        }
    }

    private function createVariationsNode()
    {
        if (isset($this->productData['variations'])) {
            return $this->createVariations();
        }
    }
}
