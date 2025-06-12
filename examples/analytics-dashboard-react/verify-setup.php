<?php

/**
 * Setup verification script for Laraduck Analytics Dashboard
 * Run this script to verify that the example is properly configured
 */

echo "🔍 Verifying Laraduck Analytics Dashboard Setup\n";
echo "================================================\n\n";

$errors = [];
$warnings = [];

// Check if we're in the right directory
if (!file_exists('composer.json')) {
    $errors[] = "❌ composer.json not found. Please run this script from the analytics-dashboard-react directory.";
    exit(1);
}

// Check required files
$requiredFiles = [
    'routes/web.php' => 'Web routes configuration',
    'resources/views/app.blade.php' => 'Main Blade template',
    'resources/js/app.jsx' => 'React application entry point',
    'vite.config.js' => 'Vite configuration',
    'tailwind.config.js' => 'Tailwind CSS configuration',
    'package.json' => 'Node.js dependencies',
    'composer.json' => 'PHP dependencies',
    '.env.example' => 'Environment configuration template',
];

echo "📋 Checking required files:\n";
foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        echo "  ✅ $file - $description\n";
    } else {
        $errors[] = "❌ Missing: $file - $description";
    }
}

// Check directories
$requiredDirectories = [
    'app/Models' => 'Eloquent models',
    'app/Http/Controllers' => 'HTTP controllers',
    'resources/js/Pages' => 'React pages',
    'resources/js/Layouts' => 'React layouts',
    'database/migrations' => 'Database migrations',
    'database/seeders' => 'Database seeders',
];

echo "\n📁 Checking required directories:\n";
foreach ($requiredDirectories as $dir => $description) {
    if (is_dir($dir)) {
        echo "  ✅ $dir - $description\n";
    } else {
        $errors[] = "❌ Missing directory: $dir - $description";
    }
}

// Check React components
$reactComponents = [
    'resources/js/Pages/Dashboard/Index.jsx' => 'Main dashboard',
    'resources/js/Pages/Analytics/Sales.jsx' => 'Sales analytics',
    'resources/js/Pages/Analytics/Customers.jsx' => 'Customer analytics',
    'resources/js/Pages/Analytics/Products.jsx' => 'Product analytics',
    'resources/js/Pages/Analytics/Advanced.jsx' => 'Advanced analytics',
    'resources/js/Layouts/AuthenticatedLayout.jsx' => 'Main layout',
];

echo "\n⚛️  Checking React components:\n";
foreach ($reactComponents as $component => $description) {
    if (file_exists($component)) {
        echo "  ✅ $component - $description\n";
    } else {
        $errors[] = "❌ Missing React component: $component - $description";
    }
}

// Check Laravel models
$models = [
    'app/Models/Sale.php' => 'Sales analytical model',
    'app/Models/Customer.php' => 'Customer analytical model',
    'app/Models/Product.php' => 'Product analytical model',
];

echo "\n🏗️  Checking Laravel models:\n";
foreach ($models as $model => $description) {
    if (file_exists($model)) {
        echo "  ✅ $model - $description\n";
    } else {
        $errors[] = "❌ Missing model: $model - $description";
    }
}

// Check controllers
$controllers = [
    'app/Http/Controllers/DashboardController.php' => 'Main dashboard controller',
    'app/Http/Controllers/AnalyticsController.php' => 'Analytics endpoints controller',
];

echo "\n🎛️  Checking controllers:\n";
foreach ($controllers as $controller => $description) {
    if (file_exists($controller)) {
        echo "  ✅ $controller - $description\n";
    } else {
        $errors[] = "❌ Missing controller: $controller - $description";
    }
}

// Check package.json dependencies
if (file_exists('package.json')) {
    $packageJson = json_decode(file_get_contents('package.json'), true);
    $requiredDeps = ['@tremor/react', 'react', '@inertiajs/react'];
    
    echo "\n📦 Checking Node.js dependencies:\n";
    foreach ($requiredDeps as $dep) {
        if (isset($packageJson['dependencies'][$dep]) || isset($packageJson['devDependencies'][$dep])) {
            echo "  ✅ $dep installed\n";
        } else {
            $warnings[] = "⚠️  Missing Node.js dependency: $dep";
        }
    }
}

// Check composer.json dependencies
if (file_exists('composer.json')) {
    $composerJson = json_decode(file_get_contents('composer.json'), true);
    $requiredDeps = ['inertiajs/inertia-laravel'];
    
    echo "\n📦 Checking PHP dependencies:\n";
    foreach ($requiredDeps as $dep) {
        if (isset($composerJson['require'][$dep]) || isset($composerJson['require-dev'][$dep])) {
            echo "  ✅ $dep installed\n";
        } else {
            $warnings[] = "⚠️  Missing PHP dependency: $dep";
        }
    }
}

// Check environment file
echo "\n🔧 Checking environment configuration:\n";
if (file_exists('.env')) {
    echo "  ✅ .env file exists\n";
    $envContent = file_get_contents('.env');
    if (strpos($envContent, 'DUCKDB_PATH') !== false) {
        echo "  ✅ DuckDB configuration found\n";
    } else {
        $warnings[] = "⚠️  DuckDB configuration not found in .env";
    }
} else {
    if (file_exists('.env.example')) {
        $warnings[] = "⚠️  .env file not found. Copy from .env.example and configure.";
    } else {
        $errors[] = "❌ No .env or .env.example file found";
    }
}

// Summary
echo "\n📊 Verification Summary:\n";
echo "========================\n";

if (empty($errors)) {
    echo "🎉 All critical files and components are present!\n";
} else {
    echo "❌ Critical issues found:\n";
    foreach ($errors as $error) {
        echo "  $error\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠️  Warnings:\n";
    foreach ($warnings as $warning) {
        echo "  $warning\n";
    }
}

if (empty($errors)) {
    echo "\n✅ Setup appears to be complete!\n";
    echo "\nNext steps:\n";
    echo "1. Ensure .env is configured with DuckDB settings\n";
    echo "2. Run: composer install\n";
    echo "3. Run: npm install\n";
    echo "4. Run: php artisan key:generate\n";
    echo "5. Run: php artisan migrate\n";
    echo "6. Run: php artisan db:seed\n";
    echo "7. Run: npm run build (or npm run dev)\n";
    echo "8. Run: php artisan serve\n";
    echo "9. Visit: http://localhost:8000\n";
} else {
    echo "\n❌ Please fix the critical issues above before proceeding.\n";
    exit(1);
}

echo "\n🚀 Happy analyzing with Laraduck!\n";
?>