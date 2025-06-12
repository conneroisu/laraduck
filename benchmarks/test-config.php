<?php

require_once __DIR__ . '/bootstrap.php';

echo "Config check:\n";
var_dump($container['config']->get('database.connections.duckdb'));
var_dump($container['config']->get('database.default'));
var_dump($container['config']->get('database'));

echo "\nTrying direct capsule:\n";
var_dump(array_keys($capsule->getDatabaseManager()->getConnections()));

echo "\nTrying to get duckdb connection from capsule:\n";
try {
    $conn = $capsule->getConnection('duckdb');
    echo "✓ Got connection from capsule\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}