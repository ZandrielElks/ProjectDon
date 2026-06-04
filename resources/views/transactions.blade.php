@extends('layouts.app')

@section('title', 'Transaksi')

@section('content')
<div class="fade-in">
    <div class="content-header">
        <h1>Riwayat Transaksi Simulasi</h1>
    </div>

    {{-- Simulation Groups Section --}}
    @if($simulationGroups->count() > 0)
    <div class="card">
        <h3 style="margin-bottom: 1rem; font-size: 1.1rem; color: var(--text-primary);">📊 Simulation Groups</h3>
        @foreach($simulationGroups as $group)
        <div class="simulation-group" style="border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 1rem; margin-bottom: 1rem; background: rgba(99,102,241,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <div>
                    <strong style="color: var(--primary);">{{ $group->simulation_group }}</strong>
                    <span style="color: var(--text-muted); font-size: 0.85rem; margin-left: 1rem;">
                        {{ \Carbon\Carbon::parse($group->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($group->end_date)->format('d M Y') }}
                    </span>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <span style="font-size: 0.85rem; color: var(--text-muted);">{{ $group->transaction_count }} transactions</span>
                    <button onclick="toggleSimulationDetails('{{ $group->simulation_group }}')" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                        <span id="toggle-icon-{{ $group->simulation_group }}">▼</span> Details
                    </button>
                    <button onclick="deleteSimulationGroup('{{ $group->simulation_group }}')" class="btn" style="background: var(--danger); padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                        🗑️ Delete
                    </button>
                </div>
            </div>
            <div style="display: flex; gap: 2rem; font-size: 0.9rem;">
                <span style="color: var(--success);">Income: +Rp {{ number_format($group->total_income, 0, ',', '.') }}</span>
                <span style="color: var(--danger);">Expense: -Rp {{ number_format($group->total_expense, 0, ',', '.') }}</span>
                <span style="color: var(--text-muted);">Net: Rp {{ number_format($group->total_income - $group->total_expense, 0, ',', '.') }}</span>
            </div>
            
            {{-- Hidden details table --}}
            <div id="details-{{ $group->simulation_group }}" style="display: none; margin-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                <table style="width: 100%; font-size: 0.85rem;">
                    <thead>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                            <th style="padding: 0.5rem; text-align: left;">Date</th>
                            <th style="padding: 0.5rem; text-align: left;">Category</th>
                            <th style="padding: 0.5rem; text-align: left;">Description</th>
                            <th style="padding: 0.5rem; text-align: right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $groupTransactions = \App\Models\Transaction::with('category')
                                ->where('simulation_group', $group->simulation_group)
                                ->orderBy('date')
                                ->get();
                        @endphp
                        @foreach($groupTransactions as $txn)
                        <tr>
                            <td style="padding: 0.5rem;">{{ \Carbon\Carbon::parse($txn->date)->format('d M Y') }}</td>
                            <td style="padding: 0.5rem;">
                                @php
                                    $badgeColor = $txn->type == 'income' ? '#4ade80' : '#f87171';
                                    $displayText = $txn->category->name;
                                    
                                    // Check if this is a debt transaction
                                    if ($txn->is_debt) {
                                        // Check if the related bill is paid
                                        if ($txn->bill && $txn->bill->status === 'paid') {
                                            $badgeColor = '#a16207';  // Brown for paid debt
                                            $displayText = $txn->category->name . ' (paid)';
                                        } else {
                                            $badgeColor = '#f87171';  // Red for unpaid debt
                                            $displayText = $txn->category->name . ' (debt)';
                                        }
                                    }
                                @endphp
                                <span class="badge" style="background: {{ $badgeColor }}22; color: {{ $badgeColor }}; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                                    {{ $displayText }}
                                </span>
                            </td>
                            <td style="padding: 0.5rem; color: var(--text-muted);">{{ $txn->description }}</td>
                            <td style="padding: 0.5rem; text-align: right; font-weight: 600; color: {{ $txn->type == 'income' ? 'var(--success)' : 'var(--danger)' }}">
                                {{ $txn->type == 'income' ? '+' : '-' }} Rp {{ number_format($txn->amount, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="card" style="text-align: center; padding: 3rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
        <h3 style="color: var(--text-muted); margin-bottom: 0.5rem;">No Simulation Groups Yet</h3>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Run a simulation and save the results to see them here</p>
        <a href="{{ route('simulator.index') }}" class="btn btn-primary" style="display: inline-flex;">
            Go to Simulator
        </a>
    </div>
    @endif
</div>

<script>
function toggleSimulationDetails(groupId) {
    const details = document.getElementById('details-' + groupId);
    const icon = document.getElementById('toggle-icon-' + groupId);
    
    if (details.style.display === 'none') {
        details.style.display = 'block';
        icon.textContent = '▲';
    } else {
        details.style.display = 'none';
        icon.textContent = '▼';
    }
}

function editTransaction(id, type, categoryId, amount, date, description) {
    document.getElementById('editTransactionForm').action = `/transactions/${id}`;
    document.getElementById('edit_type').value = type;
    document.getElementById('edit_category_id').value = categoryId;
    document.getElementById('edit_amount').value = amount;
    document.getElementById('edit_date').value = date;
    document.getElementById('edit_description').value = description || '';
    document.getElementById('editTransactionModal').style.display = 'flex';
}

async function deleteSimulationGroup(groupId) {
    if (!confirm('Are you sure you want to delete this entire simulation group? This cannot be undone.')) {
        return;
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        const response = await fetch('/transactions/delete-simulation-group', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ simulation_group: groupId })
        });

        const result = await response.json();

        if (result.success) {
            alert(`Deleted ${result.deleted} transactions`);
            window.location.reload();
        } else {
            alert('Failed to delete simulation group: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error deleting simulation group: ' + error.message);
    }
}
</script>
@endsection
