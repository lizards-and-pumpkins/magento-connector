<?php

declare(strict_types = 1);

class LizardsAndPumpkins_MagentoConnector_Model_Adminhtml_System_Config_Source_Store
{
    public function toOptionArray()
    {
        return array_map(function (Mage_Core_Model_Store $store) {
            return [
                'value' => $store->getId(),
                'label' => sprintf('%s | %s', $store->getWebsite()->getName(), $store->getName()),
            ];
        }, Mage::app()->getStores());
    }
}
