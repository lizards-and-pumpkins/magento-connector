<?php

use LizardsAndPumpkins\MagentoConnector\Api\Api;

class LizardsAndPumpkins_MagentoConnector_Adminhtml_LizardsAndPumpkinsController
    extends Mage_Adminhtml_Controller_Action
{
    public function exportAllCategoriesAction()
    {
        try {
            /** @var LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter $exporter */
            $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_catalogExporter');
            $filename = $exporter->exportAllCategories();
            $this->triggerCatalogUpdateApi($filename);
            $categoriesExported = $exporter->getNumberOfCategoriesExported();
            Mage::getSingleton('core/session')->addSuccess(
                sprintf('All %s categorie(s) exported.', $categoriesExported)
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
            $filename = $exporter->exportAllProducts();
            $this->triggerCatalogUpdateApi($filename);
            $productsExported = $exporter->getNumberOfProductsExported();
            $categoriesExported = $exporter->getNumberOfCategoriesExported();
            Mage::getSingleton('core/session')->addSuccess(
                sprintf('All products (%s) and %s categorie(s) exported.', $productsExported, $categoriesExported)
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
            $filename = $exporter->exportProductsInQueue();
            $this->triggerCatalogUpdateApi($filename);
            $productsExported = $exporter->getNumberOfProductsExported();
            $categoriesExported = $exporter->getNumberOfCategoriesExported();
            Mage::getSingleton('core/session')->addSuccess(
                sprintf('%s product(s) and %s categorie(s) from queue exported.', $productsExported, $categoriesExported)
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
            $filename = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_stock')->export();
            $apiUrl = Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/api_url');
            (new Api($apiUrl))->triggerProductStockImport($filename);
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
            // todo 1: decouple CMS and non-CMS block exports
            // todo 2: decouple triggering the API request from the export
            // todo 3: add display of count like for products and categories
            Mage::getSingleton('core/session')->addSuccess('All cms blocks exported');
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
        $this->_redirect('/');
    }

    /**
     * @param string $filename
     */
    private function triggerCatalogUpdateApi($filename)
    {
        $apiUrl = Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/api_url');
        (new Api($apiUrl))->triggerProductImport($filename);
    }
}
