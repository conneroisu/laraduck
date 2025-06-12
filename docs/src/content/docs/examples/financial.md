---
title: Financial Reports Example
description: Build financial analytics and reports with LaraDuck
---

This example shows how to build comprehensive financial reports using LaraDuck's analytical capabilities.

## Financial Data Models

### Transaction Model

```php
namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Model;
use LaraDuck\Traits\HasDuckDB;
use LaraDuck\Traits\HasWindowFunctions;

class Transaction extends Model
{
    use HasDuckDB, HasWindowFunctions;
    
    protected $connection = 'duckdb';
    protected $table = 'transactions';
    
    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'metadata' => 'json',
    ];
}
```

### Account Model

```php
class Account extends Model
{
    use HasDuckDB;
    
    protected $connection = 'duckdb';
    
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
    
    public function balance($date = null)
    {
        return $this->transactions()
            ->when($date, fn($q) => $q->where('transaction_date', '<=', $date))
            ->selectRaw('SUM(CASE WHEN type = \'credit\' THEN amount ELSE -amount END) as balance')
            ->value('balance') ?? 0;
    }
}
```

## Financial Reports

### Profit & Loss Statement

```php
class ProfitLossReport
{
    public function generate($startDate, $endDate)
    {
        // Revenue breakdown
        $revenue = Transaction::query()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('type', 'credit')
            ->whereIn('category', ['sales', 'services', 'other_income'])
            ->selectRaw('
                category,
                SUM(amount) as total,
                COUNT(*) as transaction_count,
                AVG(amount) as avg_transaction
            ')
            ->groupBy('category')
            ->get();
        
        // Expense breakdown
        $expenses = Transaction::query()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('type', 'debit')
            ->whereNotIn('category', ['transfer', 'investment'])
            ->selectRaw('
                category,
                subcategory,
                SUM(amount) as total,
                SUM(amount) * 100.0 / SUM(SUM(amount)) OVER () as percentage
            ')
            ->groupBy(['category', 'subcategory'])
            ->orderByDesc('total')
            ->get();
        
        // Monthly trends
        $monthlyTrends = Transaction::query()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw("
                DATE_TRUNC('month', transaction_date) as month,
                SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as revenue,
                SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as expenses,
                SUM(CASE WHEN type = 'credit' THEN amount ELSE -amount END) as net_income
            ")
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        return compact('revenue', 'expenses', 'monthlyTrends');
    }
}
```

### Cash Flow Analysis

```php
class CashFlowAnalysis
{
    public function analyze($accountId, $period = '30 days')
    {
        // Daily cash flow with running balance
        $dailyCashFlow = Transaction::query()
            ->where('account_id', $accountId)
            ->whereRaw("transaction_date >= CURRENT_DATE - INTERVAL ?", [$period])
            ->selectRaw("
                transaction_date,
                SUM(CASE WHEN type = 'credit' THEN amount ELSE -amount END) as daily_flow,
                SUM(SUM(CASE WHEN type = 'credit' THEN amount ELSE -amount END)) 
                    OVER (ORDER BY transaction_date) as running_balance
            ")
            ->groupBy('transaction_date')
            ->orderBy('transaction_date')
            ->get();
        
        // Cash flow categories
        $flowByCategory = Transaction::query()
            ->where('account_id', $accountId)
            ->whereRaw("transaction_date >= CURRENT_DATE - INTERVAL ?", [$period])
            ->selectRaw("
                CASE 
                    WHEN category IN ('sales', 'services') THEN 'Operating Activities'
                    WHEN category IN ('investment_income', 'asset_sale') THEN 'Investing Activities'
                    WHEN category IN ('loan', 'capital') THEN 'Financing Activities'
                    ELSE 'Other Activities'
                END as activity_type,
                SUM(CASE WHEN type = 'credit' THEN amount ELSE -amount END) as net_flow
            ")
            ->groupBy('activity_type')
            ->get();
        
        // Forecast using moving averages
        $forecast = Transaction::query()
            ->where('account_id', $accountId)
            ->selectRaw("
                transaction_date,
                daily_net,
                AVG(daily_net) OVER (
                    ORDER BY transaction_date 
                    ROWS BETWEEN 6 PRECEDING AND CURRENT ROW
                ) as ma7,
                AVG(daily_net) OVER (
                    ORDER BY transaction_date 
                    ROWS BETWEEN 29 PRECEDING AND CURRENT ROW
                ) as ma30
            ")
            ->fromRaw("(
                SELECT 
                    transaction_date,
                    SUM(CASE WHEN type = 'credit' THEN amount ELSE -amount END) as daily_net
                FROM transactions
                WHERE account_id = ?
                GROUP BY transaction_date
            ) as daily_flows", [$accountId])
            ->orderBy('transaction_date')
            ->get();
        
        return compact('dailyCashFlow', 'flowByCategory', 'forecast');
    }
}
```

### Balance Sheet Generation

```php
class BalanceSheet
{
    public function generate($date = null)
    {
        $date = $date ?? now();
        
        // Assets
        $assets = DB::connection('duckdb')->select("
            WITH asset_balances AS (
                SELECT 
                    a.id,
                    a.name,
                    a.type,
                    a.category,
                    SUM(CASE 
                        WHEN t.type = 'credit' THEN t.amount 
                        ELSE -t.amount 
                    END) as balance
                FROM accounts a
                LEFT JOIN transactions t ON a.id = t.account_id
                WHERE a.type = 'asset'
                AND t.transaction_date <= ?
                GROUP BY a.id, a.name, a.type, a.category
            )
            SELECT 
                category,
                SUM(balance) as total,
                LIST(
                    {
                        'account': name, 
                        'balance': balance
                    } ORDER BY balance DESC
                ) as accounts
            FROM asset_balances
            GROUP BY category
            ORDER BY 
                CASE category
                    WHEN 'current' THEN 1
                    WHEN 'fixed' THEN 2
                    WHEN 'other' THEN 3
                END
        ", [$date]);
        
        // Liabilities
        $liabilities = DB::connection('duckdb')->select("
            SELECT 
                category,
                SUM(balance) as total,
                LIST(
                    {
                        'account': name,
                        'balance': balance,
                        'due_date': metadata->>'due_date'
                    } ORDER BY balance DESC
                ) as accounts
            FROM (
                SELECT 
                    a.name,
                    a.category,
                    a.metadata,
                    ABS(SUM(CASE 
                        WHEN t.type = 'credit' THEN -t.amount 
                        ELSE t.amount 
                    END)) as balance
                FROM accounts a
                LEFT JOIN transactions t ON a.id = t.account_id
                WHERE a.type = 'liability'
                AND t.transaction_date <= ?
                GROUP BY a.id, a.name, a.category, a.metadata
            ) liabilities
            GROUP BY category
        ", [$date]);
        
        // Equity calculation
        $totalAssets = collect($assets)->sum('total');
        $totalLiabilities = collect($liabilities)->sum('total');
        $equity = $totalAssets - $totalLiabilities;
        
        return compact('assets', 'liabilities', 'equity', 'totalAssets', 'totalLiabilities');
    }
}
```

### Financial Ratios

```php
class FinancialRatios
{
    public function calculate($startDate, $endDate)
    {
        $data = DB::connection('duckdb')->selectOne("
            WITH period_data AS (
                SELECT
                    -- Revenue & Expenses
                    SUM(CASE WHEN type = 'credit' AND category = 'sales' THEN amount ELSE 0 END) as revenue,
                    SUM(CASE WHEN type = 'debit' AND category = 'cogs' THEN amount ELSE 0 END) as cogs,
                    SUM(CASE WHEN type = 'debit' AND category NOT IN ('cogs', 'tax') THEN amount ELSE 0 END) as operating_expenses,
                    
                    -- Balance Sheet Items (latest values)
                    LAST(total_assets) as total_assets,
                    LAST(current_assets) as current_assets,
                    LAST(current_liabilities) as current_liabilities,
                    LAST(total_liabilities) as total_liabilities,
                    LAST(inventory) as inventory,
                    LAST(receivables) as receivables,
                    
                    -- Cashflow
                    SUM(CASE WHEN type = 'credit' AND category = 'collection' THEN amount ELSE 0 END) as collections,
                    COUNT(DISTINCT DATE_TRUNC('day', transaction_date)) as days_in_period
                FROM transactions t
                CROSS JOIN (
                    SELECT 
                        SUM(CASE WHEN type = 'asset' THEN balance ELSE 0 END) as total_assets,
                        SUM(CASE WHEN type = 'asset' AND category = 'current' THEN balance ELSE 0 END) as current_assets,
                        SUM(CASE WHEN type = 'liability' AND category = 'current' THEN balance ELSE 0 END) as current_liabilities,
                        SUM(CASE WHEN type = 'liability' THEN balance ELSE 0 END) as total_liabilities,
                        SUM(CASE WHEN name = 'inventory' THEN balance ELSE 0 END) as inventory,
                        SUM(CASE WHEN name = 'accounts_receivable' THEN balance ELSE 0 END) as receivables
                    FROM account_balances
                    WHERE balance_date = ?
                ) b
                WHERE t.transaction_date BETWEEN ? AND ?
            )
            SELECT
                -- Profitability Ratios
                ROUND((revenue - cogs) * 100.0 / NULLIF(revenue, 0), 2) as gross_margin,
                ROUND((revenue - cogs - operating_expenses) * 100.0 / NULLIF(revenue, 0), 2) as operating_margin,
                ROUND((revenue - cogs - operating_expenses) * 100.0 / NULLIF(total_assets, 0), 2) as roa,
                
                -- Liquidity Ratios
                ROUND(current_assets / NULLIF(current_liabilities, 0), 2) as current_ratio,
                ROUND((current_assets - inventory) / NULLIF(current_liabilities, 0), 2) as quick_ratio,
                
                -- Efficiency Ratios
                ROUND(cogs * days_in_period / NULLIF(inventory, 0), 2) as inventory_turnover,
                ROUND(revenue * days_in_period / NULLIF(receivables, 0), 2) as receivables_turnover,
                ROUND(receivables * days_in_period / NULLIF(revenue, 0), 0) as days_sales_outstanding,
                
                -- Leverage Ratios
                ROUND(total_liabilities * 100.0 / NULLIF(total_assets, 0), 2) as debt_ratio,
                ROUND(total_liabilities / NULLIF(total_assets - total_liabilities, 0), 2) as debt_to_equity
            FROM period_data
        ", [$endDate, $startDate, $endDate]);
        
        return $data;
    }
}
```

### Budget vs Actual Analysis

```php
class BudgetAnalysis
{
    public function compareToActual($budgetId, $startDate, $endDate)
    {
        return DB::connection('duckdb')->select("
            WITH actual_data AS (
                SELECT 
                    category,
                    subcategory,
                    SUM(amount) as actual_amount,
                    COUNT(*) as transaction_count
                FROM transactions
                WHERE transaction_date BETWEEN ? AND ?
                AND type = 'debit'
                GROUP BY category, subcategory
            ),
            budget_data AS (
                SELECT 
                    category,
                    subcategory,
                    amount as budget_amount
                FROM budget_items
                WHERE budget_id = ?
            )
            SELECT 
                COALESCE(a.category, b.category) as category,
                COALESCE(a.subcategory, b.subcategory) as subcategory,
                COALESCE(b.budget_amount, 0) as budget,
                COALESCE(a.actual_amount, 0) as actual,
                COALESCE(a.actual_amount, 0) - COALESCE(b.budget_amount, 0) as variance,
                ROUND(
                    (COALESCE(a.actual_amount, 0) - COALESCE(b.budget_amount, 0)) * 100.0 / 
                    NULLIF(b.budget_amount, 0), 
                    2
                ) as variance_percentage,
                a.transaction_count,
                CASE 
                    WHEN a.actual_amount > b.budget_amount * 1.1 THEN 'Over Budget'
                    WHEN a.actual_amount < b.budget_amount * 0.9 THEN 'Under Budget'
                    ELSE 'On Track'
                END as status
            FROM actual_data a
            FULL OUTER JOIN budget_data b 
                ON a.category = b.category 
                AND a.subcategory = b.subcategory
            ORDER BY ABS(variance) DESC
        ", [$startDate, $endDate, $budgetId]);
    }
}
```

### Tax Report Generation

```php
class TaxReport
{
    public function generateForm($year)
    {
        // Income categories for tax reporting
        $incomeByCategory = Transaction::query()
            ->whereYear('transaction_date', $year)
            ->where('type', 'credit')
            ->whereIn('category', ['sales', 'services', 'investment_income', 'other_income'])
            ->selectRaw('
                category,
                tax_category,
                SUM(amount) as total,
                COUNT(*) as count,
                LIST(DISTINCT payer ORDER BY payer) as payers
            ')
            ->groupBy(['category', 'tax_category'])
            ->get();
        
        // Deductible expenses
        $deductions = Transaction::query()
            ->whereYear('transaction_date', $year)
            ->where('type', 'debit')
            ->where('tax_deductible', true)
            ->selectRaw('
                tax_category,
                category,
                SUM(amount) as total,
                COUNT(*) as count,
                AVG(amount) as avg_amount
            ')
            ->groupBy(['tax_category', 'category'])
            ->orderBy('tax_category')
            ->get();
        
        // Quarterly estimates
        $quarterlyData = Transaction::query()
            ->whereYear('transaction_date', $year)
            ->selectRaw("
                QUARTER(transaction_date) as quarter,
                SUM(CASE WHEN type = 'credit' AND taxable = true THEN amount ELSE 0 END) as taxable_income,
                SUM(CASE WHEN type = 'debit' AND tax_deductible = true THEN amount ELSE 0 END) as deductions,
                SUM(CASE WHEN category = 'estimated_tax' THEN amount ELSE 0 END) as estimated_payments
            ")
            ->groupBy('quarter')
            ->orderBy('quarter')
            ->get();
        
        return compact('incomeByCategory', 'deductions', 'quarterlyData');
    }
}
```