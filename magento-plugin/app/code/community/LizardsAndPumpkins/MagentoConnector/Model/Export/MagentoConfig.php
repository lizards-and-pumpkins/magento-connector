<?php

class LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig
{
    /**
     * @param string|Mage_Core_Model_Store|int $store
     * @return string
     */
    public function getLocaleFrom($store)
    {
        return Mage::getStoreConfig('general/locale/code', $store);
    }
}
