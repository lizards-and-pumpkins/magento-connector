<?php

class LizardsAndPumpkins_MagentoConnector_Model_Adminhtml_System_Config_Backend_ArrayBackend
    extends Mage_Core_Model_Config_Data
{
    protected function _beforeSave()
    {
        $value = $this->getValue();
        if (is_array($value)) {
            if ('' === $value[0]) {
                $this->setValue('');
            } else {
                $this->setValue(implode(',', $value));
            }
        }
        return parent::_beforeSave();
    }
}
