<?php

use Brera\MagentoConnector\Api\Api;
use Brera\MagentoConnector\XmlBuilder\StockBuilder;

class Brera_MagentoConnector_Model_Export_Stock
{
    /**
     * @var Zend_Queue_Message[]
     */
    private $messagesToDelete = [];

    /**
     * @var Brera_MagentoConnector_Model_StockXmlUploader
     */
    private $stockUploader;

    public function __construct()
    {
        $this->stockUploader = new Brera_MagentoConnector_Model_StockXmlUploader();
    }

    public function export()
    {
        $helper = Mage::helper('brera_magentoconnector/export');
        $stockBuilder = new StockBuilder();

        /* @var $messages Zend_Queue_Message_Iterator */
        $messages = $helper->getStockUpdatesToExport();
        while ($messages->count()) {
            $ids = $this->collectIdsFromMessages($messages);
            $this->buildStockUpdateXml($ids, $stockBuilder);
            $this->markMessagesToDelete($messages);
            $messages = $helper->getStockUpdatesToExport();
        }

        $this->uploadXml($stockBuilder->getXml());
        $this->triggerStockUpdateApi();
        $helper->deleteStockMessages($this->messagesToDelete);
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
        $ids = [];
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
            ->join(['product' => 'catalog/product'], 'product_id=product.entity_id', 'sku');

        foreach ($stockItems->getData() as $stockItem) {
            $stockBuilder->addStockData($stockItem['sku'], $stockItem['qty']);
        }
    }

    /**
     * @param $xml
     */
    private function uploadXml($xml)
    {
        $this->getStockUploader()->upload($xml);
    }

    /**
     * @return Brera_MagentoConnector_Model_StockXmlUploader
     */
    private function getStockUploader()
    {
        return $this->stockUploader;
    }

    private function triggerStockUpdateApi()
    {
        $apiUrl = Mage::getStoreConfig('brera/magentoconnector/api_url');
        $api = new Api($apiUrl);
        $api->triggerProductStockImport($this->getStockUploader()->getFileName());
    }
}
