<?php

declare(strict_types=1);

class LizardsAndPumpkins_MagentoConnector_Block_Adminhtml_Version_FormTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->block = new LizardsAndPumpkins_MagentoConnector_Block_Adminhtml_Version_Form();
        $this->block->toHtml();
    }

    public function testForm()
    {
        /** @var Varien_Data_Form $form */
        $form = $this->block->getForm();
        $this->assertInstanceOf(Varien_Data_Form::class, $form);
        $fieldsets = $form->getElements();
        $this->assertCount(1, $fieldsets);
        $this->assertInstanceOf(Varien_Data_Form_Element_Text::class, $form->getElement('current_version'));
        $this->assertInstanceOf(Varien_Data_Form_Element_Text::class, $form->getElement('previous_version'));
    }
}
