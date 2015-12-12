<?php

class LizardsAndPumpkins_MagentoConnector_Test_Model_SourceTableDataProviderTest extends PHPUnit_Framework_TestCase
{
    public function testUndefinedValue()
    {
        $defaultValues = [];
        $storeSpecificValues = [];

        $sourceTableData = $this->setupMagentoDependencies($defaultValues, $storeSpecificValues);

        $this->assertNull($sourceTableData->getValue("series", 2791, 0));
    }

    public function testOnlyDefaultValues()
    {
        $defaultValues = [
            [
                "attribute_code" => "series",
                "option_id"      => 2791,
                "attribute_id"   => 157,
                "sort_order"     => 0,
                "value_id"       => 14578,
                "store_id"       => 0,
                "value"          => "Cap",
            ],
        ];

        $storeSpecificValues = [];

        $sourceTableData = $this->setupMagentoDependencies($defaultValues, $storeSpecificValues);

        $this->assertEquals(
            'Cap',
            $sourceTableData->getValue("series", 2791, 0)
        );
    }

    public function testStoreSpecificValues()
    {
        $defaultValues = [];

        $storeSpecificValues = [
            [
                "attribute_code" => "series",
                "option_id"      => 2791,
                "attribute_id"   => 157,
                "sort_order"     => 0,
                "value_id"       => 14578,
                "store_id"       => 1,
                "value"          => "Cap",
            ],
        ];

        $sourceTableData = $this->setupMagentoDependencies($defaultValues, $storeSpecificValues);

        $this->assertEquals(
            'Cap',
            $sourceTableData->getValue("series", 2791, 1)
        );
    }

    public function testMixedValues()
    {
        $defaultValues = [
            [
                "attribute_code" => "series",
                "option_id"      => 2791,
                "attribute_id"   => 157,
                "sort_order"     => 0,
                "value_id"       => 14578,
                "store_id"       => 0,
                "value"          => "Cap",
            ],
        ];

        $storeSpecificValues = [
            [
                "attribute_code" => "series",
                "option_id"      => 2791,
                "attribute_id"   => 157,
                "sort_order"     => 0,
                "value_id"       => 14578,
                "store_id"       => 1,
                "value"          => "CapStore1",
            ],
        ];

        $sourceTableData = $this->setupMagentoDependencies($defaultValues, $storeSpecificValues);

        $this->assertEquals(
            'CapStore1',
            $sourceTableData->getValue("series", 2791, 1)
        );
        $this->assertEquals(
            'Cap',
            $sourceTableData->getValue("series", 2791, 2)
        );
        $this->assertEquals(
            'Cap',
            $sourceTableData->getValue("series", 2791, 0)
        );
    }

    /**
     * @param $defaultValues
     * @param $storeSpecificValues
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_SourceTableDataProvider
     */
    private function setupMagentoDependencies($defaultValues, $storeSpecificValues)
    {
        $store1 = $this->getMock(Mage_Core_Model_Store::class, ['getId']);
        $store1->method('getId')->willReturn(1);
        $store2 = $this->getMock(Mage_Core_Model_Store::class, ['getId']);
        $store2->method('getId')->willReturn(2);
        $stores = [$store1, $store2];

        /** @var $config PHPUnit_Framework_MockObject_MockObject|LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig */
        $config = $this->getMock(LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig::class);
        $config->method('getStoresWithIdKeys')->willReturn($stores);

        $resultDefaultValues = $this->getMock(Zend_Db_Statement_Interface::class, ['fetchAll']);
        $resultDefaultValues->method('fetchAll')->willReturn($defaultValues);

        $resultStoreViewValues = $this->getMock(Zend_Db_Statement_Interface::class, ['fetchAll']);
        $resultStoreViewValues->method('fetchAll')->willReturn($storeSpecificValues);

        $connection = $this->getMock(Varien_Db_Adapter_Interface::class, ['query']);
        $connection->method('query')->willReturnOnConsecutiveCalls($resultDefaultValues, $resultStoreViewValues);

        /** @var $resource PHPUnit_Framework_MockObject_MockObject|Mage_Core_Model_Resource */
        $resource = $this->getMock(Mage_Core_Model_Resource::class, ['getTableName', 'getConnection']);
        $resource->method('getTableName')->willReturn('tablename');
        $resource->method('getConnection')->willReturn($connection);

        $sourceTableData = new LizardsAndPumpkins_MagentoConnector_Model_Export_SourceTableDataProvider(
            $resource, $config
        );
        return $sourceTableData;
    }
}
