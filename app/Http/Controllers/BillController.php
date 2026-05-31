<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Bill;
use Carbon\Carbon;

class BillController extends Controller
{
    public function index()
    {
        $bills = Bill::orderBy('due_date', 'asc')->get();
        $now = Carbon::now();

        foreach ($bills as $bill) {
            $bill->is_overdue = $bill->status === 'unpaid' && Carbon::parse($bill->due_date)->isPast() && !Carbon::parse($bill->due_date)->isToday();
            $bill->is_upcoming = $bill->status === 'unpaid' && Carbon::parse($bill->due_date)->between($now, $now->copy()->addDays(7));
        }

        return view('bills', compact('bills'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'due_date' => 'required|date',
        ]);

        Bill::create($request->all());

        return redirect()->route('bills.index')->with('success', 'Tagihan berhasil ditambahkan.');
    }

    public function update(Request $request, Bill $bill)
    {
        if ($request->has('status')) {
            $bill->update(['status' => $request->status]);
            return redirect()->route('bills.index')->with('success', 'Status tagihan diperbarui.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'due_date' => 'required|date',
            'status' => 'required|in:paid,unpaid',
        ]);

        $bill->update($request->all());

        return redirect()->route('bills.index')->with('success', 'Tagihan berhasil diperbarui.');
    }

    public function destroy(Bill $bill)
    {
        $bill->delete();
        return redirect()->route('bills.index')->with('success', 'Tagihan berhasil dihapus.');
    }
}
