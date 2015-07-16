<?php

class Brera_MagentoConnector_Test_Config_Config extends EcomDev_PHPUnit_Test_Case_Config
{
    public function testIsInstalled()
    {
        $this->assertModuleIsActive();
    }

    /**
     * @loadExpectation config
     */
    public function testHasInstallScripts()
    {
        $config = $this->expected('config');
        $this->assertSchemeSetupScriptVersions('0.0.1', $config->getVersion());
    }

    /**
     * @loadExpectation config
     */
    public function testCurrentVersion()
    {
        $config = $this->expected('config');
        $this->assertModuleVersion($config->getVersion());
    }

    public function testTableAliasExists()
    {
        $this->assertTableAlias('brera_magentoconnector/product_queue', 'brera_product_queue');
    }

    public function testModelAliasExists()
    {
        $this->assertModelAlias('brera_magentoconnector/observer', Brera_MagentoConnector_Model_Observer::class);
        $this->assertModelAlias(
            'brera_magentoconnector/xml_product_collection', Brera_MagentoConnector_Model_Xml_Product_Collection::class
        );
        $this->assertModelAlias('brera_magentoconnector/xml_product', Brera_MagentoConnector_Model_Xml_Product::class);
        $this->assertModelAlias(
            'brera_magentoconnector/product_queue_item', Brera_MagentoConnector_Model_Product_Queue_Item::class
        );
    }

    public function testResourceModelAliasExists()
    {
        $this->assertResourceModelAlias(
            'brera_magentoconnector/product_queue_item',
            Brera_MagentoConnector_Model_Resource_Product_Queue_Item::class
        );
        $this->assertResourceModelAlias(
            'brera_magentoconnector/product_queue_item_collection',
            Brera_MagentoConnector_Model_Resource_Product_Queue_Item_Collection::class
        );
    }

    public function testLayoutFileIsLoaded()
    {
        $this->assertLayoutFileDefined('frontend', 'brera_magentoconnector.xml');
        $this->assertLayoutFileExists('frontend', 'brera_magentoconnector.xml');
    }

    public function testForProductUpdate()
    {
        $events = [
            'catalog_product_save_after' => 'catalogProductSaveAfter',
            'catalog_product_attribute_update_after' => 'catalogProductAttributeUpdateAfter',
            'catalog_product_delete_after' => 'catalogProductDeleteAfter',
            'catalog_controller_product_delete' => 'catalogControllerProductDelete',
            'magmi_products_were_updated' => 'magmiProductsWereUpdated'
        ];
        foreach ($events as $eventname => $observerMethod) {
            $this->assertEventObserverDefined(
                'global',
                $eventname,
                'brera_magentoconnector/observer',
                $observerMethod
            );
        }
    }

    public function testForStockQtyIsChanged()
    {
        $events = [
            'cataloginventory_stock_item_save_commit_after' => 'cataloginventoryStockItemSaveCommitAfter',
            'sales_model_service_quote_submit_before' => 'salesModelServiceQuoteSubmitBefore',
            'sales_model_service_quote_submit_failure' => 'salesModelServiceQuoteSubmitFailure',
            'sales_order_item_cancel' => 'salesOrderItemCancel',
            'sales_order_creditmemo_save_after' => 'salesOrderCreditmemoSaveAfter',
            'magmi_stock_was_updated' => 'magmiStockWasUpdated'
        ];
        foreach ($events as $eventname => $observerMethod) {
            $this->assertEventObserverDefined(
                'global',
                $eventname,
                'brera_magentoconnector/observer',
                $observerMethod
            );
        }
    }

    public function testCobbyEventListenerIsRegistered()
    {
        $this->assertEventObserverDefined(
            'global',
            'cobby_after_product_import',
            'brera_magentoconnector/observer',
            'cobbyAfterProductImport'
        );
    }
}
