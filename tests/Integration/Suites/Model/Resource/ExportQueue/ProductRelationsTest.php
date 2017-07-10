<?php

/**
 * @covers LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_ProductRelations
 * @group bisect
 */
class LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_ProductRelationsTest
    extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Mage_Core_Model_Resource
     */
    private $resource;

    /**
     * @param string $sku
     * @return Mage_Catalog_Model_Product
     */
    private function createSimpleProduct($sku)
    {
        $currentStore = Mage::app()->getStore()->getCode();
        Mage::app()->setCurrentStore(Mage_Core_Model_Store::ADMIN_CODE);
        $simple = Mage::getModel('catalog/product');
        $simple->setData('sku', $sku);
        $simple->setData('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $simple->setData('type_id', Mage_Catalog_Model_Product_Type::TYPE_SIMPLE);
        $simple->setData('attribute_set_id', $simple->getDefaultAttributeSetId());
        $simple->save();
        Mage::app()->setCurrentStore($currentStore);

        return $simple;
    }

    /**
     * @param string $sku
     * @param int[] $simpleProductIds
     * @return Mage_Catalog_Model_Product
     */
    private function createConfigurableProduct($sku, array $simpleProductIds)
    {
        $currentStore = Mage::app()->getStore()->getCode();
        Mage::app()->setCurrentStore(Mage_Core_Model_Store::ADMIN_CODE);
        $configurable = Mage::getModel('catalog/product');
        $configurable->setData('sku', $sku);
        $configurable->setData('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $configurable->setData('type_id', Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE);
        $configurable->setData('attribute_set_id', $configurable->getDefaultAttributeSetId());
        $configurable->save();

        Mage::getResourceModel('catalog/product_type_configurable')
            ->saveProducts($configurable, $simpleProductIds);
        
        Mage::app()->setCurrentStore($currentStore);

        return $configurable;
    }

    protected function setUp()
    {
        $this->resource = Mage::getSingleton('core/resource');
        $this->resource->getConnection('default_setup')->beginTransaction();
    }

    protected function tearDown()
    {
        $this->resource->getConnection('default_setup')->rollBack();
    }
    
    public function testReturnsTheGivenProductIdForIfThereIsNoParentProduct()
    {
        $product1 = $this->createSimpleProduct('foo');
        $product2 = $this->createSimpleProduct('bar');
        $productIds = [$product1->getId(), $product2->getId()];

        $productRelations = new LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_ProductRelations();
        $this->assertSame($productIds, $productRelations->replaceWithParentProductIds($productIds));
    }

    public function testReplacesTheIdsOfAssociatedSimpleProductsWithTheParentProductId()
    {
        $simple = $this->createSimpleProduct('foo');
        $configurable = $this->createConfigurableProduct('bar', [$simple->getId()]);
        $productRelations = new LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_ProductRelations();
        $this->assertSame([$configurable->getId()], $productRelations->replaceWithParentProductIds([$simple->getId()]));
    }
}
