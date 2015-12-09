<?php

namespace LizardsAndPumpkins\MagentoConnector\XmlBuilder;

class ListingBuilder
{
    const CONDITION_AND = 'and';
    const CONDITION_OR = 'or';

    /**
     * @var string[]
     */
    private static $allowedConditions = [self::CONDITION_AND, self::CONDITION_OR];

    /**
     * @var string[]
     */
    private $allowedOperations = [
        'Equal',
        'GreaterOrEqualThan',
        'GreaterThan',
        'LessOrEqualThan',
        'LessThan',
        'Like',
        'NotEqual',
    ];

    /**
     * @var string
     */
    private $website;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $condition;

    /**
     * @var string
     */
    private $urlKey;

    /**
     * @var string[]
     */
    private $filter = [];

    /**
     * @param string $urlKey
     * @param string $condition
     */
    private function __construct($urlKey, $condition)
    {
        $this->condition = $condition;
        $this->urlKey = $urlKey;
    }

    /**
     * @param string $urlKey
     * @param string $condition
     * @return ListingBuilder
     */
    public static function create($urlKey, $condition)
    {
        self::validateCondition($condition);
        self::validateUrlKey($urlKey);

        return new self($urlKey, $condition);
    }

    /**
     * @param string $urlKey
     */
    private static function validateUrlKey($urlKey)
    {
        $allowedInUrl = '#^[a-zA-Z0-9"\$\-_\.\+\!\*\'\(\)/]+$#';

        if (!is_string($urlKey)) {
            throw new \InvalidArgumentException('UrlKey must be string.');
        }

        if (!preg_match($allowedInUrl, $urlKey)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Only a-z A-Z 0-9 and "$-_.+!*\'(),/" are allowed for a url, "%s" contains forbidden characters.',
                    $urlKey
                )
            );
        }
    }

    /**
     * @param string $condition
     */
    private static function validateCondition($condition)
    {
        if (!is_string($condition)) {
            throw new \InvalidArgumentException('Condition must be string.');
        }

        if (!in_array($condition, self::$allowedConditions)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Condition must be either "and" or "or". %s given.',
                    $condition
                )
            );
        }
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        if (!is_string($locale)) {
            throw new \InvalidArgumentException('Locale msut be string');
        }
        $parts = explode('_', $locale);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException(sprintf('Locale must be of form "de_DE", "%s" given.', $locale));
        }
        list($language, $country) = $parts;
        if (!ctype_lower($language) || !ctype_upper($country) || strlen($language) !== 2 || strlen($country) !== 2) {
            throw new \InvalidArgumentException(sprintf('Locale must be of form "de_DE", "%s" given.', $locale));
        }
        $this->locale = $locale;
    }

    /**
     * @param string $website
     */
    public function setWebsite($website)
    {
        if (!is_string($website)) {
            throw new \InvalidArgumentException('Website must be string.');
        }
        $this->website = $website;
    }

    /**
     * @param string $attribute
     * @param string $operation
     * @param string $value
     */
    public function addFilterCriterion($attribute, $operation, $value)
    {
        $this->validateFilterParameters($attribute, $operation, $value);
        $this->filter[] = [
            'attribute' => $attribute,
            'operation' => $operation,
            'value'     => $value,
        ];
    }

    /**
     * @return XmlString
     */
    public function buildXml()
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('listing');

        $this->writeAttributesToListingNode($xml);

        foreach ($this->filter as $filter) {
            $xml->startElement($filter['attribute']);
            $xml->writeAttribute('operation', $filter['operation']);
            $xml->text($filter['value']);
            $xml->endElement();
        }

        $xml->endElement(); // listing
        return new XmlString($xml->flush());
    }

    /**
     * @param string $attribute
     * @param string $operation
     * @param string $value
     */
    private function validateFilterParameters($attribute, $operation, $value)
    {
        if (!is_string($attribute)) {
            throw new \InvalidArgumentException('Attribute must be a string');
        }
        if ($attribute === '') {
            throw new \InvalidArgumentException('Attribute is not allwed to be empty string.');
        }
        if (!is_string($operation)) {
            throw new \InvalidArgumentException('Operation must be a string');
        }
        if (!is_string($value)) {
            throw new \InvalidArgumentException('Value must be a string');
        }
        if (!in_array($operation, $this->allowedOperations)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Operation is invalid, must be one of %s. "%s" given.',
                    implode(', ', $this->allowedOperations),
                    $operation
                )
            );
        }
    }

    /**
     * @param \XMLWriter $xml
     */
    private function writeAttributesToListingNode($xml)
    {
        $xml->writeAttribute('url_key', $this->urlKey);
        if ($this->locale) {
            $xml->writeAttribute('locale', $this->locale);
        }
        if ($this->website) {
            $xml->writeAttribute('website', $this->website);
        }
        $xml->writeAttribute('condition', $this->condition);
    }
}
