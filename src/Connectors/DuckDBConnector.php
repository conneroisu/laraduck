<?php

namespace Laraduck\EloquentDuckDB\Connectors;

use Illuminate\Database\Connectors\Connector;
use Illuminate\Database\Connectors\ConnectorInterface;
use Laraduck\EloquentDuckDB\Database\DuckDBPDOWrapper;
use Laraduck\EloquentDuckDB\DuckDB\DuckDBCLI as DuckDB;
use RuntimeException;

/**
 * DuckDB Connector for Laravel database connections.
 * 
 * Handles connection establishment with comprehensive configuration support
 * including performance settings, cloud storage, and file format options.
 */
class DuckDBConnector extends Connector implements ConnectorInterface
{
    /**
     * Establish a database connection.
     */
    public function connect(array $config): DuckDBPDOWrapper
    {
        $database = $this->resolveDatabasePath($config);
        
        try {
            $duckdb = DuckDB::create($database);
            
            // Apply connection settings in the correct order
            $this->configureAccessMode($duckdb, $config);
            $this->configureMemorySettings($duckdb, $config);
            $this->configurePerformanceSettings($duckdb, $config);
            $this->installExtensions($duckdb, $config);
            $this->configureCloudStorage($duckdb, $config);
            $this->configureFileFormats($duckdb, $config);
            $this->applyCustomSettings($duckdb, $config);
            
            return new DuckDBPDOWrapper($duckdb);
            
        } catch (\Exception $e) {
            throw new RuntimeException(
                "Failed to connect to DuckDB database '{$database}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }
    
    /**
     * Resolve the database path from configuration.
     */
    protected function resolveDatabasePath(array $config): string
    {
        $database = $config['database'] ?? ':memory:';
        
        // Handle relative paths by making them absolute from Laravel's base path
        if ($database !== ':memory:' && !str_starts_with($database, '/')) {
            $database = base_path($database);
            
            // Ensure the directory exists
            $directory = dirname($database);
            if (!is_dir($directory)) {
                if (!mkdir($directory, 0755, true)) {
                    throw new RuntimeException("Cannot create database directory: {$directory}");
                }
            }
        }
        
        return $database;
    }
    
    /**
     * Configure access mode settings.
     */
    protected function configureAccessMode(DuckDB $duckdb, array $config): void
    {
        if (isset($config['read_only']) && $config['read_only']) {
            $this->executeSetting($duckdb, 'read_only', 'true');
        }
        
        if (isset($config['access_mode'])) {
            $this->executeSetting($duckdb, 'access_mode', $config['access_mode']);
        }
    }
    
    /**
     * Configure memory and threading settings.
     */
    protected function configureMemorySettings(DuckDB $duckdb, array $config): void
    {
        if (isset($config['threads'])) {
            $this->executeSetting($duckdb, 'threads', $config['threads']);
        }
        
        if (isset($config['memory_limit'])) {
            $this->executeSetting($duckdb, 'memory_limit', $config['memory_limit']);
        }
        
        if (isset($config['max_memory'])) {
            $this->executeSetting($duckdb, 'max_memory', $config['max_memory']);
        }
        
        if (isset($config['temp_directory'])) {
            $this->executeSetting($duckdb, 'temp_directory', $config['temp_directory']);
        }
    }
    
    /**
     * Configure performance optimization settings.
     */
    protected function configurePerformanceSettings(DuckDB $duckdb, array $config): void
    {
        $performance = $config['performance'] ?? [];
        
        foreach ($performance as $setting => $value) {
            if ($value !== null) {
                $this->executeSetting($duckdb, $setting, $this->formatSettingValue($value));
            }
        }
    }
    
    /**
     * Install and load DuckDB extensions.
     */
    protected function installExtensions(DuckDB $duckdb, array $config): void
    {
        $extensions = $config['extensions'] ?? [];
        
        foreach ($extensions as $extension) {
            try {
                // Install extension if not already installed
                $duckdb->query("INSTALL {$extension}");
                
                // Load the extension
                $duckdb->query("LOAD {$extension}");
                
            } catch (\Exception $e) {
                // Log warning but don't fail connection for optional extensions
                error_log("Warning: Failed to load DuckDB extension '{$extension}': " . $e->getMessage());
            }
        }
    }
    
    /**
     * Configure cloud storage settings.
     */
    protected function configureCloudStorage(DuckDB $duckdb, array $config): void
    {
        $cloudConfig = $config['cloud_storage'] ?? [];
        
        // AWS S3 Configuration
        $this->configureS3Settings($duckdb, $cloudConfig);
        
        // Azure Blob Storage Configuration
        $this->configureAzureSettings($duckdb, $cloudConfig);
        
        // Google Cloud Storage Configuration
        $this->configureGcsSettings($duckdb, $cloudConfig);
        
        // Generic HTTP settings
        $this->configureHttpSettings($duckdb, $cloudConfig);
    }
    
    /**
     * Configure AWS S3 settings.
     */
    protected function configureS3Settings(DuckDB $duckdb, array $config): void
    {
        $s3Settings = [
            's3_region',
            's3_access_key_id',
            's3_secret_access_key',
            's3_endpoint',
            's3_use_ssl',
            's3_url_style',
        ];
        
        foreach ($s3Settings as $setting) {
            if (isset($config[$setting]) && $config[$setting] !== null) {
                $this->executeSetting($duckdb, $setting, $this->formatSettingValue($config[$setting]));
            }
        }
    }
    
    /**
     * Configure Azure Blob Storage settings.
     */
    protected function configureAzureSettings(DuckDB $duckdb, array $config): void
    {
        $azureSettings = [
            'azure_account_name',
            'azure_account_key',
            'azure_connection_string',
        ];
        
        foreach ($azureSettings as $setting) {
            if (isset($config[$setting]) && $config[$setting] !== null) {
                $this->executeSetting($duckdb, $setting, $this->formatSettingValue($config[$setting]));
            }
        }
    }
    
    /**
     * Configure Google Cloud Storage settings.
     */
    protected function configureGcsSettings(DuckDB $duckdb, array $config): void
    {
        $gcsSettings = [
            'gcs_access_key_id',
            'gcs_secret',
            'gcs_project_id',
        ];
        
        foreach ($gcsSettings as $setting) {
            if (isset($config[$setting]) && $config[$setting] !== null) {
                $this->executeSetting($duckdb, $setting, $this->formatSettingValue($config[$setting]));
            }
        }
    }
    
    /**
     * Configure HTTP settings.
     */
    protected function configureHttpSettings(DuckDB $duckdb, array $config): void
    {
        $httpSettings = [
            'http_timeout',
            'http_retries',
            'http_retry_wait_ms',
        ];
        
        foreach ($httpSettings as $setting) {
            if (isset($config[$setting]) && $config[$setting] !== null) {
                $this->executeSetting($duckdb, $setting, $config[$setting]);
            }
        }
    }
    
    /**
     * Configure file format settings.
     */
    protected function configureFileFormats(DuckDB $duckdb, array $config): void
    {
        $fileFormats = $config['file_formats'] ?? [];
        
        // Configure CSV settings
        if (isset($fileFormats['csv'])) {
            foreach ($fileFormats['csv'] as $setting => $value) {
                if ($value !== null) {
                    $this->executeSetting($duckdb, $setting, $this->formatSettingValue($value));
                }
            }
        }
        
        // Configure Parquet settings
        if (isset($fileFormats['parquet'])) {
            foreach ($fileFormats['parquet'] as $setting => $value) {
                if ($value !== null) {
                    $this->executeSetting($duckdb, $setting, $this->formatSettingValue($value));
                }
            }
        }
        
        // Configure JSON settings
        if (isset($fileFormats['json'])) {
            foreach ($fileFormats['json'] as $setting => $value) {
                if ($value !== null) {
                    $this->executeSetting($duckdb, $setting, $this->formatSettingValue($value));
                }
            }
        }
    }
    
    /**
     * Apply custom settings from legacy configuration.
     */
    protected function applyCustomSettings(DuckDB $duckdb, array $config): void
    {
        // Support legacy 'settings' configuration for backward compatibility
        $settings = $config['settings'] ?? [];
        
        foreach ($settings as $key => $value) {
            if ($value !== null) {
                $this->executeSetting($duckdb, $key, $this->formatSettingValue($value));
            }
        }
    }
    
    /**
     * Execute a DuckDB setting command with error handling.
     */
    protected function executeSetting(DuckDB $duckdb, string $setting, mixed $value): void
    {
        try {
            $duckdb->query("SET {$setting} = {$value}");
        } catch (\Exception $e) {
            // Silently ignore settings that don't exist - normal for different DuckDB versions
        }
    }
    
    /**
     * Format a setting value for SQL execution.
     */
    protected function formatSettingValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        if (is_string($value)) {
            return "'{$value}'";
        }
        
        if (is_array($value)) {
            return "'" . json_encode($value) . "'";
        }
        
        return "'{$value}'";
    }
    
    /**
     * Validate the DuckDB connection and configuration.
     */
    protected function validateConnection(DuckDB $duckdb): void
    {
        try {
            // Test the connection with a simple query
            $duckdb->query('SELECT 1 as test');
        } catch (\Exception $e) {
            throw new RuntimeException(
                "DuckDB connection validation failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
    
    /**
     * Get connection information for debugging.
     */
    public function getConnectionInfo(array $config): array
    {
        return [
            'database' => $config['database'] ?? ':memory:',
            'read_only' => $config['read_only'] ?? false,
            'memory_limit' => $config['memory_limit'] ?? null,
            'extensions' => $config['extensions'] ?? [],
            'access_mode' => $config['access_mode'] ?? 'automatic',
        ];
    }
}