@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="fade-in">
    <div class="content-header">
        <h1>📊 Dashboard Keuangan</h1>
        <form action="{{ route('dashboard') }}" method="GET" class="flex gap-2">
            <select name="range" onchange="this.form.submit()" class="form-control" style="width: auto;">
                <option value="this_month" {{ $range == 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                <option value="all_time" {{ $range == 'all_time' ? 'selected' : '' }}>Semua Waktu</option>
            </select>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
        <div class="card stat-card">
            <span class="stat-label">💰 Total Saldo</span>
            <span class="stat-value" style="color: var(--primary);">Rp {{ number_format($balance, 0, ',', '.') }}</span>
        </div>
        <div class="card stat-card">
            <span class="stat-label">📈 Pendapatan</span>
            <span class="stat-value" style="color: var(--success);">Rp {{ number_format($totalIncome, 0, ',', '.') }}</span>
        </div>
        <div class="card stat-card">
            <span class="stat-label">📉 Pengeluaran</span>
            <span class="stat-value" style="color: var(--danger);">Rp {{ number_format($totalExpense, 0, ',', '.') }}</span>
        </div>
        <div class="card stat-card">
            <span class="stat-label">💵 Savings Rate</span>
            <span class="stat-value" style="color: {{ $savingsRate >= 20 ? 'var(--success)' : ($savingsRate >= 10 ? '#f59e0b' : 'var(--danger)') }};">
                {{ number_format($savingsRate, 1) }}%
            </span>
        </div>
    </div>

    {{-- Charts Row --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
        <div class="card">
            <h3 style="font-size: 1.125rem; margin-bottom: 1rem;">📊 Pengeluaran per Kategori</h3>
            <div style="height: 300px; display: flex; align-items: center; justify-content: center;">
                <canvas id="expensePieChart"></canvas>
            </div>
        </div>
        <div class="card">
            <h3 style="font-size: 1.125rem; margin-bottom: 1rem;">📈 Tren Pendapatan vs Pengeluaran</h3>
            <div style="height: 300px;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Recent Transactions & Bills --}}
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.125rem;">📝 Transaksi Terbaru</h3>
                <a href="{{ route('transactions.index') }}" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;">Lihat Semua</a>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Keterangan</th>
                            <th style="text-align: right;">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $transaction)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($transaction->date)->format('d M Y') }}</td>
                            <td><span class="badge {{ $transaction->type == 'income' ? 'badge-income' : 'badge-expense' }}">{{ $transaction->category->name }}</span></td>
                            <td>{{ $transaction->description ?: '-' }}</td>
                            <td style="text-align: right; font-weight: 600; color: {{ $transaction->type == 'income' ? 'var(--success)' : 'var(--danger)' }}">
                                {{ $transaction->type == 'income' ? '+' : '-' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">Belum ada transaksi</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h3 style="font-size: 1.125rem; margin-bottom: 1.5rem;">💳 Ringkasan Tagihan</h3>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border);">
                    <span style="font-size: 0.875rem; color: var(--text-muted);">Dibayar</span>
                    <span class="badge badge-paid">{{ $billsOverview['paid'] }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border);">
                    <span style="font-size: 0.875rem; color: var(--text-muted);">Belum Dibayar</span>
                    <span class="badge badge-unpaid">{{ $billsOverview['unpaid'] }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border);">
                    <span style="font-size: 0.875rem; color: var(--text-muted);">Mendatang (7 hari)</span>
                    <span class="badge" style="background: #E0F2FE; color: #0369A1;">{{ $billsOverview['upcoming'] }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.875rem; color: var(--text-muted);">Terlambat</span>
                    <span class="badge badge-danger">{{ $billsOverview['overdue'] }}</span>
                </div>
            </div>
            <a href="{{ route('bills.index') }}" class="btn btn-primary" style="margin-top: 1.5rem; width: 100%; justify-content: center;">Kelola Tagihan</a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
// Expense Pie Chart
const expenseData = @json($expensesByCategory);
const expenseLabels = expenseData.map(item => item.category);
const expenseValues = expenseData.map(item => item.total);

const pieCtx = document.getElementById('expensePieChart').getContext('2d');
new Chart(pieCtx, {
    type: 'doughnut',
    data: {
        labels: expenseLabels,
        datasets: [{
            data: expenseValues,
            backgroundColor: [
                'rgba(239, 68, 68, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(34, 197, 94, 0.8)',
                'rgba(59, 130, 246, 0.8)',
                'rgba(168, 85, 247, 0.8)',
                'rgba(236, 72, 153, 0.8)',
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    color: '#94a3b8',
                    font: { size: 11 },
                    padding: 10
                }
            }
        }
    }
});

// Trend Chart
const trendData = @json($trendData);
const trendLabels = trendData.map(item => item.month);
const incomeData = trendData.map(item => item.income);
const expenseData2 = trendData.map(item => item.expense);

const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'bar',
    data: {
        labels: trendLabels,
        datasets: [
            {
                label: 'Pendapatan',
                data: incomeData,
                backgroundColor: 'rgba(34, 197, 94, 0.7)',
                borderRadius: 4
            },
            {
                label: 'Pengeluaran',
                data: expenseData2,
                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                borderRadius: 4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: '#94a3b8',
                    font: { size: 11 }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { color: '#64748b' },
                grid: { color: 'rgba(255,255,255,0.05)' }
            },
            x: {
                ticks: { color: '#64748b' },
                grid: { display: false }
            }
        }
    }
});
</script>
@endsection