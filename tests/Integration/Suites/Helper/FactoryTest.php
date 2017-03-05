<?php

class FactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testReturnsLizardsAndPumpkinsApi()
    {
        Mage::app()->getStore()->setConfig('lizardsAndPumpkins/magentoconnector/api_url', 'http://example.com');
        
        /** @var \LizardsAndPumpkins_MagentoConnector_Helper_Factory $helper */
        $helper = Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
        $result = $helper->createLizardsAndPumpkinsApi();
        $this->assertInstanceOf(\LizardsAndPumpkins\MagentoConnector\Api\Api::class, $result);
    }
}
