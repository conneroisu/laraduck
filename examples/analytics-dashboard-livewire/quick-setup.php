<?php

/**
 * Quick Setup for Laraduck Analytics Dashboard
 * A simplified setup script that works without complex bootstrapping
 */

echo "🦆 Laraduck Analytics Dashboard Quick Setup\n";
echo "==========================================\n\n";

// Check if we're in the right directory
if (!file_exists('composer.json')) {
    echo "❌ Please run this script from the analytics-dashboard-livewire directory\n";
    exit(1);
}

// Step 1: Check if vendor directory exists
if (!is_dir('vendor')) {
    echo "❌ Vendor directory not found. Please run 'composer install' first.\n";
    exit(1);
}

echo "1. Setting up Laravel environment...\n";

// Create .env file if it doesn't exist
if (!file_exists('.env')) {
    if (file_exists('.env.example')) {
        copy('.env.example', '.env');
        echo "   ✅ .env file created from .env.example\n";
    } else {
        // Create a basic .env file
        $envContent = "APP_NAME=LaraduckAnalytics
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=" . realpath('database/database.sqlite') . "

DUCKDB_DATABASE=" . realpath('database/analytics.duckdb') . "
DUCKDB_READ_ONLY=false
DUCKDB_MEMORY_LIMIT=4GB
DUCKDB_MAX_MEMORY=8GB
DUCKDB_THREADS=4
";
        file_put_contents('.env', $envContent);
        echo "   ✅ .env file created\n";
    }
}

// Generate app key
echo "\n2. Generating application key...\n";
passthru('php artisan key:generate', $return);
if ($return === 0) {
    echo "   ✅ Application key generated\n";
} else {
    echo "   ⚠️  Could not generate application key automatically\n";
}

// Create database directories
echo "\n3. Creating database directories...\n";
if (!is_dir('database')) {
    mkdir('database', 0755, true);
}
touch('database/database.sqlite');
echo "   ✅ SQLite database file created\n";

// Check for DuckDB
echo "\n4. Checking DuckDB installation...\n";
$duckdbCheck = shell_exec('which duckdb 2>/dev/null');
if (empty($duckdbCheck)) {
    echo "   ⚠️  DuckDB not found in PATH\n";
    echo "   💡 Install DuckDB: https://duckdb.org/docs/installation/\n";
    echo "   📝 You can still continue, but analytics features may not work\n";
} else {
    echo "   ✅ DuckDB found: " . trim($duckdbCheck) . "\n";
}

// Create storage directories
echo "\n5. Setting up storage directories...\n";
$dirs = [
    'storage/app',
    'storage/framework/cache',
    'storage/framework/sessions', 
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
echo "   ✅ Storage directories created\n";

// Install npm dependencies if package.json exists
if (file_exists('package.json')) {
    echo "\n6. Installing frontend dependencies...\n";
    passthru('npm install', $return);
    if ($return === 0) {
        echo "   ✅ NPM dependencies installed\n";
    } else {
        echo "   ⚠️  NPM install failed or npm not available\n";
    }
}

echo "\n🎉 Quick setup completed!\n\n";
echo "Next steps:\n";
echo "1. Initialize database: php artisan migrate --database=analytics\n";
echo "2. Or run full setup: php setup.php\n";
echo "3. Start development server: composer run dev\n";
echo "4. Visit: http://localhost:8000\n\n";

echo "Manual database setup (if setup.php doesn't work):\n";
echo "1. Create DuckDB database: duckdb database/analytics.duckdb\n";
echo "2. Run the SQL commands from database/migrations/ files\n";
echo "3. Run seeder: php -r \"require 'database/seeders/AnalyticsSeeder.php'; (new Database\\Seeders\\AnalyticsSeeder)->run();\"\n\n";