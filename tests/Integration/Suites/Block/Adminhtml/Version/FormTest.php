<?php

class LizardsAndPumpkins_MagentoConnector_Block_Adminhtml_Version_FormTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->block = new LizardsAndPumpkins_MagentoConnector_Block_Adminhtml_Version_Form();
    }

    public function testForm()
    {
        $this->block->toHtml();
        /** @var Varien_Data_Form $form */
        $form = $this->block->getForm();
        $this->assertInstanceOf(Varien_Data_Form::class, $form);
        $fieldsets = $form->getElements();
        $this->assertCount(1, $fieldsets);
        $this->assertInstanceOf(Varien_Data_Form_Element_Text::class, $form->getElement('current_version'));
        $this->assertInstanceOf(Varien_Data_Form_Element_Text::class, $form->getElement('previous_version'));
    }

    public function testVersionsAreSet()
    {
        $previousVersion = '42';
        $currentVersion = '4711';

        $version = [
            'data' => [
                'current_version'  => $currentVersion,
                'previous_version' => $previousVersion,
            ],
        ];

        $this->block->setVersion($version);

        $this->block->toHtml();
        /** @var Varien_Data_Form $form */
        $form = $this->block->getForm();

        $this->assertEquals($currentVersion, $form->getElement('current_version')->getValue());
        $this->assertEquals($previousVersion, $form->getElement('previous_version')->getValue());
    }
}
