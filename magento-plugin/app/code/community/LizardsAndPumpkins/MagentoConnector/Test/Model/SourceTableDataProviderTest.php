<?php

class LizardsAndPumpkins_MagentoConnector_Test_Model_SourceTableDataProviderTest extends PHPUnit_Framework_TestCase
{
    public function testEmptyStringIsReturnedIfRequestedAttributeOptionIsUndefined()
    {
        $defaultValues = [];
        $storeSpecificValues = [];

        $sourceTableData = $this->setupMagentoDependencies($defaultValues, $storeSpecificValues);

        $storeId = 0;
        $attributeCode = "series";
        $optionId = 2791;
        $this->assertEquals('', $sourceTableData->getValue($storeId, $attributeCode, $optionId));
    }

    public function testValueForAdminAndOtherStoresWhenOnlyAdminStoreValuesAreGiven()
    {
        $value = "Cap";
        $store = 0;
        $store1 = 1;
        $store2 = 2;
        $attributeCode = "series";
        $optionId = 2791;
        $defaultValues = [
            [
                "attribute_code" => $attributeCode,
                "option_id"      => $optionId,
                "store_id"       => $store,
                "value"          => $value,
            ],
        ];

        $storeSpecificValues = [];

        $sourceTableData = $this->setupMagentoDependencies($defaultValues, $storeSpecificValues);

        $this->assertEquals(
            $value,
            $sourceTableData->getValue($store, $attributeCode, $optionId)
        );
        $this->assertEquals(
            $value,
            $sourceTableData->getValue($store1, $attributeCode, $optionId)
        );
        $this->assertEquals(
            $value,
            $sourceTableData->getValue($store2, $attributeCode, $optionId)
        );
    }

    public function testValueWhenOnlyStoreSpecificStoreValuesAreGiven()
    {
        $defaultValues = [];

        $value = "Cap";
        $optionId = 2791;
        $attributeCode = "series";
        $store = 1;
        $storeSpecificValues = [
            [
                "attribute_code" => $attributeCode,
                "option_id"      => $optionId,
                "store_id"       => $store,
                "value"          => $value,
            ],
        ];

        $sourceTableData = $this->setupMagentoDependencies($defaultValues, $storeSpecificValues);

        $this->assertEquals(
            'Cap',
            $sourceTableData->getValue($store, $attributeCode, $optionId)
        );
    }

    public function testValuesForAdminAndSpecificStoresWhenBothIsGiven()
    {
        $attributeCode = "series";
        $optionId = 2791;
        $storeId0 = 0;
        $storeId1 = 1;
        $storeId2 = 2;
        $value = "Cap";
        $valueStore1 = "CapStore1";

        $defaultValues = [
            [
                "attribute_code" => $attributeCode,
                "option_id"      => $optionId,
                "store_id"       => $storeId0,
                "value"          => $value,
            ],
        ];

        $storeSpecificValues = [
            [
                "attribute_code" => $attributeCode,
                "option_id"      => $optionId,
                "store_id"       => $storeId1,
                "value"          => $valueStore1,
            ],
        ];

        $sourceTableData = $this->setupMagentoDependencies($defaultValues, $storeSpecificValues);


        $this->assertEquals(
            $valueStore1,
            $sourceTableData->getValue($storeId1, $attributeCode, $optionId)
        );

        $this->assertEquals(
            $value,
            $sourceTableData->getValue($storeId2, $attributeCode, $optionId)
        );

        $this->assertEquals(
            $value,
            $sourceTableData->getValue($storeId0, $attributeCode, $optionId)
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
