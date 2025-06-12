---
title: Log Analysis Example
description: Analyze application logs efficiently with LaraDuck
---

This example demonstrates how to analyze application logs using LaraDuck's file querying capabilities and analytical functions.

## Log File Analysis

### Setup the Model

```php
namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;
use LaraDuck\Traits\HasDuckDB;
use LaraDuck\Traits\QueriesFiles;

class LogAnalyzer extends Model
{
    use HasDuckDB, QueriesFiles;
    
    protected $connection = 'duckdb';
    protected $table = 'logs'; // Virtual table for file queries
}
```

### Parsing Access Logs

```php
// Analyze nginx access logs
$topEndpoints = LogAnalyzer::fromFile('/var/log/nginx/access.log')
    ->selectRaw("
        regexp_extract(line, '\"[A-Z]+ ([^ ]+)', 1) as endpoint,
        COUNT(*) as hits,
        AVG(CAST(regexp_extract(line, '\" ([0-9]+) ', 1) AS INTEGER)) as avg_status,
        AVG(CAST(regexp_extract(line, ' ([0-9]+)$', 1) AS DOUBLE)) as avg_response_time
    ")
    ->whereRaw("line LIKE '%GET%' OR line LIKE '%POST%'")
    ->groupBy('endpoint')
    ->orderByDesc('hits')
    ->limit(20)
    ->get();

// Find error patterns
$errors = LogAnalyzer::fromFile('/var/log/app/*.log')
    ->selectRaw("
        regexp_extract(line, '\\[([A-Z]+)\\]', 1) as level,
        regexp_extract(line, '\\[ERROR\\] (.+?):', 1) as error_type,
        COUNT(*) as occurrences,
        MIN(regexp_extract(line, '^([^ ]+)', 1)) as first_seen,
        MAX(regexp_extract(line, '^([^ ]+)', 1)) as last_seen
    ")
    ->whereRaw("line LIKE '%[ERROR]%'")
    ->groupBy(['level', 'error_type'])
    ->having('occurrences', '>', 10)
    ->orderByDesc('occurrences')
    ->get();
```

### Time-based Analysis

```php
// Hourly request distribution
$hourlyStats = LogAnalyzer::fromFile('/logs/access_*.log')
    ->selectRaw("
        DATE_TRUNC('hour', 
            CAST(regexp_extract(line, '^([^ ]+)', 1) AS TIMESTAMP)
        ) as hour,
        COUNT(*) as requests,
        COUNT(DISTINCT regexp_extract(line, '([0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3})', 1)) as unique_ips,
        APPROX_QUANTILE(
            CAST(regexp_extract(line, ' ([0-9]+)$', 1) AS DOUBLE), 
            0.95
        ) as p95_response_time
    ")
    ->whereRaw("regexp_extract(line, '^([^ ]+)', 1) >= CURRENT_DATE - INTERVAL '7 days'")
    ->groupBy('hour')
    ->orderBy('hour')
    ->get();

// Error rate trends
$errorTrends = LogAnalyzer::fromFile('/logs/app.log')
    ->selectRaw("
        DATE_TRUNC('hour', timestamp) as hour,
        COUNT(*) FILTER (WHERE level = 'ERROR') as errors,
        COUNT(*) as total,
        ROUND(100.0 * COUNT(*) FILTER (WHERE level = 'ERROR') / COUNT(*), 2) as error_rate
    ")
    ->groupBy('hour')
    ->having('total', '>', 100)
    ->orderBy('hour')
    ->get();
```

### User Behavior Analysis

```php
// Session analysis
$sessions = LogAnalyzer::fromFile('/logs/app_*.log')
    ->selectRaw("
        user_id,
        session_id,
        MIN(timestamp) as session_start,
        MAX(timestamp) as session_end,
        COUNT(*) as actions,
        COUNT(DISTINCT controller) as unique_pages,
        MAX(timestamp) - MIN(timestamp) as duration
    ")
    ->whereNotNull('session_id')
    ->groupBy(['user_id', 'session_id'])
    ->having('actions', '>', 1)
    ->get();

// User journey funnel
$funnel = LogAnalyzer::fromFile('/logs/events.log')
    ->selectRaw("
        user_id,
        MAX(CASE WHEN event = 'page_view' THEN 1 ELSE 0 END) as viewed,
        MAX(CASE WHEN event = 'add_to_cart' THEN 1 ELSE 0 END) as added_to_cart,
        MAX(CASE WHEN event = 'checkout' THEN 1 ELSE 0 END) as checked_out,
        MAX(CASE WHEN event = 'purchase' THEN 1 ELSE 0 END) as purchased
    ")
    ->whereRaw("timestamp >= CURRENT_DATE - INTERVAL '30 days'")
    ->groupBy('user_id')
    ->get();

// Calculate conversion rates
$conversions = collect($funnel)->pipe(function ($users) {
    $total = $users->count();
    return [
        'viewed' => $users->sum('viewed') / $total * 100,
        'added_to_cart' => $users->sum('added_to_cart') / $total * 100,
        'checked_out' => $users->sum('checked_out') / $total * 100,
        'purchased' => $users->sum('purchased') / $total * 100,
    ];
});
```

### Performance Monitoring

```php
// API endpoint performance
$apiPerformance = LogAnalyzer::fromFile('/logs/api_*.log')
    ->selectRaw("
        endpoint,
        method,
        COUNT(*) as calls,
        AVG(response_time) as avg_time,
        MEDIAN(response_time) as median_time,
        QUANTILE_CONT(response_time, 0.95) as p95_time,
        QUANTILE_CONT(response_time, 0.99) as p99_time,
        MAX(response_time) as max_time,
        COUNT(*) FILTER (WHERE status_code >= 500) as errors
    ")
    ->groupBy(['endpoint', 'method'])
    ->having('calls', '>', 100)
    ->orderByDesc('p95_time')
    ->get();

// Slow query detection
$slowQueries = LogAnalyzer::fromFile('/logs/database.log')
    ->selectRaw("
        regexp_extract(query, '^(SELECT|INSERT|UPDATE|DELETE)', 1) as operation,
        regexp_extract(query, 'FROM ([^ ]+)', 1) as table_name,
        COUNT(*) as occurrences,
        AVG(duration_ms) as avg_duration,
        MAX(duration_ms) as max_duration,
        ARRAY_AGG(DISTINCT query ORDER BY duration_ms DESC LIMIT 5) as example_queries
    ")
    ->where('duration_ms', '>', 1000)
    ->groupBy(['operation', 'table_name'])
    ->orderByDesc('avg_duration')
    ->get();
```

### Anomaly Detection

```php
// Detect unusual traffic patterns
$anomalies = LogAnalyzer::fromFile('/logs/access.log')
    ->selectRaw("
        DATE_TRUNC('minute', timestamp) as minute,
        COUNT(*) as requests,
        AVG(COUNT(*)) OVER (
            ORDER BY DATE_TRUNC('minute', timestamp) 
            ROWS BETWEEN 60 PRECEDING AND CURRENT ROW
        ) as rolling_avg,
        STDDEV(COUNT(*)) OVER (
            ORDER BY DATE_TRUNC('minute', timestamp)
            ROWS BETWEEN 60 PRECEDING AND CURRENT ROW  
        ) as rolling_stddev
    ")
    ->groupBy('minute')
    ->havingRaw('requests > rolling_avg + (3 * rolling_stddev)')
    ->orderByDesc('requests')
    ->get();

// Suspicious user activity
$suspicious = LogAnalyzer::fromFile('/logs/security.log')
    ->selectRaw("
        ip_address,
        COUNT(DISTINCT user_id) as users_from_ip,
        COUNT(*) as total_requests,
        COUNT(DISTINCT endpoint) as unique_endpoints,
        COUNT(*) FILTER (WHERE status_code = 401) as failed_auth,
        COUNT(*) FILTER (WHERE status_code = 403) as forbidden,
        LIST(DISTINCT user_agent) as user_agents
    ")
    ->groupBy('ip_address')
    ->havingRaw('
        users_from_ip > 5 OR 
        failed_auth > 10 OR 
        (total_requests > 1000 AND unique_endpoints < 5)
    ')
    ->orderByDesc('total_requests')
    ->get();
```

### Export Results

```php
// Export analysis results for reporting
LogAnalyzer::fromFile('/logs/*.log')
    ->selectRaw("
        DATE_TRUNC('day', timestamp) as date,
        level,
        COUNT(*) as count,
        COUNT(DISTINCT user_id) as unique_users,
        COUNT(DISTINCT session_id) as sessions
    ")
    ->whereRaw("timestamp >= CURRENT_DATE - INTERVAL '30 days'")
    ->groupBy(['date', 'level'])
    ->orderBy('date')
    ->exportToParquet('/reports/monthly_log_summary.parquet');

// Create materialized view for dashboard
DB::connection('duckdb')->statement("
    CREATE OR REPLACE VIEW log_metrics AS
    SELECT 
        DATE_TRUNC('hour', timestamp) as hour,
        COUNT(*) as total_requests,
        COUNT(*) FILTER (WHERE level = 'ERROR') as errors,
        AVG(response_time) as avg_response_time,
        APPROX_COUNT_DISTINCT(user_id) as unique_users
    FROM read_csv('/logs/current/*.log', 
        columns = {
            'timestamp': 'TIMESTAMP',
            'level': 'VARCHAR',
            'user_id': 'VARCHAR', 
            'response_time': 'DOUBLE'
        }
    )
    GROUP BY hour
");
```