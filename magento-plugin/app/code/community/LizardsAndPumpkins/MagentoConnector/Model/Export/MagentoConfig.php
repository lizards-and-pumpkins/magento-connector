<?php

class LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig
{
    /**
     * @param string $store
     * @return string
     */
    public function getLocaleFrom($store)
    {
        return Mage::getStoreConfig('general/locale/code', $store);
    }

    /**
     * @return string
     */
    public function getLocalPathForProductExport()
    {
        return Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/local_path_for_product_export');
    }

    /**
     * @return string
     */
    public function getLocalFilenameTemplate()
    {
        return strftime(Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/local_filename_template'));
    }

    /**
     * @return Mage_Core_Model_Store[]
     */
    public function getStoresWithIdKeys()
    {
        return Mage::app()->getStores();
    }
}
