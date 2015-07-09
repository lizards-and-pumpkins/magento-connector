<?php

class Brera_MagentoConnector_Test_Config_Config extends EcomDev_PHPUnit_Test_Case_Config
{
    /**
     * @test
     */
    public function isInstalled()
    {
        $this->assertModuleIsActive();
    }

    /**
     * @test
     * @loadExpectation config
     */
    public function hasInstallScripts()
    {
        $config = $this->expected('config');
        $this->assertSchemeSetupScriptVersions('0.0.1', $config->getVersion());
    }

    /**
     * @test
     */
    public function tableAliasExists()
    {
        $this->assertTableAlias('brera_magentoconnector/product_queue', 'brera_product_queue');
    }

    /**
     * @test
     */
    public function modelAliasExists()
    {
        $this->assertModelAlias('brera_magentoconnector/observer', Brera_MagentoConnector_Model_Observer::class);
    }

    /**
     * @test
     */
    public function listenOnProductSaveAfter()
    {
        $this->assertEventObserverDefined(
            'global',
            'catalog_product_save_after',
            'brera_magentoconnector/observer',
            'catalogProductSaveAfter'
        );
    }

    /**
     * @test
     */
    public function listenOnProductDeleteAfter()
    {
        $this->assertEventObserverDefined(
            'global',
            'catalog_product_delete_after',
            'brera_magentoconnector/observer',
            'catalogProductDeleteAfter'
        );
    }

    /**
     * @test
     */
    public function listenOnAttributeUpdate()
    {
        $this->assertEventObserverDefined(
            'global',
            'catalog_product_attribute_update_after',
            'brera_magentoconnector/observer',
            'catalogProductAttributeUpdateAfter'
        );
    }

    /**
     * @test
     */
    public function listenOnMassDelete()
    {
        $this->assertEventObserverDefined(
            'global',
            'catalog_controller_product_delete',
            'brera_magentoconnector/observer',
            'catalogControllerProductDelete'
        );
    }

    public function testLayoutFileIsLoaded()
    {
        $this->assertLayoutFileDefined('frontend', 'brera/magentoconnector.xml');
        $this->assertLayoutFileExists('frontend', 'brera/magentoconnector.xml');
    }

    public function testForStockQtyIsChanged()
    {
        $events = [
            'cataloginventory_stock_item_save_commit_after' => 'cataloginventoryStockItemSaveCommitAfter',
            'sales_model_service_quote_submit_before' => 'salesModelServiceQuoteSubmitBefore',
            'sales_model_service_quote_submit_failure' => 'salesModelServiceQuoteSubmitFailure',
            'sales_order_item_cancel' => 'salesOrderItemCancel',
            'sales_order_creditmemo_save_after' => 'salesOrderCreditmemoSaveAfter',
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
}
