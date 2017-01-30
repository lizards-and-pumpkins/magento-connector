<?php

declare(strict_types=1);

use LizardsAndPumpkins\MagentoConnector\Api\Api;
use LizardsAndPumpkins\MagentoConnector\Api\RequestFailedException;

require Mage::getBaseDir('app')
    . '/code/community/LizardsAndPumpkins/MagentoConnector/controllers/Adminhtml/LizardsAndPumpkins/VersionController.php';

class LizardsAndPumpkins_MagentoConnector_VersionControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Mage_Adminhtml_Model_Session|PHPUnit_Framework_MockObject_MockObject
     */
    private $adminhtmlSessionMock;
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
        $adminSession = $this->getMockBuilder(Mage_Admin_Model_Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['isAllowed', 'getUser'])
            ->getMock();
        $adminSession->method('isAllowed')->willReturnCallback(function ($acl) {
            if ($acl === 'system/index') {
                return false;
            }
            return true;
        });
        $adminSession->method('getUser')->willReturn($this->createMock(Mage_Admin_Model_User::class));

        Mage::unregister('_singleton/admin/session');
        Mage::register('_singleton/admin/session', $adminSession);

        $this->adminhtmlSessionMock = $this->getMockBuilder(Mage_Adminhtml_Model_Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['addError'])
            ->getMock();

        Mage::unregister('_singleton/adminhtml/session');
        Mage::register('_singleton/adminhtml/session', $this->adminhtmlSessionMock);

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

    public function testGetCurrentVersionThrowsException()
    {
        $exceptionMessage = 'Request Failed was not catched!';
        $this->api->method('getCurrentVersion')->willThrowException(new RequestFailedException($exceptionMessage));
        $this->adminhtmlSessionMock->expects($this->once())->method('addError')
            ->with($this->equalTo($exceptionMessage));

        $this->controller->dispatch('index');

    }

    public function testFormAction()
    {
        $html = $this->controller->getLayout()->getBlock('version.container')->getChild('form')->toHtml();
        $this->assertRegExp('#action=".*admin/lizardsAndPumpkins_version/save.*"#', $html);
        $this->assertGreaterThan(0, strlen($html));
    }

    public function testSetVersion()
    {
        $newVersion = uniqid('lap', true);
        $this->api->expects($this->once())->method('setCurrentVersion')->with($this->equalTo($newVersion));

        $this->dispatchUpdate($newVersion);
    }

    public function testSetVersionThrowsException()
    {
        $exceptionMessage = 'Request Failed was not catched!';
        $this->api->method('setCurrentVersion')->willThrowException(new RequestFailedException($exceptionMessage));
        $this->adminhtmlSessionMock->expects($this->once())->method('addError')
            ->with($this->equalTo($exceptionMessage));

        $this->dispatchUpdate('123');
    }

    public function testRedirectAfterSetVersion()
    {
        $newVersion = uniqid('lap', true);
        $this->response->expects($this->once())->method('setRedirect')
            ->with($this->stringContains('admin/lizardsAndPumpkins_version/index'));

        $this->dispatchUpdate($newVersion);
    }

    public function testErrorOnEmptyVersion()
    {
        $this->request->method('getRequestedActionName')->willReturn('save');
        $this->request->method('getActionName')->willReturn('save');

        $this->adminhtmlSessionMock->expects($this->once())->method('addError');

        $this->controller->dispatch('save');
    }

    private function dispatchUpdate(string $newVersion)
    {
        $this->request->method('getRequestedActionName')->willReturn('save');
        $this->request->method('getActionName')->willReturn('save');

        $this->request->method('getParam')->willReturnCallback(function ($param) use ($newVersion) {
            if ($param === 'current_version') {
                return $newVersion;
            }
            return null;
        });

        $this->controller->dispatch('save');
    }
}
