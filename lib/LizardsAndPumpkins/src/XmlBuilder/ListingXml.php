<?php
declare(strict_types=1);

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
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);

        $xml->startElement('listing');

        $xml->writeAttribute('url_key', $urlPath);
        $xml->writeAttribute('locale', $this->config->getLocaleFrom($category->getStore()));
        $xml->writeAttribute('website', $category->getStore()->getCode());

        $xml->startElement('criteria');
        $xml->writeAttribute('type', self::CONDITION_AND);
        $this->writeCategoryCriteriaXml($xml, $urlPath);
        $this->writeStockAvailabilityCriteriaXml($xml);
        $xml->endElement();

        $this->writeCategoryAttributesXml($xml, $category);

        $xml->endElement();
        return new XmlString($xml->flush());
    }

    /**
     * @param \XMLWriter $xml
     */
    private function writeStockAvailabilityCriteriaXml(\XMLWriter $xml)
    {
        $xml->startElement('criteria');
        $xml->writeAttribute('type', 'or');

        $xml->startElement('attribute');
        $xml->writeAttribute('name', 'stock_qty');
        $xml->writeAttribute('is', 'GreaterThan');
        $xml->text('0');
        $xml->endElement();

        $xml->startElement('attribute');
        $xml->writeAttribute('name', 'backorders');
        $xml->writeAttribute('is', 'Equal');
        $xml->text('true');
        $xml->endElement();

        $xml->endElement();
    }

    /**
     * @param \XMLWriter $xml
     * @param string $urlPath
     */
    private function writeCategoryCriteriaXml(\XMLWriter $xml, $urlPath)
    {
        $xml->startElement('attribute');
        $xml->writeAttribute('name', 'category');
        $xml->writeAttribute('is', 'Equal');
        $xml->text($urlPath);
        $xml->endElement();
    }

    /**
     * @param \XMLWriter $xml
     * @param Mage_Catalog_Model_Category $category
     */
    private function writeCategoryAttributesXml(\XMLWriter $xml, Mage_Catalog_Model_Category $category)
    {
        // TODO: Put into configuration
        $attributeNames = ['meta_title', 'description', 'meta_description', 'meta_keywords'];

        $xml->startElement('attributes');

        array_map(function ($attributeName) use ($xml, $category) {
            $xml->startElement('attribute');
            $xml->writeAttribute('name', $attributeName);
            $xml->startCdata();
            $xml->text($category->getData($attributeName));
            $xml->endCdata();
            $xml->endElement();
        }, $attributeNames);

        $xml->endElement();
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
