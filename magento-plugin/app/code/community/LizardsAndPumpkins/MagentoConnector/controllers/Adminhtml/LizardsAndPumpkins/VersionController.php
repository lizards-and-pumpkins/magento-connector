<?php

declare(strict_types=1);

use LizardsAndPumpkins\MagentoConnector\Api\Api;
use LizardsAndPumpkins\MagentoConnector\Api\GuzzleAdapter;

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
        $this->api = $api ?? new Api(
                Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/api_url'),
                new GuzzleAdapter()
            );
    }

    public function indexAction()
    {
        $version = $this->api->getCurrentVersion();
        $this->loadLayout();
        $this->getLayout()->getBlock('version.container')->getChild('form')->setVersion($version);
        $this->renderLayout();
    }

    public function saveAction()
    {
        $version = $this->getRequest()->getParam('current_version');
        if (!is_string($version) || $version === '') {
            /** @var Mage_Admin_Model_Session $session */
            $session = Mage::getSingleton('admin/session');
            $session->addWarning('Current version must not be empty!');
            return $this->_redirect('*/lizardsandpumpkins_version/index');
        }

        $this->api->setCurrentVersion($version);

        $this->_redirect('*/lizardsandpumpkins_version/index');
    }
}
