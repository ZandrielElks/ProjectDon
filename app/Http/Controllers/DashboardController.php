<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Transaction;
use App\Models\Category;
use App\Models\Bill;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $range = $request->get('range', 'this_month');
        $startDate = null;
        $endDate = Carbon::now();

        switch ($range) {
            case 'this_month':
                $startDate = Carbon::now()->startOfMonth();
                break;
            case 'all_time':
                $startDate = null;  // No start date = all time
                $endDate = Carbon::now()->addYear();  // Future date to catch everything
                break;
            case 'custom':
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $startDate = Carbon::parse($request->start_date);
                    $endDate = Carbon::parse($request->end_date);
                }
                break;
        }

        $query = Transaction::query();
        if ($startDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        } else if ($range === 'all_time') {
            // For all_time, get all transactions
            // No date filter
        }

        $totalIncome = (clone $query)->where('type', 'income')->sum('amount');
        $totalExpense = (clone $query)->where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;
        $savingsRate = $totalIncome > 0 ? (($totalIncome - $totalExpense) / $totalIncome) * 100 : 0;

        $recentTransactions = Transaction::with('category')->latest('date')->limit(10)->get();

        // Expenses by category for pie chart
        $expensesByCategory = Transaction::with('category')
            ->where('type', 'expense')
            ->whereBetween('date', [$startDate ?? Carbon::now()->subMonths(6), $endDate])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category->name,
                    'total' => $item->total,
                ];
            });

        // Income vs Expense trend
        $trendData = [];
        
        if ($range === 'all_time') {
            // For all_time, get trend from oldest transaction to newest
            $oldestDate = Transaction::min('date');
            $newestDate = Transaction::max('date');
            
            if ($oldestDate && $newestDate) {
                $current = Carbon::parse($oldestDate)->startOfMonth();
                $end = Carbon::parse($newestDate)->endOfMonth();
                
                while ($current <= $end) {
                    $monthStart = $current->copy()->startOfMonth();
                    $monthEnd = $current->copy()->endOfMonth();
                    
                    $income = Transaction::where('type', 'income')
                        ->whereBetween('date', [$monthStart, $monthEnd])
                        ->sum('amount');
                    
                    $expense = Transaction::where('type', 'expense')
                        ->whereBetween('date', [$monthStart, $monthEnd])
                        ->sum('amount');
                    
                    $trendData[] = [
                        'month' => $monthStart->format('M Y'),
                        'income' => $income,
                        'expense' => $expense,
                    ];
                    
                    $current->addMonth();
                }
            }
        } else {
            // For other ranges, show last 6 months
            for ($i = 5; $i >= 0; $i--) {
                $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
                $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
                
                $income = Transaction::where('type', 'income')
                    ->whereBetween('date', [$monthStart, $monthEnd])
                    ->sum('amount');
                
                $expense = Transaction::where('type', 'expense')
                    ->whereBetween('date', [$monthStart, $monthEnd])
                    ->sum('amount');
                
                $trendData[] = [
                    'month' => $monthStart->format('M Y'),
                    'income' => $income,
                    'expense' => $expense,
                ];
            }
        }

        $bills = Bill::all();
        $billsOverview = [
            'paid' => $bills->where('status', 'paid')->count(),
            'unpaid' => $bills->where('status', 'unpaid')->count(),
            'upcoming' => $bills->where('status', 'unpaid')->filter(function ($bill) {
                return Carbon::parse($bill->due_date)->between(Carbon::now(), Carbon::now()->addDays(7));
            })->count(),
            'overdue' => $bills->where('status', 'unpaid')->filter(function ($bill) {
                return Carbon::parse($bill->due_date)->isPast() && !Carbon::parse($bill->due_date)->isToday();
            })->count(),
        ];

        return view('dashboard', compact(
            'balance', 
            'totalIncome', 
            'totalExpense', 
            'savingsRate',
            'recentTransactions', 
            'expensesByCategory',
            'trendData',
            'billsOverview', 
            'range'
        ));
    }
}
