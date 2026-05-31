@extends('layouts.app')

@section('title', 'Tagihan')

@section('content')
<div class="fade-in">
    <div class="content-header">
        <h1>Daftar Tagihan</h1>
        <button class="btn btn-primary" onclick="toggleModal('addModal')">
            Tambah Tagihan
        </button>
    </div>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nama Tagihan</th>
                        <th>Kategori</th>
                        <th>Jumlah</th>
                        <th>Jatuh Tempo</th>
                        <th>Recurring</th>
                        <th>Status</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bills as $bill)
                    <tr>
                        <td style="font-weight: 500;">{{ $bill->name }}</td>
                        <td>
                            @if($bill->category)
                                <span class="badge badge-expense">{{ $bill->category->name }}</span>
                            @else
                                <span style="color: var(--text-muted); font-size: 0.85rem;">-</span>
                            @endif
                        </td>
                        <td style="font-weight: 600;">Rp {{ number_format($bill->amount, 0, ',', '.') }}</td>
                        <td>
                            {{ \Carbon\Carbon::parse($bill->due_date)->format('d M Y') }}
                            @if($bill->is_overdue)
                                <span class="badge badge-danger" style="margin-left: 0.5rem;">Terlambat</span>
                            @elseif($bill->is_upcoming)
                                <span class="badge" style="margin-left: 0.5rem; background: #FEF3C7; color: #92400E;">Segera</span>
                            @endif
                        </td>
                        <td>
                            @if($bill->is_recurring)
                                <span class="badge" style="background: rgba(99,102,241,0.2); color: #6366f1;">
                                    {{ ucfirst($bill->frequency) }}
                                </span>
                            @else
                                <span style="color: var(--text-muted); font-size: 0.85rem;">One-time</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $bill->status == 'paid' ? 'badge-paid' : 'badge-unpaid' }}">
                                {{ $bill->status == 'paid' ? 'Paid' : 'Unpaid' }}
                            </span>
                        </td>
                        <td style="text-align: center; display: flex; gap: 0.5rem; justify-content: center;">
                            <button class="btn btn-outline" style="padding: 0.4rem;" onclick="editBill({{ json_encode($bill) }})">
                                Edit
                            </button>
                            <form action="{{ route('bills.destroy', $bill) }}" method="POST" onsubmit="return confirm('Hapus tagihan ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger" style="padding: 0.4rem;">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">Belum ada tagihan</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2>Tambah Tagihan</h2>
            <button onclick="toggleModal('addModal')" style="background: none; border: none; cursor: pointer; color: var(--text-muted);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <form action="{{ route('bills.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Nama Tagihan</label>
                <input type="text" name="name" required class="form-control" placeholder="Contoh: Listrik, Internet, dll">
            </div>
            <div class="form-group">
                <label>Jumlah (Rp)</label>
                <input type="number" name="amount" required class="form-control">
            </div>
            <div class="form-group">
                <label>Jatuh Tempo</label>
                <input type="date" name="due_date" required class="form-control">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem;">Simpan Tagihan</button>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2>Edit Tagihan</h2>
            <button onclick="toggleModal('editModal')" style="background: none; border: none; cursor: pointer; color: var(--text-muted);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <form id="editForm" method="POST">
            @csrf @method('PUT')
            <div class="form-group">
                <label>Nama Tagihan</label>
                <input type="text" name="name" id="edit_name" required class="form-control">
            </div>
            <div class="form-group">
                <label>Jumlah (Rp)</label>
                <input type="number" name="amount" id="edit_amount" required class="form-control">
            </div>
            <div class="form-group">
                <label>Jatuh Tempo</label>
                <input type="date" name="due_date" id="edit_due_date" required class="form-control">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="edit_status" required class="form-control">
                    <option value="unpaid">Belum Dibayar</option>
                    <option value="paid">Sudah Dibayar</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem;">Update Tagihan</button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function toggleModal(id) {
        document.getElementById(id).classList.toggle('active');
    }

    function editBill(bill) {
        document.getElementById('editForm').action = `/bills/${bill.id}`;
        document.getElementById('edit_name').value = bill.name;
        document.getElementById('edit_amount').value = Math.round(bill.amount);
        document.getElementById('edit_due_date').value = bill.due_date;
        document.getElementById('edit_status').value = bill.status;
        toggleModal('editModal');
    }
</script>
@endsection
