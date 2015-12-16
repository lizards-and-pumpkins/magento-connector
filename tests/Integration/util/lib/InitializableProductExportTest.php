<?php

interface InitializableProductExportTest
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
}
