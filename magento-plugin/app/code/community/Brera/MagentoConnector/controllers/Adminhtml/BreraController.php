<?php

class Brera_MagentoConnector_Adminhtml_BreraController extends Mage_Adminhtml_Controller_Action
{
    public function exportAllProductsAction()
    {
        try {
            /** @var Brera_MagentoConnector_Model_Export_Exporter $exporter */
            $exporter = Mage::getModel('brera_magentoconnector/export_exporter');
            $productsExported = $exporter->exportAllProducts();
            Mage::getSingleton('core/session')->addSuccess(sprintf('All (%s) products exported.', $productsExported));
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
        $this->_redirect('/');
    }

    public function exportQueuedProductUpdatesAction()
    {
        try {
            /** @var Brera_MagentoConnector_Model_Export_Exporter $exporter */
            $exporter = Mage::getModel('brera_magentoconnector/export_exporter');
            $productsExported = $exporter->exportProductsInQueue();
            Mage::getSingleton('core/session')->addSuccess(
                sprintf('%s products from queue exported.', $productsExported)
            );
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
        $this->_redirect('/');
    }

    public function exportAllStocksAction()
    {
        try {
            Mage::helper('brera_magentoconnector/export')->addAllProductIdsToStockExport();
            Mage::getModel('brera_magentoconnector/export_stock')->export();
            Mage::getSingleton('core/session')->addSuccess('All stocks exported');
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
        $this->_redirect('/');
    }

    public function exportAllCmsBlocksAction()
    {
        try {
            $exporter = Mage::getModel('brera_magentoconnector/export_cms_block');
            $exporter->export();
            Mage::getSingleton('core/session')->addSuccess('All cms blocks exported');
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
        $this->_redirect('/');

    }
}
