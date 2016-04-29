<?php

use LizardsAndPumpkins\MagentoConnector\Api\Api;

class LizardsAndPumpkins_MagentoConnector_Model_Export_Content
{
    const SNIPPET_KEY_REPLACE_PATTERN = '#[^a-zA-Z0-9:_\-]#';

    const XML_SPECIAL_BLOCKS = 'lizardsAndPumpkins/magentoconnector/cms_special_blocks';

    /**
     * @var Api
     */
    private $memoizedApi;

    public function export()
    {
        $cmsBlocks = $this->getCmsBlocks();
        $this->exportCmsBlocks($cmsBlocks);

        $inProductListingCmsBlocks = $this->getInProductListingCmsBlocks();
        $this->exportInProductListingCmsBlocks($inProductListingCmsBlocks);

        $this->exportNonCmsBlocks();
    }

    /**
     * @return Mage_Cms_Model_Resource_Block_Collection
     */
    private function getCmsBlocks()
    {
        /** @var Mage_Cms_Model_Resource_Block_Collection $cmsBlocks */
        $cmsBlocks = Mage::getResourceModel('cms/block_collection')
            ->join(['block_store' => 'cms/block_store'], 'main_table.block_id=block_store.block_id', 'store_id')
            ->addExpressionFieldToSelect(
                'block_id',
                "CONCAT({{block_id}},'_', {{store_id}})",
                [
                    'block_id' => 'main_table.block_id',
                    'store_id' => 'store_id',
                ]
            );
        $cmsBlocks->addFieldToFilter(['identifier'], [['like' => 'content_block_%']]);

        return $cmsBlocks;
    }

    /**
     * @return Mage_Cms_Model_Resource_Block_Collection
     */
    private function getInProductListingCmsBlocks()
    {
        /** @var Mage_Cms_Model_Resource_Block_Collection $cmsBlocks */
        $cmsBlocks = Mage::getResourceModel('cms/block_collection')
            ->join(['block_store' => 'cms/block_store'], 'main_table.block_id=block_store.block_id', 'store_id')
            ->addExpressionFieldToSelect(
                'block_id',
                "CONCAT({{block_id}},'_', {{store_id}})",
                [
                    'block_id' => 'main_table.block_id',
                    'store_id' => 'store_id',
                ]
            );
        $cmsBlocks->addFieldToFilter(['identifier'], [['like' => 'product_listing_content_block_%']]);

        return $cmsBlocks;
    }

    /**
     * @return Api
     */
    private function getApi()
    {
        if (null === $this->memoizedApi) {
            $apiUrl = Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/api_url');
            $this->memoizedApi = new Api($apiUrl);
        }

        return $this->memoizedApi;
    }

    private function exportCmsBlocks(Mage_Cms_Model_Resource_Block_Collection $cmsBlocks)
    {
        array_map(function (Mage_Cms_Model_Block $block) {
            $blockId = $this->normalizeIdentifier($block->getIdentifier());
            $context = [
                'locale'  => Mage::getStoreConfig('general/locale/code', $block->getData('store_id')),
                'website' => Mage::app()->getStore($block->getData('store_id'))->getCode(),
            ];
            $keyGeneratorParameters = [];
            $this->getApi()->triggerCmsBlockUpdate($blockId, $block->getContent(), $context, $keyGeneratorParameters);
        }, iterator_to_array($cmsBlocks));
    }

    private function exportInProductListingCmsBlocks(Mage_Cms_Model_Resource_Block_Collection $cmsBlocks)
    {
        array_map(function (Mage_Cms_Model_Block $block) {
            $blockIdStringWithoutLastVariableToken = preg_replace('/_[^_]+$/', '', $block->getIdentifier());
            $blockId = $this->normalizeIdentifier($blockIdStringWithoutLastVariableToken);

            $categoryUrlSuffix = Mage::getStoreConfig(Mage_Catalog_Helper_Category::XML_PATH_CATEGORY_URL_SUFFIX);
            $categorySlug = preg_replace('/.*_/', '', $block->getIdentifier()) . '.' . $categoryUrlSuffix;

            $context = [
                'locale'  => Mage::getStoreConfig('general/locale/code', $block->getData('store_id')),
                'website' => Mage::app()->getStore($block->getData('store_id'))->getCode(),
            ];

            $keyGeneratorParameters = ['url_key' => $categorySlug];

            $this->getApi()->triggerCmsBlockUpdate($blockId, $block->getContent(), $context, $keyGeneratorParameters);
        }, iterator_to_array($cmsBlocks));
    }

    private function exportNonCmsBlocks()
    {
        $this->disableBlockCache();
        $this->disableCollectionCache();
        $this->replaceCatalogCategoryHelperToAvoidWrongTranslations();

        $specialBlocks = Mage::getStoreConfig(self::XML_SPECIAL_BLOCKS);
        if (!is_array($specialBlocks)) {
            return;
        }

        /** @var Mage_Core_Model_App_Emulation $appEmulation */
        $appEmulation = Mage::getSingleton('core/app_emulation');

        array_map(function ($blockIdentifier) use ($appEmulation) {
            array_map(function (Mage_Core_Model_Store $store) use ($blockIdentifier, $appEmulation) {
                $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($store->getId());

                $layout = $this->getLayoutForStore($store);
                Mage::app()->loadArea(Mage_Core_Model_App_Area::AREA_FRONTEND);
                $block = $layout->getBlock($blockIdentifier);

                if (null === $block) {
                    return;
                }

                $blockId = 'content_block_' . $this->normalizeIdentifier($block->getNameInLayout());
                $content = $block->toHtml();
                $context = [
                    'locale'  => Mage::getStoreConfig('general/locale/code', $store->getId()),
                    'website' => $store->getCode()
                ];
                $keyGeneratorParameters = [];

                $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

                $this->getApi()->triggerCmsBlockUpdate($blockId, $content, $context, $keyGeneratorParameters);
            }, $this->getMagentoConfig()->getStoresToExport());
        }, array_keys($specialBlocks));
    }

    /**
     * @param string $identifier
     * @return string
     */
    private function normalizeIdentifier($identifier)
    {
        return preg_replace(self::SNIPPET_KEY_REPLACE_PATTERN, '-', $identifier);
    }

    private function disableBlockCache()
    {
        Mage::app()->getCacheInstance()->banUse(Mage_Core_Block_Abstract::CACHE_GROUP);
    }

    private function disableCollectionCache()
    {
        Mage::app()->getCacheInstance()->banUse('collections');
    }

    private function replaceCatalogCategoryHelperToAvoidWrongTranslations()
    {
        $registryKey = '_helper/catalog/category';
        Mage::unregister($registryKey);
        Mage::register($registryKey, new LizardsAndPumpkins_MagentoConnector_Helper_Catalog_Category());
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return Mage_Core_Model_Layout
     */
    private function getLayoutForStore(Mage_Core_Model_Store $store)
    {
        /** @var Mage_Core_Model_Layout $layout */
        $layout = Mage::getModel('core/layout');
        $layout->getUpdate()->load(['default', 'STORE_' . $store->getCode()]);
        $layout->generateXml();
        $layout->generateBlocks();

        return $layout;
    }
    
    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig
     */
    private function getMagentoConfig()
    {
        return Mage::getModel('lizardsAndPumpkins_magentoconnector/export_magentoConfig');
    }
}
