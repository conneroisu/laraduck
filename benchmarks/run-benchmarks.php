#!/usr/bin/env php
<?php

require_once __DIR__ . '/bootstrap.php';

use Laraduck\EloquentDuckDB\Benchmarks\BenchmarkRunner;
use Laraduck\EloquentDuckDB\Benchmarks\ComparisonBenchmark;

// Simple CLI argument parsing
$command = $argv[1] ?? 'all';

echo "Laraduck Benchmark Suite\n";
echo "========================\n\n";

switch ($command) {
    case 'all':
        echo "Running all benchmarks...\n\n";
        
        // Run Laraduck-specific benchmarks
        echo "1. Running Laraduck Performance Benchmarks\n";
        echo str_repeat('-', 60) . "\n";
        $runner = new BenchmarkRunner();
        $runner->run();
        
        echo "\n\n2. Running Database Comparison Benchmarks\n";
        echo str_repeat('-', 60) . "\n";
        $comparison = new ComparisonBenchmark();
        $comparison->run();
        break;
        
    case 'performance':
        echo "Running Laraduck performance benchmarks...\n\n";
        $runner = new BenchmarkRunner();
        $runner->run();
        break;
        
    case 'comparison':
        echo "Running database comparison benchmarks...\n\n";
        $comparison = new ComparisonBenchmark();
        $comparison->run();
        break;
        
    case 'help':
    default:
        echo "Usage: php run-benchmarks.php [command]\n\n";
        echo "Commands:\n";
        echo "  all         - Run all benchmarks (default)\n";
        echo "  performance - Run Laraduck performance benchmarks only\n";
        echo "  comparison  - Run database comparison benchmarks only\n";
        echo "  help        - Show this help message\n";
        echo "\nExamples:\n";
        echo "  php benchmarks/run-benchmarks.php\n";
        echo "  php benchmarks/run-benchmarks.php performance\n";
        echo "  php benchmarks/run-benchmarks.php comparison\n";
        break;
}