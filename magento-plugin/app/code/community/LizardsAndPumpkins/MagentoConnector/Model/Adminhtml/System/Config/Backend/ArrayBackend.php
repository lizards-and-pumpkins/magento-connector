<?php

declare(strict_types = 1);

class LizardsAndPumpkins_MagentoConnector_Model_Adminhtml_System_Config_Backend_ArrayBackend
    extends Mage_Core_Model_Config_Data
{
    protected function _beforeSave()
    {
        $value = $this->getValue();
        if (is_array($value)) {
            $valueToStore = '' !== $value[0] ?
                implode(',', $value) :
                '';
            $this->setValue($valueToStore);
        }
        return parent::_beforeSave();
    }
}
