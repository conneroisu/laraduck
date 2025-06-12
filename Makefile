.PHONY: help install test lint benchmark benchmark-performance benchmark-comparison clean

help:
	@echo "Laraduck - DuckDB Driver for Laravel"
	@echo ""
	@echo "Available commands:"
	@echo "  make install              - Install dependencies"
	@echo "  make test                 - Run PHPUnit tests"
	@echo "  make lint                 - Run code linting"
	@echo "  make benchmark            - Run all benchmarks"
	@echo "  make benchmark-performance - Run performance benchmarks only"
	@echo "  make benchmark-comparison - Run database comparison benchmarks"
	@echo "  make clean                - Clean generated files"

install:
	composer install

test:
	./vendor/bin/phpunit

lint:
	@echo "Running PHP linting..."
	@find src tests benchmarks -name "*.php" -exec php -l {} \;

benchmark:
	@echo "Running all benchmarks..."
	@php benchmarks/run-benchmarks.php all

benchmark-performance:
	@echo "Running performance benchmarks..."
	@php benchmarks/run-benchmarks.php performance

benchmark-comparison:
	@echo "Running comparison benchmarks..."
	@php benchmarks/run-benchmarks.php comparison

clean:
	@echo "Cleaning generated files..."
	@rm -f benchmark_results.json comparison_results.json
	@rm -f benchmark_data.parquet benchmark_data.csv benchmark_export.parquet
	@echo "Clean complete."