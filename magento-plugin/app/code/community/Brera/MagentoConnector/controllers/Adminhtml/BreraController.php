<?php

class Brera_MagentoConnector_Adminhtml_BreraController extends Mage_Adminhtml_Controller_Action
{
    public function exportAllProductsAction()
    {
        $exporter = Mage::getModel('brera_magentoconnector/export_exporter');
        $exporter->exportAllProducts();
    }
}
