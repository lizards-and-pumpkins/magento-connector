<?php

declare(strict_types=1);

use LizardsAndPumpkins\MagentoConnector\Api\Api;

require Mage::getBaseDir('app')
    . '/code/community/LizardsAndPumpkins/MagentoConnector/controllers/Adminhtml/LizardsAndPumpkins/VersionController.php';

class VersionControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Mage_Admin_Model_Session|PHPUnit_Framework_MockObject_MockObject
     */
    private $sessionMock;
    /**
     * @var Zend_Controller_Response_Http|PHPUnit_Framework_MockObject_MockObject
     */
    private $response;
    /**
     * @var Zend_Controller_Request_Http|PHPUnit_Framework_MockObject_MockObject
     */
    private $request;
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
        $this->sessionMock = $this->getMockBuilder(Mage_Admin_Model_Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['isAllowed', 'getUser', 'addWarning'])
            ->getMock();
        $this->sessionMock->method('isAllowed')->willReturn(true);
        $this->sessionMock->method('getUser')->willReturn($this->createMock(Mage_Admin_Model_User::class));

        Mage::unregister('_singleton/admin/session');
        Mage::register('_singleton/admin/session', $this->sessionMock);

        $this->api = $this->createMock(Api::class);
        $this->request = $this->getMockBuilder(Zend_Controller_Request_Http::class)
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
                    'getParam',
                ]
            )
            ->getMock();

        $this->request->method('getRouteName')->willReturn('adminhtml');
        $this->request->method('getControllerName')->willReturn('lizardsandpumpkins_version');
        $this->request->method('getRequestedRouteName')->willReturn('adminhtml');
        $this->request->method('getRequestedControllerName')->willReturn('lizardsandpumpkins_version');
        $this->request->method('isDispatched')->willReturn(true);

        $this->response = $this->createMock(Zend_Controller_Response_Http::class);
        $this->controller = new LizardsAndPumpkins_MagentoConnector_Adminhtml_LizardsAndPumpkins_VersionController(
            $this->request, $this->response, [], $this->api
        );
    }

    public function testGetCurrentVersionAndSetOnBlock()
    {
        $this->request->method('getRequestedActionName')->willReturn('index');
        $this->request->method('getActionName')->willReturn('index');

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

    public function testSetVersion()
    {
        $newVersion = uniqid('lap', true);
        $this->api->expects($this->once())->method('setCurrentVersion')->with($this->equalTo($newVersion));

        $this->dispatchUpdate($newVersion);
    }

    public function testRedirectAfterSetVersion()
    {
        $newVersion = uniqid('lap', true);
        $this->response->expects($this->once())->method('setRedirect')
            ->with($this->stringContains('admin/lizardsandpumpkins_version/index'));

        $this->dispatchUpdate($newVersion);
    }

    public function testErrorOnEmptyVersion()
    {
        $this->request->method('getRequestedActionName')->willReturn('update');
        $this->request->method('getActionName')->willReturn('update');

        $this->sessionMock->expects($this->once())->method('addWarning');

        $this->controller->dispatch('update');
    }

    private function dispatchUpdate(string $newVersion)
    {
        $this->request->method('getRequestedActionName')->willReturn('update');
        $this->request->method('getActionName')->willReturn('update');

        $this->request->method('getParam')->willReturnCallback(function ($param) use ($newVersion) {
            if ($param === 'current_version') {
                return $newVersion;
            }
            return null;
        });

        $this->controller->dispatch('update');
    }
}
