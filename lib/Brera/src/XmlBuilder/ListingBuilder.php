<?php
namespace Brera\MagentoConnector\XmlBuilder;

class ListingBuilder
{
    /**
     * @var string[]
     */
    private static $allowedConditions = ['and', 'or'];

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
     * @param string $urlKey
     * @param string $condition
     */
    private function __construct($urlKey, $condition)
    {
        $this->condition = $condition;
        $this->urlKey = $urlKey;
    }

    public static function create($urlKey, $condition)
    {
        $allowedInUrl = '#^[a-zA-Z0-9"\$\-_\.\+\!\*\'\(\)]*$#';
        if (!is_string($urlKey) || !preg_match($allowedInUrl, $urlKey) || $urlKey === '') {
            throw new \InvalidArgumentException(
                sprintf(
                    'Only a-z A-Z 0-9 and "$-_.+!*\'()," are allowed for a url, "%s" contains forbidden characters.',
                    is_string($urlKey) ? $urlKey : 'no string'
                )
            );
        }

        if (!in_array($condition, self::$allowedConditions)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Condition must be either "and" or "or". %s given.',
                    is_string($condition) ? "\"$condition\"" : 'No string'
                )
            );
        }

        return new self($urlKey, $condition);
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $parts = explode('_', $locale);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException(sprintf('Locale must be of form "de_DE", "%s" given.', $locale));
        }
        list($language, $country) = $parts;
        if (ctype_lower($language) || ctype_upper($country) || strlen($language) !== 2 || strlen($country) !== 2) {
            throw new \InvalidArgumentException(sprintf('Locale must be of form "de_DE", "%s" given.', $locale));
        }
        $this->locale = $locale;
    }

    /**
     * @param string $website
     */
    public function setWebsite($website)
    {
        $this->website = $website;
    }

    public function buildXml()
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('listing');
        $xml->writeAttribute('url_key', $this->urlKey);

        $xml->endElement(); // listing
        return new XmlString($xml->flush());

    }
}
