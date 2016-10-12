<?php

use LizardsAndPumpkins\MagentoConnector\Api\Api;

class LizardsAndPumpkins_MagentoConnector_Model_Export_Content
{
    const SNIPPET_KEY_REPLACE_PATTERN = '#[^a-zA-Z0-9:_\-]#';

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
    }

    public function exportBlock(Mage_Cms_Model_Block $block)
    {
        if (preg_match('/^content_block_.+/', $block->getIdentifier())) {
            $this->exportCmsBlock($block);
            return;
        }

        if (preg_match('/^product_listing_content_block_.+/', $block->getIdentifier())) {
            $this->exportProductListingCmsBlock($block);
            return;
        }
    }

    /**
     * @return Mage_Cms_Model_Resource_Block_Collection
     */
    private function getCmsBlocks()
    {
        /** @var Mage_Cms_Model_Resource_Block_Collection $cmsBlocks */
        $cmsBlocks = Mage::getResourceModel('cms/block_collection')
            ->join(['block_store' => 'cms/block_store'], 'main_table.block_id=block_store.block_id', 'store_id')
            ->addExpressionFieldToSelect('block_id', "CONCAT({{block_id}},'_', {{store_id}})", [
                'block_id' => 'main_table.block_id',
                'store_id' => 'store_id',
            ]);
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
            ->addExpressionFieldToSelect('block_id', "CONCAT({{block_id}},'_', {{store_id}})", [
                'block_id' => 'main_table.block_id',
                'store_id' => 'store_id',
            ]);
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
        array_map([$this, 'exportCmsBlock'], iterator_to_array($cmsBlocks));
    }

    private function exportInProductListingCmsBlocks(Mage_Cms_Model_Resource_Block_Collection $cmsBlocks)
    {
        array_map([$this, 'exportProductListingCmsBlock'], iterator_to_array($cmsBlocks));
    }

    private function exportCmsBlock(Mage_Cms_Model_Block $block)
    {
        if ($block->getData('store_id') !== '0') {
            $blockId = $this->normalizeIdentifier($block->getIdentifier());
            $content = $this->getBlockContent($block);
            $context = $this->getBlockContext($block);
            $keyGeneratorParameters = [];

            $this->getApi()->triggerCmsBlockUpdate($blockId, $content, $context, $keyGeneratorParameters);
            return;
        }

        array_map(function(Mage_Core_Model_Store $store) use ($block) {
            $block->setData('store_id', $store->getId());
            $this->exportCmsBlock($block);
        }, Mage::app()->getStores());
    }

    private function exportProductListingCmsBlock(Mage_Cms_Model_Block $block)
    {
        if ($block->getData('store_id') !== '0') {
            $blockIdStringWithoutLastVariableToken = preg_replace('/_[^_]+$/', '', $block->getIdentifier());

            $categoryUrlSuffix = Mage::getStoreConfig(Mage_Catalog_Helper_Category::XML_PATH_CATEGORY_URL_SUFFIX);
            $categorySlug = preg_replace('/.*_/', '', $block->getIdentifier()) . '.' . $categoryUrlSuffix;
            $keyGeneratorParameters = ['url_key' => $categorySlug];

            $blockId = $this->normalizeIdentifier($blockIdStringWithoutLastVariableToken);
            $content = $this->getBlockContent($block);
            $context = $this->getBlockContext($block);

            $this->getApi()->triggerCmsBlockUpdate($blockId, $content, $context, $keyGeneratorParameters);
            return;
        }

        array_map(function(Mage_Core_Model_Store $store) use ($block) {
            $block->setData('store_id', $store->getId());
            $this->exportProductListingCmsBlock($block);
        }, Mage::app()->getStores());
    }

    /**
     * @param Mage_Cms_Model_Block $block
     * @return string[]
     */
    private function getBlockContext(Mage_Cms_Model_Block $block)
    {
        return [
            'locale'  => Mage::getStoreConfig('general/locale/code', $block->getData('store_id')),
            'website' => Mage::app()->getStore($block->getData('store_id'))->getCode(),
        ];
    }

    /**
     * @param string $identifier
     * @return string
     */
    private function normalizeIdentifier($identifier)
    {
        return preg_replace(self::SNIPPET_KEY_REPLACE_PATTERN, '-', $identifier);
    }

    private function disableCaches()
    {
        $this->disableBlockCache();
        $this->disableCollectionCache();
        $this->replaceCatalogCategoryHelperToAvoidWrongTranslations();
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
     * @param Mage_Cms_Model_Block $block
     * @return string
     */
    private function getBlockContent(Mage_Cms_Model_Block $block)
    {
        if (!$block->getIsActive()) {
            return '';
        }

        $this->disableCaches();

        $appEmulation = $this->getEmulationSingleton();
        $processor = $this->getCmsContentProcessor();

        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($block->getData('store_id'));

        $content = $processor->filter($block->getContent());

        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

        return $content;
    }

    /**
     * @return Mage_Core_Model_App_Emulation
     */
    private function getEmulationSingleton()
    {
        return Mage::getSingleton('core/app_emulation');
    }

    private function getCmsContentProcessor()
    {
        /** @var Mage_Cms_Helper_Data $cmsHelper */
        $cmsHelper = Mage::helper('cms');

        return $cmsHelper->getPageTemplateProcessor();
    }
}
