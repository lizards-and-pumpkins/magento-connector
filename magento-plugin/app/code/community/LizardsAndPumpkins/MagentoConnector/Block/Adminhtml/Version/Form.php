<?php

declare(strict_types=1);

class LizardsAndPumpkins_MagentoConnector_Block_Adminhtml_Version_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form([
            'id'     => 'edit_form',
            'action' => $this->getData('action'),
            'method' => 'post',
        ]);

        $fieldset = $form->addFieldset(
            'fieldset',
            ['legend' => Mage::helper('lizardsAndPumpkins_magentoconnector')->__('Current Version from API')]
        );

        $fieldset->addField('current_version', 'text', [
            'label'    => Mage::helper('lizardsAndPumpkins_magentoconnector')->__('Current Version'),
            'name'     => 'current_version',
            'required' => true,
            'value'    => $this->getVersion()['data']['current_version'],
        ]);

        $fieldset->addField('previous_version', 'text', [
            'label'    => Mage::helper('lizardsAndPumpkins_magentoconnector')->__('Previous Version'),
            'name'     => 'previous_version',
            'required' => true,
            'readonly' => true,
            'value'    => $this->getVersion()['data']['previous_version'],
        ]);

        $form->setUseContainer(true);

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
