<?php

/**
 * Quick verification script for Laraduck Analytics Dashboard
 */

$baseDir = '/Users/connerohnesorge/Documents/001Repos/laraduck/examples/analytics-dashboard-react';

echo "🔍 Quick Verification of Laraduck Analytics Dashboard\n";
echo "=====================================================\n\n";

// Check critical files
$criticalFiles = [
    'routes/web.php',
    'resources/views/app.blade.php', 
    'resources/js/app.jsx',
    'vite.config.js',
    'composer.json',
    'package.json',
    'app/Models/Sale.php',
    'app/Http/Controllers/DashboardController.php',
    'resources/js/Pages/Dashboard/Index.jsx',
];

$allExist = true;
foreach ($criticalFiles as $file) {
    $fullPath = $baseDir . '/' . $file;
    if (file_exists($fullPath)) {
        echo "✅ $file\n";
    } else {
        echo "❌ $file (MISSING)\n";
        $allExist = false;
    }
}

echo "\n";

if ($allExist) {
    echo "🎉 All critical files are present!\n";
    echo "📝 Setup verification: PASSED\n\n";
    
    echo "🚀 Ready to test the setup process:\n";
    echo "1. Navigate to: cd examples/analytics-dashboard-react\n";
    echo "2. Install PHP deps: composer install\n";
    echo "3. Install Node deps: npm install\n";
    echo "4. Copy environment: cp .env.example .env\n";
    echo "5. Generate key: php artisan key:generate\n";
    echo "6. Create database: mkdir -p database && touch database/analytics.duckdb\n";
    echo "7. Run migrations: php artisan migrate\n";
    echo "8. Seed data: php artisan db:seed\n";
    echo "9. Build assets: npm run build\n";
    echo "10. Start server: php artisan serve\n";
    echo "11. Visit: http://localhost:8000\n";
} else {
    echo "❌ Some critical files are missing!\n";
    echo "Please ensure all files were created correctly.\n";
}

?>