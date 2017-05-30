<?php

class LizardsAndPumpkins_MagentoConnector_Helper_DataVersionTest extends \PHPUnit\Framework\TestCase
{
    public function testReturnsTheConfiguredTargetDataVersion()
    {
        $dataVersion = 'foo';
        Mage::app()->getStore()->setConfig('lizardsAndPumpkins/data_version/for_export', $dataVersion);
        
        /** @var LizardsAndPumpkins_MagentoConnector_Helper_DataVersion $helper */
        $helper = Mage::helper('lizardsAndPumpkins_magentoconnector/dataVersion');
        
        $this->assertSame($dataVersion, $helper->getTargetVersion());
    }
}
