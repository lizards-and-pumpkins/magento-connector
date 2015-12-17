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
        $this->setupStoreViewSpecificValues();
    }

    /**
     * @param int $store
     * @param string $attributeCode
     * @param int $optionId
     * @return string
     */
    public function getValue($store, $attributeCode, $optionId)
    {
        if (isset($this->attributeValues[$store][$attributeCode][$optionId])) {
            return $this->attributeValues[$store][$attributeCode][$optionId];
        }
        return '';
    }

    private function setupDefaultValues()
    {
        $attributeOption = $this->resource->getTableName('eav/attribute_option');
        $attributeOptionValue = $this->resource->getTableName('eav/attribute_option_value');
        $attribute = $this->resource->getTableName('eav/attribute');

        $query = "SELECT attribute_code, o.option_id, value, store_id
                    FROM $attributeOption o
                    INNER JOIN $attributeOptionValue v ON o.option_id = v.option_id
                    INNER JOIN $attribute a ON o.attribute_id = a.attribute_id
                    WHERE store_id = 0";

        $result = $this->resource->getConnection('core_write')->query($query);
        foreach ($result->fetchAll() as $row) {
            $this->attributeValues[0][$row['attribute_code']][$row['option_id']] = $row['value'];
            foreach ($this->config->getStoresWithIdKeys() as $store) {
                $this->attributeValues[$store->getId()][$row['attribute_code']][$row['option_id']] = $row['value'];
            }
        }
    }

    private function setupStoreViewSpecificValues()
    {
        $attributeOption = $this->resource->getTableName('eav/attribute_option');
        $attributeOptionValue = $this->resource->getTableName('eav/attribute_option_value');
        $attribute = $this->resource->getTableName('eav/attribute');

        $query = "SELECT attribute_code, o.option_id, value, store_id
                    FROM $attributeOption o
                    INNER JOIN $attributeOptionValue v ON o.option_id = v.option_id
                    INNER JOIN $attribute a ON o.attribute_id = a.attribute_id
                    WHERE store_id <> 0";

        $result = $this->resource->getConnection('core_write')->query($query);
        foreach ($result->fetchAll() as $row) {
            $this->attributeValues[$row['store_id']][$row['attribute_code']][$row['option_id']] = $row['value'];
        }
    }
}
