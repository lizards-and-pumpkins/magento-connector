<?php

class LizardsAndPumpkins_MagentoConnector_Adminhtml_LizardsAndPumpkinsController
    extends Mage_Adminhtml_Controller_Action
{
    public function exportAllCategoriesAction()
    {
        try {
            /** @var LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter $exporter */
            $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_catalogExporter');
            $exporter->exportAllCategories();
            $categoriesExporterd = $exporter->getNumberOfCategoriesExported();
            Mage::getSingleton('core/session')->addSuccess(
                sprintf('All %s categories exported.', $categoriesExporterd)
            );
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
        $this->_redirect('/');
    }

    public function exportAllProductsAction()
    {
        try {
            /** @var LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter $exporter */
            $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_catalogExporter');
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
            /** @var LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter $exporter */
            $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_catalogExporter');
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
            Mage::helper('lizardsAndPumpkins_magentoconnector/export')->addAllProductIdsToStockExport();
            Mage::getModel('lizardsAndPumpkins_magentoconnector/export_stock')->export();
            Mage::getSingleton('core/session')->addSuccess('All stocks exported');
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
        $this->_redirect('/');
    }

    public function exportAllCmsBlocksAction()
    {
        try {
            $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_content');
            $exporter->export();
            Mage::getSingleton('core/session')->addSuccess('All cms blocks exported');
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
        $this->_redirect('/');

    }
}
