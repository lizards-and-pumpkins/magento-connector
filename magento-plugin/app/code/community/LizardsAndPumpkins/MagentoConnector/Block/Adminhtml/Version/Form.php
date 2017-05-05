<?php

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

        $versions = $this->getVersion()['data'];
        $fieldset->addField('current_version', 'text', [
            'label'    => Mage::helper('lizardsAndPumpkins_magentoconnector')->__('Current Data Version from API'),
            'name'     => 'current_version',
            'required' => true,
            'value'    => isset($versions['current_version']) ? $versions['current_version'] : '',
        ]);

        $fieldset->addField('previous_version', 'text', [
            'label'    => Mage::helper('lizardsAndPumpkins_magentoconnector')->__('Previous Data Version'),
            'name'     => 'previous_version',
            'readonly' => true,
            'value'    => isset($versions['previous_version']) ? $versions['previous_version'] : '',
        ]);

        $form->setUseContainer(true);

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
