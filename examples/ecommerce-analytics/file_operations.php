<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/app/Commands/FileOperations.php';

use App\Commands\FileOperations;

output("E-Commerce Analytics - File Operations", 'info');
output("=====================================\n", 'info');

try {
    // Run export examples
    FileOperations::exportExamples();
    
    // Run import examples
    FileOperations::importExamples();
    
    // Run advanced operations
    FileOperations::advancedFileOperations();
    
    output("\n✓ File operations completed successfully!", 'success');
    
    // Show exported files
    output("\nExported files:", 'info');
    $exportDir = __DIR__ . '/exports';
    $files = glob($exportDir . '/*');
    
    foreach ($files as $file) {
        if (is_file($file)) {
            $size = filesize($file);
            $sizeFormatted = $size > 1024*1024 
                ? number_format($size/1024/1024, 2) . ' MB'
                : number_format($size/1024, 2) . ' KB';
            
            output("  - " . basename($file) . " ({$sizeFormatted})", 'info');
        }
    }
    
} catch (\Exception $e) {
    output("Error: " . $e->getMessage(), 'error');
    output("Stack trace: " . $e->getTraceAsString(), 'error');
}