<?php

interface InitializableProductExportTest
{
    /**
     * @param string[]|string $productIds
     * @return void
     */
    public function initTestExpectations($productIds);

    /**
     * @return string
     */
    public function getExpectationFileName();
}
