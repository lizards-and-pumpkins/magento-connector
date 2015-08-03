<?php

class Brera_MagentoConnector_Adminhtml_BreraController extends Mage_Adminhtml_Controller_Action
{
    public function exportAllProductsAction()
    {
        /** @var Brera_MagentoConnector_Model_Export_Exporter $exporter */
        $exporter = Mage::getModel('brera_magentoconnector/export_exporter');
        $exporter->exportAllProducts();
        $this->_redirect('/');
    }

    public function exportQueuedProductUpdatesAction()
    {
        try {
            /** @var Brera_MagentoConnector_Model_Export_Exporter $exporter */
            $exporter = Mage::getModel('brera_magentoconnector/export_exporter');
            $exporter->exportProductsInQueue();
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addNotice($e->getMessage());
        }
        $this->_redirect('/');
    }
}
