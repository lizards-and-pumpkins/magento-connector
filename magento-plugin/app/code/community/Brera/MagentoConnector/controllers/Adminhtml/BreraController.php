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
}
