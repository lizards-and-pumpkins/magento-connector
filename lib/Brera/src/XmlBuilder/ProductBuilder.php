<?php
namespace Brera\MagentoConnector\XmlBuilder;

require_once('ProductContainer.php');
require_once('InvalidImageDefinitionException.php');

class ProductBuilder
{
    const IGNORED_PRODUCT_ATTRIBUTES = [
        'images',
        'variations',
        'associated_products',
    ];

    const PRODUCT_ATTRIBUTES_MAGENTO_TO_LAP_MAP = [
        'type_id'      => 'type',
        'sku'          => 'sku',
        'tax_class_id' => 'tax_class',
    ];

    const ASSOCIATED_PRODUCT_ATTRIBUTES_MAGENTO_TO_LAP_MAP = [
        'type_id'      => 'type',
        'sku'          => 'sku',
        'tax_class_id' => 'tax_class',
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
        $this->createProductAttributesAsAttributes();

        $this->createImagesNodes();
        $this->createAssociatedProductsNode();
        $this->createVariationsNode();

        $this->xml->startElement('attributes');
        foreach ($this->productData as $attributeName => $value) {
            $this->checkAttributeName($attributeName);
            if (!$this->isANodeRequiredForAttribute($attributeName)) {
                continue;
            }

            if ($attributeName == 'categories') {
                $this->createNode('category', $value);
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
    private function isANodeRequiredForAttribute($attribute)
    {
        return !in_array($attribute, self::IGNORED_PRODUCT_ATTRIBUTES)
        && !in_array($attribute, array_keys(self::PRODUCT_ATTRIBUTES_MAGENTO_TO_LAP_MAP));
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
        if (!is_array($value) && !$this->isCastabletoString($value)) {
            return;
        }
        $values = $value;
        if ($this->isCastableToString($value)) {
            $values = [$value];
        }

        foreach ($values as $value) {
            $this->xml->startElement($attributeName);
            $this->addContextAttributes();
            if ($this->isCdataNeeded($value)) {
                $value = str_replace(']]>', ']]]]><![CDATA[', $value);
                $this->xml->writeCdata($value);
            } else {
                $this->xml->text($value);
            }
            $this->xml->endElement();
        }
    }

    /**
     * @param string $value
     * @return bool
     */
    private function isCdataNeeded($value)
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
     * @param mixed $value
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

    private function createImagesNodes()
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
            foreach (self::ASSOCIATED_PRODUCT_ATTRIBUTES_MAGENTO_TO_LAP_MAP as $magentoName => $lpName) {
                if (isset($product[$magentoName])) {
                    $xml->writeAttribute($lpName, $product[$magentoName]);
                }
            }
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

    private function createProductAttributesAsAttributes()
    {
        foreach (self::PRODUCT_ATTRIBUTES_MAGENTO_TO_LAP_MAP as $magentoAttribute => $lpAttribute) {
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
            $this->createVariations();
        }
    }
}
