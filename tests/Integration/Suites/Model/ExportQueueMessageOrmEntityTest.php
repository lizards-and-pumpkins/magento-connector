<?php

/**
 * @covers LizardsAndPumpkins_MagentoConnector_Model_ExportQueue_Message
 * @covers LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message
 * @covers LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message_Collection
 * @group bisect
 */
class ExportQueueMessageOrmEntityTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_ExportQueue_Message
     */
    private function createMessageInstance()
    {
        return Mage::getModel('lizardsAndPumpkins_magentoconnector/exportQueue_message');
    }

    protected function setUp()
    {
        Mage::getSingleton('core/resource')->getConnection('default_setup')->beginTransaction();
    }

    protected function tearDown()
    {
        Mage::getSingleton('core/resource')->getConnection('default_setup')->rollBack();
    }

    /**
     * @param string $type
     * @param string $dataVersion
     * @param int $objectId
     * @return LizardsAndPumpkins_MagentoConnector_Model_ExportQueue_Message
     */
    private function createExportQueueMessage($type, $dataVersion, $objectId)
    {
        $message = $this->createMessageInstance();
        $message->setType($type);
        $message->setDataVersion($dataVersion);
        $message->setObjectId($objectId);
        $message->save();

        return $message;
    }

    public function testExportQueueMessageSaveAndLoad()
    {
        $messageA = $this->createExportQueueMessage('catalog_product', 'foo', 123);

        $messageB = $this->createMessageInstance();
        $messageB->load($messageA->getId());
        
        $this->assertSame($messageA->getId(), $messageB->getId());
        $this->assertSame($messageA->getType(), $messageB->getType());
        $this->assertSame($messageA->getDataVersion(), $messageB->getDataVersion());
        $this->assertSame($messageA->getObjectId(), $messageB->getObjectId());
        $this->assertSame($messageA->getCreatedAt(), $messageB->getCreatedAt());
    }

    public function testSavingExportQueueMessageSetsCreatedAt()
    {
        $message = $this->createMessageInstance();
        $message->setType('foo');
        $message->setDataVersion('bar');
        $message->setObjectId('123');
        
        $this->assertNull($message->getCreatedAt());
        
        $message->save();

        $this->assertNotNull($message->getCreatedAt());
    }
    
    
    public function testCollectionLoad()
    {
        $messageA = $this->createExportQueueMessage('catalog_product', 'foo', 1);
        $messageB = $this->createExportQueueMessage('catalog_product', 'foo', 2);
        $messageC = $this->createExportQueueMessage('catalog_product', 'foo', 3);
        
        /** @var LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message_Collection $collection */
        $collection = Mage::getResourceModel('lizardsAndPumpkins_magentoconnector/exportQueue_message_collection');
        
        $messages = $collection->getItems();
        
        $this->assertArrayHasKey($messageA->getId(), $messages);
        $this->assertArrayHasKey($messageB->getId(), $messages);
        $this->assertArrayHasKey($messageC->getId(), $messages);
    }

    public function testSettingAndGettingACollectionMessageTypeFilter()
    {
        /** @var LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message_Collection $collection */
        $collection = Mage::getResourceModel('lizardsAndPumpkins_magentoconnector/exportQueue_message_collection');
        
        $this->assertNull($collection->getMessageType());
        $collection->addFieldToFilter(LizardsAndPumpkins_MagentoConnector_Model_ExportQueue_Message::TYPE, 'foo');
        $this->assertSame('foo', $collection->getMessageType());
    }

    public function testCollectionThrowsExceptionIfMessageTypeFilterIsOverwritten()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The queue message type is already set');
        
        /** @var LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message_Collection $collection */
        $collection = Mage::getResourceModel('lizardsAndPumpkins_magentoconnector/exportQueue_message_collection');
        $collection->addFieldToFilter(LizardsAndPumpkins_MagentoConnector_Model_ExportQueue_Message::TYPE, 'foo');
        $collection->addFieldToFilter(LizardsAndPumpkins_MagentoConnector_Model_ExportQueue_Message::TYPE, 'bar');
    }

    public function testCollectionReturnsObjectIdsByType()
    {
        $dummyDataVersion = 'foo';
        
        $this->createExportQueueMessage('catalog_product', $dummyDataVersion, 1);
        $this->createExportQueueMessage('catalog_product', $dummyDataVersion, 2);
        $this->createExportQueueMessage('catalog_category', $dummyDataVersion, 3);
        $this->createExportQueueMessage('catalog_category', $dummyDataVersion, 4);

        /** @var LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message_Collection $collection */
        $collection = Mage::getResourceModel('lizardsAndPumpkins_magentoconnector/exportQueue_message_collection');

        $productIds = $collection->getObjectIdsByType('catalog_product');
        $this->assertContains(1, $productIds);
        $this->assertContains(2, $productIds);
        $this->assertNotContains(3, $productIds);
        $this->assertNotContains(4, $productIds);

        $categoryIds = $collection->getObjectIdsByType('catalog_category');
        $this->assertContains(3, $categoryIds);
        $this->assertContains(4, $categoryIds);
        $this->assertNotContains(1, $categoryIds);
        $this->assertNotContains(2, $categoryIds);
    }
}
