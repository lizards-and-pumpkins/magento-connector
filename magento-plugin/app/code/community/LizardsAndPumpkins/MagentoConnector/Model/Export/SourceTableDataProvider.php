<?php

class LizardsAndPumpkins_MagentoConnector_Model_Export_SourceTableDataProvider
{
    /**
     * @var Mage_Core_Model_Resource
     */
    private $resource;

    /**
     * @var array[]
     */
    private $attributeValues;
    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig
     */
    private $config;

    public function __construct(
        Mage_Core_Model_Resource $resource,
        LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig $config
    ) {
        $this->resource = $resource;
        $this->config = $config;
        $this->loadOptionValues();
    }

    private function loadOptionValues()
    {
        $this->setupDefaultValues();
        $this->setupStoreViewSpecifivValues();
    }

    /**
     * @param int $attributeId
     * @param int $optionId
     * @param int $store
     * @return string
     */
    public function getValue($attributeId, $optionId, $store)
    {
        return $this->attributeValues[$store][$attributeId][$optionId];
    }

    private function setupDefaultValues()
    {
        $attributeOption = $this->resource->getTableName('eav/attribute_option');
        $attributeOptionValue = $this->resource->getTableName('eav/attribute_option_value');

        $query = "SELECT * FROM $attributeOption o
                    INNER JOIN $attributeOptionValue v
                      ON o.option_id = v.option_id
                    WHERE store_id = 0";

        $result = $this->resource->getConnection('core_write')->query($query);
        foreach ($result->fetchAll() as $row) {
            $this->attributeValues[0][$row['attribute_id']][$row['option_id']] = $row['value'];
            foreach ($this->config->getStoresWithIdKeys() as $store) {
                $this->attributeValues[$store->getId()][$row['attribute_id']][$row['option_id']] = $row['value'];
            }
        }
    }

    private function setupStoreViewSpecifivValues()
    {
        $attributeOption = $this->resource->getTableName('eav/attribute_option');
        $attributeOptionValue = $this->resource->getTableName('eav/attribute_option_value');

        $query = "SELECT * FROM $attributeOption o
                    INNER JOIN $attributeOptionValue v
                      ON o.option_id = v.option_id
                    WHERE store_id <> 0";

        $result = $this->resource->getConnection('core_write')->query($query);
        foreach ($result->fetchAll() as $row) {
            $this->attributeValues[$row['store_id']][$row['attribute_id']][$row['option_id']] = $row['value'];
        }
    }
}
