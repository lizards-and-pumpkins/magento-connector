<?php

/**
 * @covers LizardsAndPumpkins_MagentoConnector_Model_Resource_Setup
 */
class LizardsAndPumpkins_MagentoConnector_Model_Resource_SetupTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Mage_Core_Model_Resource_Setup|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockSetup;

    /**
     * @var Varien_Db_Adapter_Interface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockConnection;

    /**
     * @var Varien_Db_Ddl_Table|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockTable;

    protected function setUp()
    {
        $this->mockSetup = $this->createMock(Mage_Core_Model_Resource_Setup::class);
        $this->mockConnection = $this->createMock(Varien_Db_Adapter_Interface::class);
        $this->mockTable = $this->createMock(Varien_Db_Ddl_Table::class);
        $this->mockConnection->method('newTable')->willReturn($this->mockTable);
    }
    
    public function testCreatesTable()
    {
        $this->mockSetup->method('getTable')->willReturn('lizards_pumpkins_export_queue');
        
        $this->mockTable->expects($this->atLeast(5))->method('addColumn');
        $this->mockTable->expects($this->atLeastOnce())->method('addIndex');
        
        $this->mockConnection->expects($this->once())->method('createTable')->with($this->mockTable);
        
        $setup = new LizardsAndPumpkins_MagentoConnector_Model_Resource_Setup();
        $setup->createQueueTable($this->mockSetup, $this->mockConnection);
    }
}
