<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Transaction;
use App\Models\Category;
use App\Models\Bill;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $transactions = $query->latest('date')->get();
        
        // Group simulation transactions
        $simulationGroups = Transaction::whereNotNull('simulation_group')
            ->select('simulation_group')
            ->selectRaw('MIN(date) as start_date')
            ->selectRaw('MAX(date) as end_date')
            ->selectRaw('COUNT(*) as transaction_count')
            ->selectRaw("SUM(CASE WHEN type = 'income' AND is_surplus = 0 THEN amount ELSE 0 END) as total_income")
            ->selectRaw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as total_expense')
            ->groupBy('simulation_group')
            ->orderBy('start_date', 'desc')
            ->get();

        // Calculate final surplus for each group (only if last transaction is surplus)
        foreach ($simulationGroups as $group) {
            $lastTransaction = Transaction::where('simulation_group', $group->simulation_group)
                ->orderBy('date', 'desc')
                ->orderBy('id', 'desc')  // In case of same date, use latest by ID
                ->first();
            
            // Only count surplus if the very last transaction is a surplus
            $group->final_surplus = ($lastTransaction && $lastTransaction->is_surplus) ? $lastTransaction->amount : 0;
        }
        
        $categories = Category::all();

        return view('transactions', compact('transactions', 'categories', 'simulationGroups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'type' => 'required|in:income,expense',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'date' => 'required|date',
        ]);

        Transaction::create($request->all());

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil ditambahkan.');
    }

    public function update(Request $request, Transaction $transaction)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'type' => 'required|in:income,expense',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'date' => 'required|date',
        ]);

        $transaction->update($request->all());

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil dihapus.');
    }

    public function saveSimulation(Request $request)
    {
        $request->validate([
            'transactions' => 'required|array',
            'transactions.*.amount' => 'required|numeric',
            'transactions.*.type' => 'required|in:income,expense',
            'transactions.*.category_name' => 'required|string',
            'transactions.*.description' => 'nullable|string',
            'transactions.*.date' => 'required|date',
        ]);

        $savedCount = 0;
        $errors = [];
        
        // Generate unique group ID for this simulation
        $simulationGroup = 'SIM-' . now()->format('YmdHis') . '-' . uniqid();

        foreach ($request->transactions as $txnData) {
            // Check if this is a surplus entry (informational, not counted in totals)
            $isSurplus = isset($txnData['category_name']) && strpos($txnData['category_name'], '(surplus)') !== false;
            
            // Handle surplus entries - save without category
            if ($isSurplus) {
                Transaction::create([
                    'amount' => $txnData['amount'],
                    'type' => 'income',
                    'category_id' => null,  // No category for surplus
                    'description' => $txnData['description'] ?? 'Surplus from simulation',
                    'date' => $txnData['date'],
                    'simulation_group' => $simulationGroup,
                    'is_debt' => false,
                    'is_surplus' => true,
                ]);
                
                $savedCount++;
                continue;
            }
            
            // Check if this is a debt transaction
            if (isset($txnData['is_debt']) && $txnData['is_debt']) {
                // Find or create category by name (use original category, not Tagihan)
                $categoryName = $txnData['category_name'];
                $category = Category::where('name', $categoryName)->first();

                if (!$category) {
                    // Auto-create category if it doesn't exist
                    $category = Category::create([
                        'name' => $categoryName,
                        'type' => 'expense',
                        'amount' => $txnData['amount'],
                    ]);
                }

                // Create a Bill for this debt
                $bill = Bill::create([
                    'name' => $categoryName . ' debt',
                    'amount' => $txnData['amount'],
                    'due_date' => $txnData['date'],
                    'status' => 'unpaid',
                    'category_id' => $category->id,
                ]);

                // Create transaction linked to the bill
                Transaction::create([
                    'amount' => $txnData['amount'],
                    'type' => $txnData['type'],
                    'category_id' => $category->id,
                    'description' => $txnData['description'] ?? null,
                    'date' => $txnData['date'],
                    'simulation_group' => $simulationGroup,
                    'is_debt' => true,
                    'bill_id' => $bill->id,
                ]);

                $savedCount++;
            } else {
                // Regular transaction - find or create category by name
                $category = Category::where('name', $txnData['category_name'])->first();

                if (!$category) {
                    // Auto-create category if it doesn't exist
                    $category = Category::create([
                        'name' => $txnData['category_name'],
                        'type' => $txnData['type'],
                        'amount' => $txnData['amount'],
                    ]);
                }

                // Create transaction
                Transaction::create([
                    'amount' => $txnData['amount'],
                    'type' => $txnData['type'],
                    'category_id' => $category->id,
                    'description' => $txnData['description'] ?? null,
                    'date' => $txnData['date'],
                    'simulation_group' => $simulationGroup,
                    'is_debt' => false,
                ]);

                $savedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'count' => $savedCount,
            'simulation_group' => $simulationGroup,
            'message' => "Successfully saved {$savedCount} transactions",
            'errors' => $errors,
        ]);
    }

    public function deleteSimulationGroup(Request $request)
    {
        $request->validate([
            'simulation_group' => 'required|string',
        ]);

        $deleted = Transaction::where('simulation_group', $request->simulation_group)->delete();

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
            'message' => "Deleted {$deleted} transactions",
        ]);
    }
}
