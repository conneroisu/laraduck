<?php

/**
 * Laraduck Analytics Dashboard Setup Script
 *
 * This script sets up the DuckDB database, runs migrations, and seeds sample data
 * for the Livewire Analytics Dashboard example.
 */

require_once __DIR__.'/vendor/autoload.php';

use Database\Seeders\AnalyticsSeeder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Support\Facades\DB;

echo "🦆 Laraduck Analytics Dashboard Setup\n";
echo "=====================================\n\n";

try {
    // Bootstrap Laravel application
    $app = new Application(
        $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
    );

    $app->singleton(
        Illuminate\Contracts\Http\Kernel::class,
        App\Http\Kernel::class
    );

    $app->singleton(
        Illuminate\Contracts\Console\Kernel::class,
        App\Console\Kernel::class
    );

    $app->singleton(
        Illuminate\Contracts\Debug\ExceptionHandler::class,
        App\Exceptions\Handler::class
    );

    // Bootstrap the application
    $app->bootstrapWith([
        LoadEnvironmentVariables::class,
        LoadConfiguration::class,
        HandleExceptions::class,
        RegisterFacades::class,
        RegisterProviders::class,
        BootProviders::class,
    ]);

    // Step 1: Verify DuckDB connection
    echo "1. Verifying DuckDB connection...\n";
    try {
        $connection = DB::connection('analytics');
        $connection->getPdo();
        echo "   ✅ DuckDB connection successful\n";
    } catch (Exception $e) {
        echo '   ❌ DuckDB connection failed: '.$e->getMessage()."\n";
        echo "   💡 Make sure DuckDB is installed on your system\n";
        exit(1);
    }

    // Step 2: Run migrations
    echo "\n2. Running DuckDB migrations...\n";
    try {
        // Run migrations using direct SQL execution
        $migrationFiles = glob(__DIR__.'/database/migrations/*.php');
        sort($migrationFiles);

        foreach ($migrationFiles as $file) {
            $migrationName = basename($file, '.php');
            echo "   Running migration: $migrationName\n";

            require_once $file;
            $className = 'return new class extends Illuminate\Database\Migrations\Migration';
            $migration = eval(str_replace('<?php', '', file_get_contents($file)));
            $migration->up();
        }
        echo "   ✅ All migrations completed successfully\n";
    } catch (Exception $e) {
        echo '   ❌ Migration failed: '.$e->getMessage()."\n";
        exit(1);
    }

    // Step 3: Seed sample data
    echo "\n3. Seeding sample analytics data...\n";
    try {
        $seeder = new AnalyticsSeeder;
        $seeder->run();
        echo "   ✅ Sample data seeded successfully\n";
        echo "   📊 Created:\n";
        echo "       - 10 products across multiple categories\n";
        echo "       - 50 customers in different regions\n";
        echo "       - 500 sales transactions over 90 days\n";
    } catch (Exception $e) {
        echo '   ❌ Seeding failed: '.$e->getMessage()."\n";
        exit(1);
    }

    // Step 4: Verify data
    echo "\n4. Verifying data integrity...\n";
    try {
        $productCount = DB::connection('analytics')->table('products')->count();
        $customerCount = DB::connection('analytics')->table('customers')->count();
        $salesCount = DB::connection('analytics')->table('sales')->count();
        $totalRevenue = DB::connection('analytics')->table('sales')->sum('total_amount');

        echo "   📊 Data Summary:\n";
        echo "       Products: $productCount\n";
        echo "       Customers: $customerCount\n";
        echo "       Sales: $salesCount\n";
        echo '       Total Revenue: $'.number_format($totalRevenue, 2)."\n";
        echo "   ✅ Data verification completed\n";
    } catch (Exception $e) {
        echo '   ❌ Data verification failed: '.$e->getMessage()."\n";
        exit(1);
    }

    // Success message
    echo "\n🎉 Setup completed successfully!\n\n";
    echo "Next steps:\n";
    echo "1. Run 'composer run dev' to start the development server\n";
    echo "2. Visit http://localhost:8000 to see the analytics dashboard\n";
    echo "3. Run 'php verify-setup.php' to verify the installation\n\n";
    echo "Features to explore:\n";
    echo "- Real-time analytics with Livewire components\n";
    echo "- Advanced DuckDB queries with window functions\n";
    echo "- Interactive charts and data visualizations\n";
    echo "- Product performance analytics\n";
    echo "- Regional sales breakdowns\n\n";

} catch (Exception $e) {
    echo '❌ Setup failed: '.$e->getMessage()."\n";
    echo "\nDebugging information:\n";
    echo 'File: '.$e->getFile()."\n";
    echo 'Line: '.$e->getLine()."\n";
    echo "Trace:\n".$e->getTraceAsString()."\n";
    exit(1);
}
