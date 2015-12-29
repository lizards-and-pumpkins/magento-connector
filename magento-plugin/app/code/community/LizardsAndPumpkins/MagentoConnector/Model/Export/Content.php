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

    /**
     * @param Mage_Cms_Model_Resource_Block_Collection $cmsBlocks
     */
    private function exportCmsBlocks($cmsBlocks)
    {
        foreach ($cmsBlocks as $block) {
            /* @var Mage_Cms_Model_Block $block */
            $context = [
                'locale'  => Mage::getStoreConfig('general/locale/code', $block->getStoreId()),
                'website' => Mage::app()->getStore($block->getStoreId())->getWebsite()->getCode(),
            ];
            $this->api->triggerCmsBlockUpdate(
                $this->normalizeIdentifier($block->getIdentifier()),
                $block->getContent(),
                $context
            );
        }
    }

    private function exportNonCmsBlocks()
    {
        $this->disableBlockCache();
        $this->disableCollectionCache();

        $this->replaceCatalogCategoryHelperToAvoidWrongTranslations();

        /** @var $appEmulation Mage_Core_Model_App_Emulation */
        $specialBlocks = Mage::getStoreConfig(self::XML_SPECIAL_BLOCKS);
        if (!is_array($specialBlocks)) {
            return;
        }

        $specialBlocks = array_keys($specialBlocks);

        /** @var $store Mage_Core_Model_Store */
        foreach (Mage::app()->getStores(true) as $store) {
            foreach ($specialBlocks as $blockName) {
                $layout = $this->emulateStore($store);
                $block = $layout->getBlock($blockName);
                if (!$block) {
                    continue;
                }

                $content = $block->toHtml();
                $context = [
                    'locale'  => Mage::getStoreConfig('general/locale/code', $store->getId()),
                    'website' => $store->getWebsite()->getCode()
                ];
                $this->api->triggerCmsBlockUpdate(
                    'content_block_' . $this->normalizeIdentifier($block->getNameInLayout()),
                    $content,
                    $context
                );
            }
        }
    }

    /**
     * @param Mage_Core_Model_Store $store
     *
     * @return Mage_Core_Model_Layout
     */
    private function emulateStore($store)
    {
        $storeId = $store->getId();
        Mage::app()->getLocale()->emulate($storeId);
        Mage::app()->setCurrentStore(Mage::app()->getStore($storeId));

        Mage::getDesign()->setArea('frontend')
            ->setStore($storeId);

        $designChange = Mage::getSingleton('core/design')
            ->loadChange($storeId);

        if ($designChange->getData()) {
            Mage::getDesign()->setPackageName($designChange->getPackage())
                ->setTheme($designChange->getTheme());
        }

        $layout = Mage::getModel('core/layout');
        $layout->setArea(Mage_Core_Model_App_Area::AREA_FRONTEND);
        $layout->getUpdate()->load('default');
        $layout->generateXml();
        $layout->generateBlocks();
        return $layout;
    }

    /**
     * @param string $identifier
     *
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
}
