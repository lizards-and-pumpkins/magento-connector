<?php

use LizardsAndPumpkins\MagentoConnector\Api\Api;

class LizardsAndPumpkins_MagentoConnector_Model_Export_Content
{
    const SNIPPET_KEY_REPLACE_PATTERN = '#[^a-zA-Z0-9:_\-]#';

    const XML_SPECIAL_BLOCKS = 'lizardsAndPumpkins/magentoconnector/cms_special_blocks';

    /**
     * @var Api
     */
    private $api;

    public function export()
    {
        $cmsBlocks = $this->getAllCmsBlocksWithStoreId();
        $this->api = $this->getApi();
        $this->exportCmsBlocks($cmsBlocks);
        $this->exportNonCmsBlocks();
    }

    /**
     * @return Mage_Cms_Model_Resource_Block_Collection
     */
    private function getAllCmsBlocksWithStoreId()
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
        $cmsBlocks->addFieldToFilter(
            [
                'identifier',
                'identifier',
            ],
            [
                ['like' => 'product_listing_content_block_%'],
                ['like' => 'content_block_%']
            ]
        );
        return $cmsBlocks;
    }

    /**
     * @return Api
     */
    private function getApi()
    {
        $apiUrl = Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/api_url');
        return new Api($apiUrl);
    }

    private function exportCmsBlocks(Mage_Cms_Model_Resource_Block_Collection $cmsBlocks)
    {
        array_map(function (Mage_Cms_Model_Block $block) {
            $blockId = $this->normalizeIdentifier($block->getIdentifier());
            $context = [
                'locale'  => Mage::getStoreConfig('general/locale/code', $block->getData('store_id')),
                'website' => Mage::app()->getStore($block->getData('store_id'))->getWebsite()->getCode(),
            ];
            $this->api->triggerCmsBlockUpdate($blockId, $block->getContent(), $context);
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

                $layout = $this->getLayout();
                $block = $layout->getBlock($blockIdentifier);

                if (null === $block) {
                    return;
                }

                $blockId = 'content_block_' . $this->normalizeIdentifier($block->getNameInLayout());
                $content = $block->toHtml();
                $context = [
                    'locale'  => Mage::getStoreConfig('general/locale/code', $store->getId()),
                    'website' => $store->getWebsite()->getCode()
                ];

                $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

                $this->api->triggerCmsBlockUpdate($blockId, $content, $context);
            }, Mage::app()->getStores(true));
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
     * @return Mage_Core_Model_Layout
     */
    private function getLayout()
    {
        /** @var Mage_Core_Model_Layout $layout */
        $layout = Mage::getModel('core/layout');
        $layout->getUpdate()->load('default');
        $layout->generateXml();
        $layout->generateBlocks();

        return $layout;
    }
}
