<?php

namespace LizardsAndPumpkins\MagentoConnector\XmlBuilder;

use LizardsAndPumpkins\MagentoConnector\XmlBuilder\Exception\StoreNotSetOnCategoryException;
use LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig;
use Mage_Catalog_Model_Category;
use Mage_Core_Model_Store;

class ListingXml
{
    const CONDITION_AND = 'and';

    const URL_KEY_REPLACE_PATTERN = '#[^a-zA-Z0-9:_\-./]#';

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig
     */
    private $config;

    public function __construct(LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param Mage_Catalog_Model_Category $category
     * @return XmlString
     */
    public function buildXml(Mage_Catalog_Model_Category $category)
    {
        if (!($category->getStore() instanceof Mage_Core_Model_Store)) {
            throw new StoreNotSetOnCategoryException('Store must be set on category.');
        }

        $urlPath = $this->normalizeUrl($category->getUrlPath());
        $website = $category->getStore()->getWebsite()->getCode();
        $locale = $this->config->getLocaleFrom($category->getStore());

        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startElement('listing');

        $xml->writeAttribute('url_key', $urlPath);
        $xml->writeAttribute('locale', $locale);
        $xml->writeAttribute('website', $website);

        $xml->startElement('criteria');
        $xml->writeAttribute('type', self::CONDITION_AND);
        $xml->writeRaw($this->getCategoryCriteriaXml($urlPath));
        $xml->writeRaw($this->getStockAvailabilityCriteriaXml());
        $xml->endElement();

        $xml->writeRaw($this->getCategoryAttributesXml($category));

        $xml->endElement();
        return new XmlString($xml->flush());
    }

    /**
     * @return string
     */
    private function getStockAvailabilityCriteriaXml()
    {
        return <<<EOX
<criteria type="or">
    <attribute name="stock_qty" is="GreaterThan">0</attribute>
    <attribute name="backorders" is="Equal">true</attribute>
</criteria>
EOX;
    }

    /**
     * @param string $urlPath
     * @return string
     */
    private function getCategoryCriteriaXml($urlPath)
    {
        $xml = new \XMLWriter();
        $xml->openMemory();

        $xml->startElement('attribute');
        $xml->writeAttribute('name', 'category');
        $xml->writeAttribute('is', 'Equal');
        $xml->text($urlPath);
        $xml->endElement();

        return $xml->flush();
    }

    /**
     * @param Mage_Catalog_Model_Category $category
     * @return string
     */
    private function getCategoryAttributesXml(Mage_Catalog_Model_Category $category)
    {
        $xml = new \XMLWriter();
        $xml->openMemory();

        $attributeNames = ['meta_title']; // TODO: Put into configuration
        $xml->startElement('attributes');

        array_map(function ($attributeName) use ($xml, $category) {
            $xml->startElement('attribute');
            $xml->writeAttribute('name', $attributeName);
            $xml->text($category->getData($attributeName));
            $xml->endElement();
        }, $attributeNames);

        $xml->endElement();

        return $xml->flush();
    }

    /**
     * @param string $urlPath
     * @return string
     */
    private function normalizeUrl($urlPath)
    {
        return preg_replace(self::URL_KEY_REPLACE_PATTERN, '_', $urlPath);
    }
}
