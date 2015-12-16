<?php

interface InitializableProductExportTest extends \PHPUnit_Framework_Test
{
    /**
     * @return void
     */
    public function initTestExpectations();

    /**
     * @return string
     */
    public function getExpectationFileName();

    /**
     * @return string[]|int[]
     */
    public function getProductIdsForInitialization();

    /**
     * @return void
     */
    public function resetFactory();
}
