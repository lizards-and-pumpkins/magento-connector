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
}
