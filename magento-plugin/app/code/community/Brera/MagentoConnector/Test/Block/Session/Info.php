<?php

class Brera_MagentoConnector_Test_Block_Session_Info extends EcomDev_PHPUnit_Test_Case
{
    public function testPriceIsFormatted()
    {
        $this->markTestSkipped('Need to implement, but too much dependencies');
    }

    /**
     * @param int $customerId
     * @dataProvider getCustomerId
     */
    public function testIsCustomerLoggedIn($customerId)
    {
        $this->getCustomerWith($customerId);
        $block = new Brera_MagentoConnector_Block_Session_Info();
        $this->assertEquals((bool) $customerId, $block->isCustomerLoggedIn());
    }

    public function getCustomerId()
    {
        return [
            [[1]],
            [[null]],
            [[0]],
        ];
    }

    private function getCustomerWith($customerId)
    {
        $customer = $this->getModelMock('customer/customer', ['getId']);
        $customer->expects($this->any())
            ->method('getId')
            ->willReturn($customerId);

        $customerSession = $this->getModelMock('customer/session', ['getCustomer'], false, [], '', false);
        $customerSession->expects($this->any())->method('getCustomer')->willReturn($customer);
        $this->replaceByMock('singleton', 'customer/session', $customerSession);
    }
}
