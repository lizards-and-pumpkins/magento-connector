<?php

use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_CompleteCatalogExporter as CompleteCatalogExporter;
use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_QueueExporter as QueueExporter;

class LizardsAndPumpkins_MagentoConnector_Adminhtml_LizardsAndPumpkinsController
    extends Mage_Adminhtml_Controller_Action
{
    public function exportAllCategoriesAction()
    {
        try {
            /** @var CompleteCatalogExporter $exporter */
            $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/catalogExport_completeCatalogExporter');
            //$exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_catalogExporter');
            $exporter->exportAllCategories();
            Mage::getSingleton('core/session')->addSuccess(sprintf('All categories exported.'));
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
        $this->_redirect('/');
    }

    public function exportAllProductsAction()
    {
        try {
            /** @var CompleteCatalogExporter $exporter */
            $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/catalogExport_completeCatalogExporter');
            $exporter->exportAllProducts();
            Mage::getSingleton('core/session')->addSuccess(sprintf('All products exported.'));
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
        $this->_redirect('/');
    }

    public function exportQueuedProductUpdatesAction()
    {
        try {
            /** @var QueueExporter $exporter */
            $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/catalogExport_queueExporter');
            $exporter->exportQueuedProducts();
            Mage::getSingleton('core/session')->addSuccess(sprintf('Products from queue exported.'));
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
        $this->_redirect('/');
    }

    public function exportAllCmsBlocksAction()
    {
        try {
            /** @var LizardsAndPumpkins_MagentoConnector_Model_CmsExport_BlockExport $exporter */
            $exporter = Mage::getModel('lizardsAndPumpkins_magentoconnector/cmsExport_blockExport');
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
}
