<?php

use LizardsAndPumpkins\MagentoConnector\Api\Api;
use LizardsAndPumpkins\MagentoConnector\Api\RequestFailedException;

class LizardsAndPumpkins_MagentoConnector_Adminhtml_LizardsAndPumpkins_VersionController
    extends Mage_Adminhtml_Controller_Action
{
    /**
     * @var Api
     */
    private $api;

    public function __construct(
        Zend_Controller_Request_Abstract $request,
        Zend_Controller_Response_Abstract $response,
        array $invokeArgs = [],
        Api $api = null
    ) {
        parent::__construct($request, $response, $invokeArgs);
        if ($api) {
            $this->api = $api;
        } else {
            /** @var \LizardsAndPumpkins_MagentoConnector_Helper_Factory $helper */
            $helper = Mage::helper('lizardsAndPumpkins_magentoconnector/factory');
            $this->api = $helper->createLizardsAndPumpkinsApi();
        }
    }

    public function indexAction()
    {
        /** @var Mage_Admin_Model_Session $session */
        $session = Mage::getSingleton('adminhtml/session');
        $this->loadLayout();

        try {
            $version = $this->api->getCurrentVersion();
            $this->getLayout()->getBlock('version.container')->getChild('form')->setVersion($version);
        } catch (RequestFailedException $e) {
            $session->addError($e->getMessage());
        }

        $this->renderLayout();
    }

    public function saveAction()
    {
        /** @var Mage_Admin_Model_Session $session */
        $session = Mage::getSingleton('adminhtml/session');

        $version = $this->getRequest()->getParam('current_version');
        if (!is_string($version) || '' === trim($version)) {
            $session->addError('Current data version must not be empty!');
            return $this->_redirect('*/lizardsAndPumpkins_version/index');
        }
        try {
            $this->api->setCurrentVersion($version);
        } catch (RequestFailedException $e) {
            $session->addError($e->getMessage());
        }

        $this->_redirect('*/lizardsAndPumpkins_version/index');
    }
}
