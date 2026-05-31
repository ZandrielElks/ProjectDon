<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Transaction;
use App\Models\Category;

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
            ->selectRaw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_income')
            ->selectRaw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as total_expense')
            ->groupBy('simulation_group')
            ->orderBy('start_date', 'desc')
            ->get();
        
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
            // Find or create category by name
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
            ]);

            $savedCount++;
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
