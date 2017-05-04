<?php

class StubCollection extends LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection
{
    protected $_selectAttributes = ['foo' => 123];

    public function publicSetItemAttributeValue(array $valueInfo)
    {
        return $this->_setItemAttributeValue($valueInfo);
    }
}
