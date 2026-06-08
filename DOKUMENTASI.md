# 📚 Dokumentasi BagStack - Sistem Simulasi Keuangan

## 📖 Daftar Isi
1. [Gambaran Umum](#gambaran-umum)
2. [Arsitektur Sistem](#arsitektur-sistem)
3. [Komponen Utama](#komponen-utama)
4. [Alur Kerja (Workflow)](#alur-kerja-workflow)
5. [Mesin Simulasi](#mesin-simulasi)
6. [Logika Bisnis](#logika-bisnis)
7. [API Reference](#api-reference)
8. [Database Schema](#database-schema)
9. [Frontend & UI](#frontend--ui)

---

## 🎯 Gambaran Umum

**BagStack** adalah aplikasi web simulasi keuangan yang memungkinkan pengguna untuk:
- Memvisualisasikan aliran keuangan menggunakan node graph interaktif
- Mensimulasikan skenario keuangan multi-periode (mingguan/bulanan/tahunan)
- Melacak pendapatan, pengeluaran, hutang, dan surplus
- Menganalisis kesehatan keuangan dengan grafik dan laporan

### Fitur Utama
✅ **Visual Workflow Builder** - Drag & drop nodes untuk membuat aliran keuangan  
✅ **Smart Money Allocation** - Distribusi otomatis berdasarkan aturan split/prioritas  
✅ **Multi-Period Simulation** - Simulasi 1-120 periode dengan unit waktu fleksibel  
✅ **Debt & Surplus Tracking** - Pelacakan hutang dan surplus otomatis  
✅ **Negative Balance Support** - Grafik mendukung saldo negatif  
✅ **Transaction History** - Riwayat transaksi simulasi tersimpan  

---

## 🏗️ Arsitektur Sistem

### Tech Stack
- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: Blade Templates + Vanilla JavaScript
- **Database**: SQLite (development) / MySQL (production ready)
- **Charting**: Chart.js
- **Graph Layout**: Dagre.js

### Struktur Direktori
```
BagStack/
├── app/
│   ├── Http/Controllers/
│   │   ├── TransactionController.php    # Manajemen transaksi
│   │   ├── CategoryController.php       # Manajemen kategori
│   │   ├── BillController.php           # Manajemen tagihan
│   │   ├── WorkflowController.php       # Manajemen workflow
│   │   └── SimulatorController.php      # Endpoint simulasi
│   ├── Models/
│   │   ├── Transaction.php              # Model transaksi
│   │   ├── Category.php                 # Model kategori
│   │   ├── Bill.php                     # Model tagihan
│   │   ├── Workflow.php                 # Model workflow
│   │   ├── FlowObject.php               # Model node workflow
│   │   └── ObjectConnection.php         # Model edge/koneksi
│   └── Services/
│       └── SimulationEngine.php         # ⭐ Core simulation logic
├── database/
│   └── migrations/                      # Database migrations
├── resources/
│   └── views/
│       ├── dashboard.blade.php          # Halaman dashboard
│       ├── simulator.blade.php          # Halaman simulator
│       └── transactions.blade.php       # Riwayat transaksi
└── public/
    └── css/
        └── style.css                    # Custom styling
```

---

## 🧩 Komponen Utama

### 1. Models (Data Layer)

#### Transaction Model
```php
// Properti
- id: integer
- amount: decimal(15,2)
- type: enum('income', 'expense')
- category_id: integer (nullable)
- description: text
- date: date
- simulation_group: string (nullable)
- is_debt: boolean (default: false)
- is_surplus: boolean (default: false)
- bill_id: integer (nullable)

// Relasi
- belongsTo(Category)
- belongsTo(Bill)
```

**Penjelasan Field Khusus:**
- `simulation_group`: Grup transaksi dari simulasi yang sama (format: SIM-{timestamp}-{hash})
- `is_debt`: Menandai transaksi sebagai hutang (tidak memiliki alokasi cukup)
- `is_surplus`: Menandai transaksi sebagai surplus (sisa alokasi yang tidak terpakai)

#### Workflow Model
```php
// Properti
- id: integer
- project_id: integer
- name: string
- viewport_json: json (posisi & zoom canvas)

// Relasi
- hasMany(FlowObject) - node-node dalam workflow
- hasMany(ObjectConnection) - koneksi antar node
- belongsTo(Project)
```

#### FlowObject Model (Node)
```php
// Properti
- id: integer
- workflow_id: integer
- type: enum('income', 'outcome', 'rule')
- name: string
- data_json: json
- position_x: integer
- position_y: integer

// data_json structure berdasarkan type:
// income: { amount, frequency, tax_rate, start_delay }
// outcome: { amount, frequency }
// rule: { rule_type: 'split' | 'priority' }
```

**Tipe Node:**
1. **Income Node** 🟢 - Menghasilkan uang masuk
2. **Outcome Node** 🔴 - Pengeluaran/expense
3. **Rule Node** 🟣 - Mengatur distribusi uang

#### ObjectConnection Model (Edge)
```php
// Properti
- id: integer
- workflow_id: integer
- source_object_id: integer
- target_object_id: integer
- edge_data: json

// edge_data structure:
// { percentage: 0-100 } - untuk split rule
```

---

## 🔄 Alur Kerja (Workflow)

### Cara Kerja Visual Builder

1. **Pengguna membuat node** di canvas
   - Klik "Tambah Node" → pilih tipe (Income/Outcome/Rule)
   - Node muncul di canvas dengan form input

2. **Pengguna menghubungkan node**
   - Drag dari port output (dot kanan) ke port input (dot kiri)
   - Koneksi tersimpan sebagai edge

3. **Konfigurasi node**
   - Income: jumlah, frekuensi, pajak
   - Outcome: jumlah, frekuensi
   - Rule: tipe (split/priority), persentase alokasi

4. **Simpan workflow**
   - POST ke `/workflows/{id}` dengan data nodes & edges
   - Tersimpan di database

5. **Jalankan simulasi**
   - Klik "Jalankan Simulasi"
   - Input: jumlah periode, satuan waktu
   - POST ke `/workflows/{id}/simulate`
   - Hasil ditampilkan di panel

---

## ⚙️ Mesin Simulasi

### SimulationEngine.php - Core Logic

#### Alur Eksekusi

```
1. Load workflow (nodes + edges)
2. Build adjacency list (graph representation)
3. For each period (1 to N):
   a. Fire income nodes → tambah balance
   b. Fire rule nodes:
      - Split rule: distribusi berdasarkan %
      - Priority rule: prioritas berdasarkan jumlah expense
   c. Fire outcome nodes → kurangi balance
   d. Track surplus dan debt
   e. Simpan timeline & logs
4. Return hasil simulasi
```

#### Konsep Penting

**1. Fire Count**
Menentukan berapa kali node aktif dalam satu periode:
```php
// Contoh: Weekly income dalam Monthly simulation
frequency = 'weekly', timeUnit = 'month' → fireCount = 4
// Node income aktif 4x dalam 1 bulan
```

**2. Balance Calculation**
```
Balance = Balance_sebelumnya + Income - Actual_Payment

Actual_Payment = min(Allocated_Amount, Full_Expense_Amount)
```

**Contoh:**
```
Period 1:
- Income: 800k
- Allocated to Makanan: 400k
- Makanan expense: 200k
- Actual payment: min(400k, 200k) = 200k
- Balance: 800k - 200k = 600k
- Surplus: 400k - 200k = 200k (informational)
```

**3. Debt Tracking**
```
Debt = Full_Expense_Amount - Allocated_Amount

Ketika Allocated < Required:
- Bayar: Allocated_Amount
- Debt: Required - Allocated (dicatat tapi tidak kurangi balance lagi)
```

**4. Surplus Tracking**
```
Surplus = Allocated_Amount - Full_Expense_Amount

Ketika Allocated > Required:
- Bayar: Full_Expense_Amount
- Surplus: sisa yang tidak terpakai (carry forward ke periode berikutnya)
```

### Split Rule Logic

```php
// Distribusi berdasarkan persentase
foreach (targets as target) {
    allocated = totalAmount * (percentage / 100);
    
    if (allocated >= expenseAmount) {
        pay(expenseAmount);
        surplus = allocated - expenseAmount;
    } else {
        pay(allocated);
        debt = expenseAmount - allocated;
    }
}
```

**Contoh Split 50/50:**
```
Income: 800k
Split: 50% Investasi, 50% Makanan

Periode 1:
- Investasi: allocated 400k, needs 300k → pay 300k, surplus 100k
- Makanan: allocated 400k, needs 200k → pay 200k, surplus 200k
- Balance: 800k - 500k = 300k

Periode 2 (no new income):
- Available: 300k
- Investasi: allocated 150k, needs 300k → pay 150k, debt 150k  
- Makanan: allocated 150k, needs 200k → pay 150k, debt 50k
- Balance: 300k - 300k = 0k
```

### Priority Rule Logic

```php
// Urutkan target berdasarkan jumlah expense (descending)
sort(targets, by: expenseAmount, desc);

foreach (target in sorted_targets) {
    if (availableAmount >= expenseAmount) {
        pay(expenseAmount);
        surplus = availableAmount - expenseAmount;
    } else {
        pay(availableAmount);
        debt = expenseAmount - availableAmount;
    }
    availableAmount = surplus; // untuk target berikutnya
}
```

---

## 💼 Logika Bisnis

### 1. Frequency System

| Frequency | Week Unit | Month Unit | Year Unit |
|-----------|-----------|------------|-----------|
| Weekly    | 1x        | 4x         | 52x       |
| Monthly   | Setiap 4 minggu | 1x | 12x |
| Yearly    | Setiap 52 minggu | Setiap 12 bulan | 1x |
| One-time  | 1x saja (tidak berulang) | | |

### 2. Tax Calculation

```
Net Income = Gross Income * (1 - Tax Rate / 100)

Contoh:
Gross: 1,000,000
Tax: 20%
Net: 1,000,000 * (1 - 0.20) = 800,000
```

### 3. Debt Management

**Debt** terjadi ketika:
- Alokasi < Pengeluaran yang diperlukan
- Balance tidak cukup untuk membayar

**Penyimpanan Debt:**
```php
Transaction::create([
    'amount' => debt_amount,
    'type' => 'expense',
    'category_id' => original_category_id, // bukan kategori baru
    'is_debt' => true,
    'bill_id' => bill_id // link ke Bill
]);

Bill::create([
    'amount' => debt_amount,
    'status' => 'unpaid',
    'due_date' => simulation_date
]);
```

### 4. Surplus Management

**Surplus** terjadi ketika:
- Alokasi > Pengeluaran yang diperlukan
- Ada sisa uang yang tidak terpakai

**Penyimpanan Surplus:**
```php
Transaction::create([
    'amount' => surplus_amount,
    'type' => 'income',
    'category_id' => null, // tidak pakai kategori
    'is_surplus' => true
]);
```

**Final Surplus Calculation:**
```php
// Hanya surplus terakhir yang dihitung dalam Net
$finalSurplus = (lastTransaction->is_surplus) ? lastTransaction->amount : 0;
$netWorth = totalIncome - totalExpense + $finalSurplus;
```

### 5. Validation Rules

**Income Node:**
- Amount harus > 0
- Frequency wajib diisi
- Tax rate: 0-100%
- Start delay: >= 0

**Outcome Node:**
- Amount harus > 0
- Frequency wajib diisi
- Harus terhubung ke Rule (tidak bisa langsung ke Income)

**Rule Node - Split:**
- Total persentase semua edge harus = 100%
- Minimal 1 target terhubung

**Rule Node - Priority:**
- Tidak perlu persentase
- Otomatis prioritas berdasarkan jumlah expense

---

## 🔌 API Reference

### Workflow Endpoints

#### GET `/simulator`
Menampilkan halaman simulator dengan workflow aktif

**Response:** View dengan data workflow, categories

---

#### POST `/workflows/{id}`
Menyimpan workflow (nodes + edges)

**Request Body:**
```json
{
  "nodes": [
    {
      "id": "n1",
      "type": "income",
      "name": "Salary",
      "x": 100,
      "y": 100,
      "data": {
        "amount": 5000000,
        "frequency": "monthly",
        "tax_rate": 20,
        "start_delay": 0
      }
    },
    {
      "id": "n2",
      "type": "rule",
      "name": "Main Split",
      "x": 300,
      "y": 100,
      "data": {
        "rule_type": "split"
      }
    }
  ],
  "edges": [
    {
      "fromNode": "n1",
      "fromPort": "out",
      "toNode": "n2",
      "toPort": "in",
      "data": {
        "percentage": 100
      }
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Workflow saved"
}
```

---

#### POST `/workflows/{id}/simulate`
Menjalankan simulasi

**Request Body:**
```json
{
  "periods": 12,
  "time_unit": "month"
}
```

**Response:**
```json
{
  "periods": 12,
  "time_unit": "month",
  "final_balance": 1500000.00,
  "timeline": [
    {
      "period": 1,
      "label": "Jan",
      "income": 800000.00,
      "expense": 500000.00,
      "net": 300000.00,
      "balance": 300000.00
    }
  ],
  "logs": [
    {
      "period": 1,
      "type": "income",
      "node": "Salary",
      "amount": 800000.00,
      "balance": 800000.00
    },
    {
      "period": 1,
      "type": "expense",
      "node": "Rent",
      "amount": 300000.00,
      "balance": 500000.00
    },
    {
      "period": 1,
      "type": "expense",
      "node": "Rent",
      "amount": 150000.00,
      "balance": 350000.00,
      "is_debt": true
    }
  ]
}
```

---

### Transaction Endpoints

#### POST `/transactions/save-simulation`
Menyimpan hasil simulasi ke database

**Request Body:**
```json
{
  "transactions": [
    {
      "amount": 800000,
      "type": "income",
      "category_name": "Salary",
      "description": "Simulated income from period 1",
      "date": "2026-06-08"
    },
    {
      "amount": 300000,
      "type": "expense",
      "category_name": "Rent",
      "description": "Simulated expense from period 1",
      "date": "2026-06-08"
    },
    {
      "amount": 150000,
      "type": "expense",
      "category_name": "Rent",
      "description": "Debt: Rent from period 1",
      "date": "2026-06-08",
      "is_debt": true
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "count": 10,
  "simulation_group": "SIM-20260608132909-6a26c3a53a947",
  "message": "Successfully saved 10 transactions"
}
```

---

## 🗄️ Database Schema

### Tabel: `transactions`
```sql
CREATE TABLE transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    amount DECIMAL(15,2) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    category_id BIGINT NULL,
    description TEXT NULL,
    date DATE NOT NULL,
    simulation_group VARCHAR(255) NULL,
    is_debt BOOLEAN DEFAULT FALSE,
    is_surplus BOOLEAN DEFAULT FALSE,
    bill_id BIGINT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_date (date),
    INDEX idx_simulation_group (simulation_group),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE SET NULL
);
```

### Tabel: `workflows`
```sql
CREATE TABLE workflows (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    viewport_json JSON NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
```

### Tabel: `flow_objects`
```sql
CREATE TABLE flow_objects (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    workflow_id BIGINT NOT NULL,
    type ENUM('income', 'outcome', 'rule') NOT NULL,
    name VARCHAR(255) NOT NULL,
    data_json JSON NULL,
    position_x INT DEFAULT 0,
    position_y INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE
);
```

### Tabel: `object_connections`
```sql
CREATE TABLE object_connections (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    workflow_id BIGINT NOT NULL,
    source_object_id BIGINT NOT NULL,
    target_object_id BIGINT NOT NULL,
    edge_data JSON NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE,
    FOREIGN KEY (source_object_id) REFERENCES flow_objects(id) ON DELETE CASCADE,
    FOREIGN KEY (target_object_id) REFERENCES flow_objects(id) ON DELETE CASCADE
);
```

---

## 🎨 Frontend & UI

### Simulator Canvas

**Teknologi:**
- HTML5 Canvas untuk edge rendering
- CSS Grid untuk layout
- Vanilla JavaScript untuk interaksi

**Fitur:**
- ✅ Drag & drop nodes
- ✅ Pan canvas (Alt+drag atau middle-click)
- ✅ Zoom dengan scroll
- ✅ Bezier curve connections
- ✅ Real-time validation

**Event Handling:**
```javascript
// Mouse events
- mousedown pada port → mulai koneksi
- mousemove → update preview koneksi
- mouseup pada port target → selesaikan koneksi
- mousedown pada header → mulai drag node
```

### Chart Rendering

**Chart.js Configuration:**
```javascript
// Net Worth Chart (Line)
{
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar'],
        datasets: [{
            label: 'Balance',
            data: [300000, 0, -200000], // support negative
            borderColor: '#6366f1',
            fill: true,
            tension: 0.35
        }]
    },
    options: {
        scales: {
            y: {
                grace: '5%' // padding untuk nilai negatif
            }
        }
    }
}

// Income vs Expense Chart (Bar)
{
    type: 'bar',
    data: {
        labels: ['Jan', 'Feb', 'Mar'],
        datasets: [
            { label: 'In', data: [800, 0, 0], backgroundColor: '#4ade80' },
            { label: 'Out', data: [500, 500, 500], backgroundColor: '#f87171' }
        ]
    }
}
```

---

## 🔍 Contoh Use Case

### Use Case 1: Gaji Bulanan dengan Split 50/50

**Setup:**
```
Income: Gaji (5,000,000, monthly, tax 20%)
Rule: Split 50/50
Outcomes: 
  - Investasi (300,000, monthly)
  - Makanan (200,000, monthly)
```

**Simulasi 3 bulan:**

| Period | Income | Investasi | Makanan | Balance | Notes |
|--------|--------|-----------|---------|---------|-------|
| 1 | 4,000,000 | -300,000 | -200,000 | 3,500,000 | Surplus 1.9jt/item |
| 2 | 4,000,000 | -300,000 | -200,000 | 7,000,000 | |
| 3 | 4,000,000 | -300,000 | -200,000 | 10,500,000 | |

---

### Use Case 2: One-time Income dengan Recurring Expenses

**Setup:**
```
Income: Bonus (5,000,000, one-time)
Rule: Split 100% ke Makanan
Outcomes:
  - Makanan (200,000, monthly)
```

**Simulasi 3 bulan:**

| Period | Income | Makanan Paid | Makanan Debt | Balance |
|--------|--------|--------------|--------------|---------|
| 1 | 5,000,000 | -200,000 | 0 | 4,800,000 |
| 2 | 0 | -200,000 | 0 | 4,600,000 |
| 3 | 0 | -200,000 | 0 | 4,400,000 |

---

## 🐛 Troubleshooting

### Issue: Graph tidak muncul
**Solusi:** 
- Periksa console untuk error JavaScript
- Pastikan Dagre.js dan Chart.js loaded
- Clear browser cache

### Issue: Simulasi menghasilkan balance salah
**Solusi:**
- Verifikasi persentase split total = 100%
- Periksa frequency setting
- Debug dengan melihat logs array di response

### Issue: Debt tidak tercatat
**Solusi:**
- Pastikan alokasi < expense amount
- Periksa is_debt flag di database
- Verifikasi Bill tercipta

---

## 📝 Changelog

### Version 1.0.0 (2026-06-08)
- ✅ Initial release
- ✅ Visual workflow builder
- ✅ Multi-period simulation
- ✅ Debt & surplus tracking
- ✅ Negative balance support
- ✅ Transaction history

---

## 👨‍💻 Developer Notes

### Menambah Node Type Baru

1. Tambah type di `NODE_CFG` (simulator.blade.php)
2. Tambah handling di `SimulationEngine::run()`
3. Update database enum jika perlu
4. Tambah styling di CSS

### Menambah Frequency Baru

1. Update `fireCount()` function
2. Tambah option di dropdown
3. Test dengan berbagai time unit

### Extending Rule Logic

Lihat `processSplitRule()` dan `processPriorityRule()` di `SimulationEngine.php`

---

## 📧 Support

Untuk pertanyaan atau issue, silakan buka issue di GitHub repository.

---

**Dibuat dengan ❤️ menggunakan Laravel & Chart.js**
