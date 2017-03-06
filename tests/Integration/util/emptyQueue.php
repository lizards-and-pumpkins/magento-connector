#!/usr/bin/env php
<?php

declare(strict_types = 1);

require __DIR__ . '/../bootstrap.php';

$connection = Mage::getSingleton('core/resource')->getConnection('default_write');
$connection->delete('message');

echo "Queue empty\n";
