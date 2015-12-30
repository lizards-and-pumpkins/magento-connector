<?php

use LizardsAndPumpkins\MagentoConnector\XmlBuilder\ListingBuilder;
use LizardsAndPumpkins\MagentoConnector\XmlBuilder\XmlString;

class LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryTransformer
{
    const URL_KEY_REPLACE_PATTERN = '#[^a-zA-Z0-9:_\-\./]#';

    /**
     * @var Mage_Catalog_Model_Category
     */
    private $category;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig
     */
    private $config;

    private function __construct(
        Mage_Catalog_Model_Category $category,
        LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig $config
    ) {
        $this->category = $category;
        $this->config = $config;
    }

    /**
     * @param Mage_Catalog_Model_Category $category
     * @param LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig $config
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryTransformer
     */
    public static function createForTesting(
        Mage_Catalog_Model_Category $category,
        LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig $config
    ) {
        return new self($category, $config);
    }

    /**
     * @param Mage_Catalog_Model_Category $category
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryTransformer
     */
    public static function createFrom(Mage_Catalog_Model_Category $category)
    {
        $config = new LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig();
        return new self($category, $config);
    }

    /**
     * @return XmlString
     */
    public function getCategoryXml()
    {
        if (!($this->category->getStore() instanceof Mage_Core_Model_Store)) {
            throw new RuntimeException('Store must be set on category.');
        }

        $urlPath = $this->category->getUrlPath();
        $listingBuilder = ListingBuilder::create(
            $this->normalizeUrl($urlPath),
            $this->category->getStore()->getWebsite()->getCode(),
            $this->getLocale($this->category->getStore())
        );

        $listingBuilder->addFilterCriterion('category', 'Equal', $this->normalizeUrl($urlPath));

        return $listingBuilder->buildXml();
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return string
     */
    private function getLocale($store)
    {
        return $this->config->getLocaleFrom($store);
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
