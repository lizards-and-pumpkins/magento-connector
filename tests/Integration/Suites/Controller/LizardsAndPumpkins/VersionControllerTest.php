<?php

declare(strict_types=1);

use LizardsAndPumpkins\MagentoConnector\Api\Api;

require Mage::getBaseDir('app')
    . '/code/community/LizardsAndPumpkins/MagentoConnector/controllers/Adminhtml/LizardsAndPumpkins/VersionController.php';

class VersionControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Api|PHPUnit_Framework_MockObject_MockObject
     */
    private $api;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Adminhtml_LizardsAndPumpkins_VersionController
     */
    private $controller;

    protected function setUp()
    {
        $sessionMock = $this->getMockBuilder(Mage_Admin_Model_Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['isAllowed', 'getUser'])
            ->getMock();
        $sessionMock->method('isAllowed')->willReturn(true);
        $sessionMock->method('getUser')->willReturn($this->createMock(Mage_Admin_Model_User::class));

        Mage::register('_singleton/admin/session', $sessionMock);

        $this->api = $this->createMock(Api::class);
        $request = $this->getMockBuilder(Zend_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getRequestedRouteName',
                    'getRequestedControllerName',
                    'getRequestedActionName',
                    'getRouteName',
                    'getControllerName',
                    'getActionName',
                    'isDispatched',
                ]
            )
            ->getMock();

        $request->method('getRouteName')->willReturn('adminhtml');
        $request->method('getControllerName')->willReturn('lizardsandpumpkins_version');
        $request->method('getActionName')->willReturn('index');
        $request->method('getRequestedRouteName')->willReturn('adminhtml');
        $request->method('getRequestedControllerName')->willReturn('lizardsandpumpkins_version');
        $request->method('getRequestedActionName')->willReturn('index');
        $request->method('isDispatched')->willReturn(true);

        $response = $this->createMock(Zend_Controller_Response_Http::class);
        $this->controller = new LizardsAndPumpkins_MagentoConnector_Adminhtml_LizardsAndPumpkins_VersionController(
            $request, $response, [], $this->api
        );
    }

    public function testGetCurrentVersionAndSetOnBlock()
    {
        $version = [
            'data' => [
                'current_version'  => '1',
                'previous_version' => '0',
            ],
        ];
        $this->api->expects($this->once())->method('getCurrentVersion')->willReturn($version);

        $this->controller->dispatch('index');

        $this->assertEquals(
            $version,
            $this->controller->getLayout()->getBlock('version.container')->getChild('form')->getVersion()
        );
    }
}
