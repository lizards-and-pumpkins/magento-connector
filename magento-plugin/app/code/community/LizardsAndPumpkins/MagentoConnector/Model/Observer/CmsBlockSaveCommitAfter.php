<?php

declare(strict_types=1);

class LizardsAndPumpkins_MagentoConnector_Model_Observer_CmsBlockSaveCommitAfter
{
    public function observe(Varien_Event_Observer $observer)
    {
        $block = $observer->getData('object');
        $this->exportBlock($block);
    }

    private function exportBlock(Mage_Cms_Model_Block $block)
    {
        array_map(function($storeId) use ($block) {
            $block->setData('store_id', $storeId);
            $this->getCmsBlockExporter()->exportBlock($block);
        }, $block->getData('stores'));
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_Content
     */
    private function getCmsBlockExporter()
    {
        return Mage::getModel('lizardsAndPumpkins_magentoconnector/export_content');
    }
}
