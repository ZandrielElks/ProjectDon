@extends('layouts.app')

@section('title', 'Kategori')

@section('content')
    <div class="fade-in">
        <div class="content-header">
            <h1>Manajemen Kategori</h1>
            <button class="btn btn-primary" onclick="toggleModal('addModal')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Tambah Kategori
            </button>
        </div>

        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Kategori</th>
                            <th>Jenis</th>
                            <th style="text-align: right;">Jumlah (Rp)</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td style="font-weight: 500;">{{ $category->name }}</td>
                                <td>
                                    <span class="badge {{ $category->type == 'income' ? 'badge-income' : 'badge-expense' }}">
                                        {{ $category->type == 'income' ? 'Pendapatan' : 'Pengeluaran' }}
                                    </span>
                                </td>
                                <td
                                    style="text-align: right; font-weight: 600; color: {{ $category->type == 'income' ? 'var(--success)' : 'var(--danger)' }}">
                                    Rp {{ number_format($category->amount ?? 0, 0, ',', '.') }}
                                </td>
                                <td style="text-align: center; display: flex; gap: 0.5rem; justify-content: center;">
                                    <button class="btn btn-outline" style="padding: 0.4rem;"
                                        onclick="editCategory({{ json_encode($category) }})">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>
                                    <form action="{{ route('categories.destroy', $category) }}" method="POST"
                                        onsubmit="return confirm('Hapus kategori ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger" style="padding: 0.4rem;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                                </path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-muted);">Belum ada
                                    kategori</td>
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
                <h2>Tambah Kategori</h2>
                <button onclick="toggleModal('addModal')"
                    style="background: none; border: none; cursor: pointer; color: var(--text-muted);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <form action="{{ route('categories.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="name" required class="form-control" placeholder="Contoh: Makanan, Gaji, dll">
                </div>
                <div class="form-group">
                    <label>Jenis</label>
                    <select name="type" required class="form-control">
                        <option value="expense">Pengeluaran</option>
                        <option value="income">Pendapatan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jumlah (Rp)</label>
                    <input type="number" name="amount" required class="form-control" placeholder="Contoh: 1000000" min="0">
                </div>
                <button type="submit" class="btn btn-primary"
                    style="width: 100%; justify-content: center; margin-top: 1rem;">Simpan Kategori</button>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2>Edit Kategori</h2>
                <button onclick="toggleModal('editModal')"
                    style="background: none; border: none; cursor: pointer; color: var(--text-muted);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="name" id="edit_name" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Jenis</label>
                    <select name="type" id="edit_type" required class="form-control">
                        <option value="expense">Pengeluaran</option>
                        <option value="income">Pendapatan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jumlah (Rp)</label>
                    <input type="number" name="amount" id="edit_amount" required class="form-control" min="0">
                </div>
                <button type="submit" class="btn btn-primary"
                    style="width: 100%; justify-content: center; margin-top: 1rem;">Update Kategori</button>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function toggleModal(id) {
            document.getElementById(id).classList.toggle('active');
        }

        function editCategory(category) {
            document.getElementById('editForm').action = `/categories/${category.id}`;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_type').value = category.type;
            document.getElementById('edit_amount').value = Math.round(category.amount);
            toggleModal('editModal');
        }
    </script>
@endsection