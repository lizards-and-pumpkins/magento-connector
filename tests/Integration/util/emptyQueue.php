#!/usr/bin/env php
<?php

require __DIR__ . '/../bootstrap.php';

$connection = Mage::getSingleton('core/resource')->getConnection('default_write');
$connection->delete('message');

echo "done\n";
