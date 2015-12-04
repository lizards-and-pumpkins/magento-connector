<?php

class Brera_MagentoConnector_Adminhtml_BreraController extends Mage_Adminhtml_Controller_Action
{
    public function exportAllProductsAction()
    {
        try {
            /** @var Brera_MagentoConnector_Model_Export_CatalogExporter $exporter */
            $exporter = Mage::getModel('brera_magentoconnector/export_catalogExporter');
            $exporter->exportAllProducts();
            $productsExported = $exporter->getNumberOfProductsExported();
            $categoriesExporterd = $exporter->getNumberOfCategoriesExported();
            Mage::getSingleton('core/session')->addSuccess(
                sprintf('All (%s) products and %s categories exported.', $productsExported, $categoriesExporterd)
            );
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
        $this->_redirect('/');
    }

    public function exportQueuedProductUpdatesAction()
    {
        try {
            /** @var Brera_MagentoConnector_Model_Export_CatalogExporter $exporter */
            $exporter = Mage::getModel('brera_magentoconnector/export_catalogExporter');
            $exporter->exportProductsInQueue();
            $productsExported = $exporter->getNumberOfProductsExported();
            $categoriesExporterd = $exporter->getNumberOfCategoriesExported();
            Mage::getSingleton('core/session')->addSuccess(
                sprintf('%s products and %s categories from queue exported.', $productsExported, $categoriesExporterd)
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
