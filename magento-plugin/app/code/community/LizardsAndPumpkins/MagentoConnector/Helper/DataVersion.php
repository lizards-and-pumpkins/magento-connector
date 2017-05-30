<?php

class LizardsAndPumpkins_MagentoConnector_Helper_DataVersion extends Mage_Core_Helper_Abstract
{
    /**
     * @return string
     */
    public function getTargetVersion()
    {
        return (string) Mage::getStoreConfig('lizardsAndPumpkins/data_version/for_export');
    }
}
