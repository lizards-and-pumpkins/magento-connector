<?php

use Brera\MagentoConnector\XmlBuilder\StockBuilder;

class Brera_MagentoConnector_Model_Export_Stock
{
    private $messagesToDelete = array();

    public function export()
    {
        $helper = Mage::helper('brera_magentoconnector/export');
        $stockBuilder = new StockBuilder();
        $messagesToDelete = array();

        /* @var $messages Zend_Queue_Message_Iterator */
        $messages = $helper->getStockUpdatesToExport();
        while ($messages->count()) {
            $ids = $this->collectIdsFromMessages($messages);
            $this->buildStockUpdateXml($ids, $stockBuilder);
            $this->markMessagesToDelete($messages);
            $messages = $helper->getStockUpdatesToExport();
        }

        $helper->deleteStockMessages($this->messagesToDelete);
        $this->uploadXml($stockBuilder->getXml());
    }

    /**
     * @param Zend_Queue_Message_Iterator $messages
     */
    private function markMessagesToDelete($messages)
    {
        foreach ($messages as $message) {
            $this->messagesToDelete[] = $message;
        }
    }

    /**
     * @param Zend_Queue_Message_Iterator $messages
     * @return int[]
     */
    private function collectIdsFromMessages($messages)
    {
        $ids = array();
        foreach ($messages as $message) {
            /* @var $message Zend_Queue_Message */
            $ids[] = $message->body;
        }
        return $ids;
    }

    /**
     * @param int[] $ids
     * @param StockBuilder $stockBuilder
     * @return string
     */
    private function buildStockUpdateXml($ids, $stockBuilder)
    {
        $stockItems = Mage::getResourceModel('cataloginventory/stock_item_collection')
            ->addProductsFilter($ids)
            ->join(array('product' => 'catalog/product'), 'product_id=product.entity_id', 'sku');

        foreach ($stockItems->getData() as $stockItem) {
            $stockBuilder->addStockData($stockItem['sku'], $stockItem['qty']);
        }
    }

    /**
     * @param $xml
     */
    private function uploadXml($xml)
    {
        $stockUploader = new Brera_MagentoConnector_Model_StockXmlUploader();
        $stockUploader->upload($xml);
    }
}
