<?php

use Brera\MagentoConnector\Api\Api;

class Brera_MagentoConnector_Model_Export_Cms_Block
{
    /**
     * @var Api
     */
    private $api;

    public function export()
    {
        $cmsBlocks = $this->getAllCmsBlocksWithStoreId();
        $this->api = $this->getApi();
        $this->exportCmsBlocks($cmsBlocks);
    }

    /**
     * @return Mage_Core_Model_Resource_Db_Collection_Abstract
     */
    private function getAllCmsBlocksWithStoreId()
    {
        $cmsBlocks = Mage::getResourceModel('cms/block_collection')
            ->join(array('block_store' => 'cms/block_store'), 'main_table.block_id=block_store.block_id', 'store_id')
            ->addExpressionFieldToSelect(
                'block_id',
                "CONCAT({{block_id}}, {{store_id}})",
                array(
                    'block_id' => 'main_table.block_id',
                    'store_id' => 'store_id',
                )
            );
        return $cmsBlocks;
    }

    /**
     * @return Api
     */
    private function getApi()
    {
        $apiUrl = Mage::getStoreConfig('brera/magentoconnector/api_url');
        return new Api($apiUrl);
    }

    /**
     * @param $cmsBlocks
     */
    private function exportCmsBlocks($cmsBlocks)
    {
        foreach ($cmsBlocks as $block) {
            /* @var Mage_Cms_Model_Block $block */
            $context = array(
                'locale' => Mage::getStoreConfig('general/locale/code', $block->getStoreId())
            );
            $this->api->triggerCmsBlockUpdate($block->getIdentifier(), $block->getContent(), $context);
        }
    }
}
