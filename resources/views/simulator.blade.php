@extends('layouts.app')

@section('title', 'Simulator')

@section('content')
<div class="fade-in">
    <div class="content-header">
        <h1>Simulasi Keuangan</h1>
        <div style="display:flex;gap:1rem;">
            <div style="position:relative;">
                <button class="btn btn-outline" id="addNodeBtn">+ Tambah Node</button>
                <div id="nodeMenu" style="display:none;position:absolute;top:100%;left:0;z-index:100;background:var(--card-bg);border:1px solid var(--border);border-radius:8px;min-width:200px;max-height:400px;overflow-y:auto;box-shadow:0 8px 24px rgba(0,0,0,.3);margin-top:4px;">
                    <div style="padding:.4rem .8rem;font-size:.7rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;border-bottom:1px solid var(--border);">Income (from Categories)</div>
                    @foreach($categories->where('type', 'income') as $cat)
                    <div class="node-menu-item" onclick="addNodeFromCategory('income', {{ json_encode($cat) }})">
                        {{ $cat->name }} <span style="color:var(--success);font-size:.75rem;">(Rp {{ number_format($cat->amount, 0, ',', '.') }})</span>
                    </div>
                    @endforeach
                    @if($categories->where('type', 'income')->isEmpty())
                    <div style="padding:.5rem 1rem;font-size:.75rem;color:var(--text-muted);font-style:italic;">No income categories</div>
                    @endif

                    <div style="padding:.4rem .8rem;font-size:.7rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;border-bottom:1px solid var(--border);margin-top:.5rem;">Outcome (from Categories)</div>
                    @foreach($categories->where('type', 'expense') as $cat)
                    <div class="node-menu-item" onclick="addNodeFromCategory('outcome', {{ json_encode($cat) }})">
                        {{ $cat->name }} <span style="color:var(--danger);font-size:.75rem;">(Rp {{ number_format($cat->amount, 0, ',', '.') }})</span>
                    </div>
                    @endforeach
                    @if($categories->where('type', 'expense')->isEmpty())
                    <div style="padding:.5rem 1rem;font-size:.75rem;color:var(--text-muted);font-style:italic;">No expense categories</div>
                    @endif

                    <div style="padding:.4rem .8rem;font-size:.7rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;border-bottom:1px solid var(--border);margin-top:.5rem;">Flow Control</div>
                    <div class="node-menu-item" onclick="addNode('rule')">Rule</div>
                </div>
            </div>
            <button class="btn btn-outline" onclick="openSimModal()" style="border-color:#6366f1;color:#6366f1;">&#9654; Jalankan Simulasi</button>
            <button class="btn btn-outline" onclick="autoLayoutDiagram()" style="border-color:#22d3ee;color:#22d3ee;">📐 Auto Layout</button>
            <button class="btn btn-primary" onclick="saveWorkflow()">Simpan Workflow</button>
            <button class="btn btn-outline" onclick="if(confirm('Clear all nodes and start fresh?')) { nodes = {}; edges = []; nodeCounter = 1; edgeCounter = 1; inner.innerHTML = ''; drawEdges(); showToast('Workflow cleared'); }" style="border-color:#ef4444;color:#ef4444;">🗑️ Clear</button>
        </div>
    </div>

    {{-- Canvas wrapper: position:relative so the results panel anchors inside it --}}
    <div id="canvasWrapper" class="card" style="margin-bottom:2rem;padding:0;overflow:hidden;position:relative;">
        <canvas id="edgeCanvas" style="position:absolute;pointer-events:none;z-index:2;top:0;left:0;"></canvas>

        <div id="flowCanvas"
             style="position:relative;width:100%;height:620px;overflow:hidden;cursor:grab;
                    background:#1a1a1a;background-image:radial-gradient(circle,#333 0.5px,transparent 0.5px);
                    background-size:20px 20px;">
            <div id="canvasInner" style="position:absolute;top:0;left:0;transform-origin:0 0;"></div>
            <div id="zoomHint" style="position:absolute;bottom:10px;left:12px;font-size:.68rem;color:rgba(255,255,255,.25);pointer-events:none;">
                Scroll to zoom &nbsp;|&nbsp; Alt+drag or middle-click to pan
            </div>
        </div>

        {{-- Simulation Results panel — top-right corner of the canvas wrapper --}}
        <div id="simResults"
             style="display:none;position:absolute;top:1rem;right:1rem;width:420px;max-height:595px;
                    background:rgba(13,13,26,.97);border:1px solid rgba(255,255,255,.12);
                    border-radius:12px;box-shadow:0 20px 40px rgba(0,0,0,.55);
                    overflow-y:auto;overflow-x:hidden;z-index:50;padding:1.25rem;box-sizing:border-box;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                <h3 style="margin:0;font-size:1rem;color:#e2e8f0;">&#128202; Simulation Results</h3>
                <div style="display:flex;gap:0.5rem;align-items:center;">
                    <button onclick="saveSimulationTransactions()" id="saveTxnBtn"
                            style="background:#10b981;border:none;color:white;padding:0.4rem 0.8rem;border-radius:6px;cursor:pointer;font-size:0.75rem;font-weight:600;">
                        Save Transactions
                    </button>
                    <button onclick="enableDragging(); document.getElementById('simResults').style.display='none'"
                            style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:1.1rem;line-height:1;">&#10005;</button>
                </div>
            </div>
            <div style="display:flex;gap:0.5rem;align-items:center;margin-bottom:1rem;padding:0.75rem;background:rgba(255,255,255,.05);border-radius:8px;">
                <button onclick="toggleAnimationPause()" id="pausePlayBtn" style="background:#f59e0b;border:none;color:white;padding:0.4rem 0.8rem;border-radius:4px;cursor:pointer;font-size:0.75rem;font-weight:600;">⏸ Pause</button>
            </div>
            <div id="simSummary" style="display:grid;grid-template-columns:repeat(3,1fr);gap:.6rem;margin-bottom:1rem;"></div>
            <div style="margin-bottom:.8rem;">
                <h4 style="margin:.3rem 0;font-size:.82rem;color:#cbd5e1;">Net Worth</h4>
                <div style="height:140px;"><canvas id="netWorthChart"></canvas></div>
            </div>
            <div style="margin-bottom:.8rem;">
                <h4 style="margin:.3rem 0;font-size:.82rem;color:#cbd5e1;">Income vs Expense</h4>
                <div style="height:120px;"><canvas id="incomeExpenseChart"></canvas></div>
            </div>
            <div>
                <h4 style="margin:.3rem 0;font-size:.82rem;color:#cbd5e1;">Transactions</h4>
                <div style="max-height:150px;overflow-y:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:.72rem;">
                        <thead><tr>
                            <th style="text-align:left;padding:.3rem .5rem;color:#64748b;font-weight:600;background:rgba(255,255,255,.04);">Period</th>
                            <th style="text-align:left;padding:.3rem .5rem;color:#64748b;font-weight:600;background:rgba(255,255,255,.04);">Type / Node</th>
                            <th style="text-align:right;padding:.3rem .5rem;color:#64748b;font-weight:600;background:rgba(255,255,255,.04);">Amount</th>
                        </tr></thead>
                        <tbody id="simLogBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Simulation Settings Modal --}}
<div id="simModal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.65);align-items:center;justify-content:center;">
    <div class="card" style="width:400px;padding:2rem;">
        <h2 style="margin-bottom:1.5rem;">&#9881;&#65039; Pengaturan Simulasi</h2>
        <div class="form-group">
            <label>Jumlah Periode</label>
            <input type="number" id="simPeriods" class="form-control" value="12" min="1" max="120">
        </div>
        <div class="form-group">
            <label>Satuan Waktu</label>
            <select id="simUnit" class="form-control">
                <option value="month">Bulan</option>
                <option value="week">Minggu</option>
                <option value="year">Tahun</option>
            </select>
        </div>
        <div style="display:flex;gap:1rem;margin-top:1.5rem;">
            <button class="btn btn-primary" style="flex:1;justify-content:center;" onclick="runSimulation()">&#9654; Jalankan</button>
            <button class="btn btn-outline" style="flex:1;justify-content:center;" onclick="closeSimModal()">Batal</button>
        </div>
    </div>
</div>

<style>
/* ── Flow node styles ──────────────────────────────────────────────── */
.flow-node {
    position: absolute;
    min-width: 185px;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,.45);
    user-select: none;
    cursor: move;
    z-index: 10;
    transition: box-shadow .15s;
}
.flow-node:hover   { box-shadow: 0 6px 28px rgba(0,0,0,.65); }
.flow-node.selected { outline: 2px solid #6366f1; }

.node-header {
    padding: .5rem .85rem;
    border-radius: 10px 10px 0 0;
    font-weight: 700;
    font-size: .82rem;
    display: flex;
    align-items: center;
    gap: .4rem;
    letter-spacing: .04em;
    color: #f1f5f9;
}
.node-body {
    padding: .55rem .85rem .7rem;
    background: rgba(13,13,26,.9);
    border-radius: 0 0 10px 10px;
}
.node-port-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: .15rem 0;
    font-size: .75rem;
    color: #94a3b8;
}
.port-dot {
    width: 12px; height: 12px;
    border-radius: 50%;
    cursor: crosshair;
    flex-shrink: 0;
    transition: transform .1s;
}
.port-dot:hover { transform: scale(1.45); }
.port-in  { background: #abaceeff; margin-right: .4rem; }
.port-out { background: #22d3ee; margin-left:  .4rem; }

.node-field { margin: .38rem 0; }
.node-field label {
    font-size: .7rem;
    color: #94a3b8;
    display: block;
    margin-bottom: 2px;
}
.node-field input,
.node-field select {
    width: 100%;
    padding: .28rem .48rem;
    border-radius: 5px;
    border: 1px solid rgba(255,255,255,.13);
    background: rgba(255,255,255,.07);
    color: #e2e8f0;
    font-size: .78rem;
    font-family: inherit;
}
/* Dark-themed number spinners */
.node-field input[type="number"] {
    -moz-appearance: textfield;
    appearance: textfield;
    color-scheme: dark;
}
.node-field input[type="number"]::-webkit-outer-spin-button,
.node-field input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
/* Custom number spinner styling */
.node-field input[type="number"]::-webkit-outer-spin-button,
.node-field input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: inner-spin-button;
    display: flex;
    background: rgba(255,255,255,.1);
    color: #6366f1;
    border-left: 1px solid rgba(255,255,255,.1);
}

.node-field select option { background: #1a1a2e; color: #e2e8f0; }
.node-field input:focus,
.node-field select:focus {
    outline: none;
    border-color: #6366f1;
}

/* Allocation field number input styling */
.allocation-field input[type="number"] {
    -moz-appearance: textfield;
    appearance: textfield;
    color-scheme: dark;
}
.allocation-field input[type="number"]::-webkit-outer-spin-button,
.allocation-field input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: inner-spin-button;
    display: flex;
    background: rgba(99,102,241,.2);
    color: #a78bfa;
    border-left: 1px solid rgba(99,102,241,.3);
}
.allocation-field input[type="number"]:focus {
    outline: none;
    border-color: #a78bfa;
}

.node-menu-item {
    padding: .55rem 1rem;
    cursor: pointer;
    color: var(--text-main);
    font-size: .875rem;
    transition: background .15s;
}
.node-menu-item:hover { background: rgba(99,102,241,.12); }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dagre@0.8.5/dist/dagre.min.js"></script>
<script>
// ═══════════════════════════════════════════════════════════════════════
//  BagStack Simulator — node-graph engine + simulation UI
//
//  Architecture:
//    nodes{}   — in-memory map of all flow nodes
//    edges[]   — list of connections between nodes
//    addNode() — creates a DOM node card and registers it
//    addEdge() — validates (no cycles) then registers a connection
//    drawEdges()  — redraws bezier curves on the overlay canvas
//    saveWorkflow / loadWorkflow — sync with the Laravel backend
//    runSimulation — calls /workflows/{id}/simulate and renders charts
// ═══════════════════════════════════════════════════════════════════════

const WORKFLOW_ID = {{ $workflow->id ?? 1 }};

// ── In-memory state ────────────────────────────────────────────────────
let nodes       = {};   // { [nid]: { id, type, name, x, y, data, el } }
let edges       = [];   // [{ id, fromNode, fromPort, toNode, toPort }]
let nodeCounter = 1;
let edgeCounter = 1;
let selectedNode = null;
let dragging     = null;  // { nodeId, ox, oy }
let connecting   = null;  // { fromNode, fromPort, x, y }

// ── DOM refs ───────────────────────────────────────────────────────────
const flowCanvas = document.getElementById('flowCanvas');
const inner      = document.getElementById('canvasInner');
const edgeCvs    = document.getElementById('edgeCanvas');
const ctx        = edgeCvs.getContext('2d');

// ── Zoom / pan state ───────────────────────────────────────────────────
let panX = 0, panY = 0, zoom = 1;
let panning = false, panStart = null;

// ── Throttle for drag performance ──────────────────────────────────────
let lastDrawTime = 0;
const DRAW_THROTTLE_MS = 16;  // ~60fps for dragging
let pendingDraw = false;
let rafId = null;

function throttledDrawEdges() {
    const now = Date.now();
    if (now - lastDrawTime >= DRAW_THROTTLE_MS) {
        drawEdges();
        lastDrawTime = now;
        pendingDraw = false;
        if (rafId) cancelAnimationFrame(rafId);
        rafId = null;
    } else if (!pendingDraw) {
        pendingDraw = true;
        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(() => {
            if (Date.now() - lastDrawTime >= DRAW_THROTTLE_MS) {
                drawEdges();
                lastDrawTime = Date.now();
            }
            pendingDraw = false;
            rafId = null;
        });
    }
}

// ── Node type definitions ──────────────────────────────────────────────
// Each entry describes the node's label, accent color, port names, and
// editable fields. trigger_creator has no fields — it just logs.
const NODE_CFG = {
    income: {
        label: 'Income', color: '#16a34a',
        ports: { in: [], out: ['out'] },
        fields: [
            { key: 'amount', label: 'Amount', type: 'number', readonly: true },
            { key: 'frequency', label: 'Frequency', type: 'select',
              options: ['monthly','weekly','yearly','one-time'], readonly: false },
        ],
    },
    outcome: {
        label: 'Outcome', color: '#dc2626',
        ports: { in: ['in'], out: [] },
        fields: [
            { key: 'amount', label: 'Amount', type: 'number', readonly: true },
            { key: 'frequency', label: 'Frequency', type: 'select',
              options: ['monthly','weekly','yearly','one-time'], readonly: false },
        ],
    },
    rule: {
        label: 'Rule', color: '#7c3aed',
        ports: { in: ['in'], out: ['out'] },
        fields: [
            { key: 'rule_type', label: 'Rule Type', type: 'select',
              options: ['split','priority'], readonly: false },
        ],
    },
};

// ── Add-node dropdown ──────────────────────────────────────────────────
document.getElementById('addNodeBtn').addEventListener('click', e => {
    e.stopPropagation();
    const m = document.getElementById('nodeMenu');
    m.style.display = m.style.display === 'none' ? 'block' : 'none';
});
document.addEventListener('click', () => {
    document.getElementById('nodeMenu').style.display = 'none';
});

// ── addNode: create a node card and register it ────────────────────────
function addNode(type, x, y, data = {}, id = null) {
    const cfg = NODE_CFG[type];
    if (!cfg) {
        console.error(`Unknown node type: ${type}`);
        return;
    }

    const nid = String(id ?? ('n' + nodeCounter++));  // Ensure string ID
    x = x ?? (80  + (Math.random() * 280 | 0));
    y = y ?? (80  + (Math.random() * 180 | 0));
    
    // Set default values for rule type
    if (type === 'rule' && !data.rule_type) {
        data.rule_type = 'split';
    }

    if (!inner) {
        console.error('Canvas inner element not found!');
        return;
    }

    const el = document.createElement('div');
    el.className = 'flow-node';
    el.id = 'node-' + nid;
    el.style.left = x + 'px';
    el.style.top  = y + 'px';

    const inPorts  = cfg.ports.in.map(p =>
        `<div class="node-port-row">
            <span class="port-dot port-in" data-node="${nid}" data-port="${p}" data-dir="in"></span>
            <span>${p}</span>
         </div>`).join('');

    const outPorts = cfg.ports.out.map(p =>
        `<div class="node-port-row" style="justify-content:flex-end;">
            <span>${p}</span>
            <span class="port-dot port-out" data-node="${nid}" data-port="${p}" data-dir="out"></span>
         </div>`).join('');

    const fields = cfg.fields.map(f => {
        // Use readonly property from field definition
        const isReadonly = f.readonly === true;
        return renderField(f, data[f.key], isReadonly);
    }).join('');

    el.innerHTML = `
        <div class="node-header" style="background:${cfg.color}22;border-bottom:2px solid ${cfg.color};">
            <span>${data.label || cfg.label}</span>
            <button onclick="deleteNode('${nid}')"
                    style="margin-left:auto;background:none;border:none;color:#f87171;
                           cursor:pointer;font-size:.9rem;line-height:1;" title="Delete">&#10005;</button>
        </div>
        <div class="node-body">${inPorts}${fields}<div id="node-${nid}-allocations" style="margin-top:.5rem;"></div>${outPorts}</div>`;

    inner.appendChild(el);
    nodes[nid] = { id: nid, type, name: data.label || cfg.label, x, y, data: { ...data }, el };

    // Drag the node by its header
    el.querySelector('.node-header').addEventListener('mousedown', e => {
        if (e.target.tagName === 'BUTTON') return;
        if (animationRunning || simulationCompleted) return;
        e.preventDefault();
        selectNode(nid);
        const r = el.getBoundingClientRect();
        dragging = { nodeId: nid, ox: e.clientX - r.left, oy: e.clientY - r.top };
    });

    // Start / finish a connection from a port dot
    el.querySelectorAll('.port-dot').forEach(dot => {
        dot.addEventListener('mousedown', e => {
            e.stopPropagation();
            if (dot.dataset.dir === 'out')
                connecting = { fromNode: nid, fromPort: dot.dataset.port, x: e.clientX, y: e.clientY };
        });
        dot.addEventListener('mouseup', e => {
            e.stopPropagation();
            if (connecting && dot.dataset.dir === 'in' && connecting.fromNode !== nid) {
                addEdge(connecting.fromNode, connecting.fromPort, nid, dot.dataset.port);
                connecting = null;
                drawEdges();
            }
        });
    });

    // Keep node.data in sync with field inputs
    el.querySelectorAll('[data-field]').forEach(inp => {
        inp.addEventListener('input', () => { 
            nodes[nid].data[inp.dataset.field] = inp.value;
            // Update allocations when rule type changes
            if (inp.dataset.field === 'rule_type') {
                updateAllocationFields();
            }
        });
    });

    selectNode(nid);
    drawEdges();
    return nid;
}

// Update allocations display when edges change
function updateAllocationFields() {
    // Get all rule nodes
    Object.values(nodes).forEach(node => {
        const allocContainer = document.getElementById(`node-${node.id}-allocations`);
        if (!allocContainer) return;
        
        if (node.type === 'rule' && node.data.rule_type === 'split') {
            // Get all outgoing edges from this rule
            const outgoingEdges = edges.filter(e => String(e.fromNode) === String(node.id));
            
            allocContainer.innerHTML = '';
            
            if (outgoingEdges.length === 0) {
                allocContainer.innerHTML = '<div style="font-size:.7rem;color:#64748b;margin:.3rem 0;font-style:italic;">No connections</div>';
                return;
            }
            
            // Calculate current total
            let totalPercentage = 0;
            outgoingEdges.forEach(edge => {
                totalPercentage += edge.percentage || 100;
            });
            
            // Render allocation fields
            outgoingEdges.forEach((edge, idx) => {
                const targetNode = nodes[edge.toNode];
                if (!targetNode) return;
                
                const allocDiv = document.createElement('div');
                allocDiv.className = 'allocation-field';
                allocDiv.style.cssText = 'margin:.3rem 0;display:flex;gap:.4rem;align-items:center;';
                
                const label = document.createElement('label');
                label.style.cssText = 'flex:1;font-size:.7rem;color:#94a3b8;';
                label.textContent = targetNode.name;
                
                const input = document.createElement('input');
                input.type = 'number';
                input.min = '0';
                input.max = '100';
                input.value = edge.percentage || 100;
                input.disabled = outgoingEdges.length === 1;  // Disable if only one connection
                input.style.cssText = 'width:50px;padding:.2rem .4rem;border-radius:4px;border:1px solid rgba(255,255,255,.13);background:rgba(255,255,255,.07);color:#e2e8f0;font-size:.7rem;font-family:inherit;' + (outgoingEdges.length === 1 ? 'opacity:0.6;cursor:not-allowed;' : '');
                
                input.addEventListener('change', e => {
                    let newVal = parseFloat(e.target.value) || 0;
                    newVal = Math.max(0, Math.min(100, newVal)); // Clamp to 0-100
                    
                    // If only one connection, force 100%
                    if (outgoingEdges.length === 1) {
                        newVal = 100;
                        input.value = 100;
                        edge.percentage = 100;
                    } else {
                        edge.percentage = newVal;
                        input.value = newVal;
                    }
                    
                    updateAllocationFields(); // Recalculate totals
                    drawEdges();
                });
                
                allocDiv.appendChild(label);
                allocDiv.appendChild(input);
                
                const percentLabel = document.createElement('span');
                percentLabel.style.cssText = 'font-size:.65rem;color:#64748b;min-width:28px;text-align:right;';
                percentLabel.textContent = edge.percentage + '%';
                allocDiv.appendChild(percentLabel);
                
                allocContainer.appendChild(allocDiv);
            });
            
            // Show total
            const totalDiv = document.createElement('div');
            totalDiv.style.cssText = 'margin-top:.4rem;padding-top:.4rem;border-top:1px solid rgba(255,255,255,.1);display:flex;justify-content:space-between;align-items:center;';
            
            const totalLabel = document.createElement('span');
            totalLabel.style.cssText = 'font-size:.7rem;font-weight:600;color:#cbd5e1;';
            totalLabel.textContent = 'Total:';
            totalDiv.appendChild(totalLabel);
            
            const totalValue = document.createElement('span');
            totalValue.style.cssText = `font-size:.7rem;font-weight:600;color:${totalPercentage > 100 ? '#ef4444' : totalPercentage < 100 ? '#f59e0b' : '#10b981'};`;
            totalValue.textContent = totalPercentage + '%';
            totalDiv.appendChild(totalValue);
            
            allocContainer.appendChild(totalDiv);
        } else if (node.type === 'rule' && node.data.rule_type === 'priority') {
            // Priority rules don't show percentage fields
            allocContainer.innerHTML = '<div style="font-size:.7rem;color:#94a3b8;margin:.3rem 0;font-style:italic;">Priority order by expense amount</div>';
        }
    });
}

// Helper: create node from a category
function addNodeFromCategory(type, category) {
    const data = {
        label: category.name,
        amount: category.amount,
        frequency: 'monthly',
        category: category.name,
    };
    addNode(type, null, null, data);
}

// Render a single field (input or select)
function renderField(f, val = '', readonly = false) {
    if (f.type === 'select') {
        const opts = f.options.map(o =>
            `<option value="${o}" ${val === o ? 'selected' : ''}>${o}</option>`).join('');
        return `<div class="node-field"><label>${f.label}</label>
                <select data-field="${f.key}" ${readonly ? 'disabled' : ''}>${opts}</select></div>`;
    }
    return `<div class="node-field"><label>${f.label}</label>
            <input type="${f.type || 'text'}" data-field="${f.key}" value="${val ?? ''}" ${readonly ? 'readonly' : ''}></div>`;
}

function deleteNode(nid) {
    nid = String(nid);
    
    // Find all rules that have edges to/from this node
    const affectedRules = new Set();
    edges.forEach(e => {
        if (String(e.fromNode) === nid && nodes[e.toNode]?.type === 'rule') {
            affectedRules.add(String(e.toNode));
        }
        if (String(e.toNode) === nid && nodes[e.fromNode]?.type === 'rule') {
            affectedRules.add(String(e.fromNode));
        }
    });
    
    // Remove edges connected to this node
    edges = edges.filter(e => e.fromNode !== nid && e.toNode !== nid);
    
    // Auto-redistribute percentages for affected rules
    affectedRules.forEach(ruleId => {
        const rule = nodes[ruleId];
        if (rule && rule.data.rule_type === 'split') {
            const ruleEdges = edges.filter(e => String(e.fromNode) === ruleId);
            if (ruleEdges.length > 0) {
                // Redistribute equally among remaining edges
                const perEdge = Math.floor(100 / ruleEdges.length);
                ruleEdges.forEach(e => {
                    e.percentage = perEdge;
                });
            }
        }
    });
    
    nodes[nid]?.el.remove();
    delete nodes[nid];
    if (selectedNode === nid) selectedNode = null;
    updateAllocationFields();
    drawEdges();
}

function selectNode(nid) {
    Object.values(nodes).forEach(n => n.el.classList.remove('selected'));
    selectedNode = nid;
    nodes[nid]?.el.classList.add('selected');
}

// ── addEdge: validate (no cycles) then register ────────────────────────
function addEdge(fromNode, fromPort, toNode, toPort, percentage = null) {
    // Convert to strings for consistent comparison
    fromNode = String(fromNode);
    toNode = String(toNode);
    
    if (edges.find(e => String(e.fromNode) === fromNode && String(e.toNode) === toNode)) return; // duplicate

    // Validation: Income can only connect to ONE rule
    const fromNodeObj = nodes[fromNode];
    if (fromNodeObj && fromNodeObj.type === 'income') {
        const toNodeObj = nodes[toNode];
        if (toNodeObj && toNodeObj.type === 'rule') {
            // Check if income already has a connection to any rule
            const existingRuleConnection = edges.find(e => 
                String(e.fromNode) === fromNode && 
                nodes[e.toNode] && 
                nodes[e.toNode].type === 'rule'
            );
            if (existingRuleConnection) {
                showToast('Income can only connect to ONE rule at a time!', true);
                return;
            }
        } else {
            // Income can only connect to rules, not directly to outcomes
            showToast('Income must connect to a Rule first!', true);
            return;
        }
    }

    // Validation: Expense can only be connected BY ONE rule
    const toNodeObj = nodes[toNode];
    if (toNodeObj && toNodeObj.type === 'outcome') {
        // Check if this outcome already has an incoming connection from any rule
        const existingRuleConnection = edges.find(e => 
            String(e.toNode) === toNode && 
            nodes[e.fromNode] && 
            nodes[e.fromNode].type === 'rule'
        );
        if (existingRuleConnection) {
            showToast('Expense can only be connected by ONE rule at a time!', true);
            return;
        }
    }

    if (wouldCreateCycle(fromNode, toNode)) {
        showToast('Connection would create a loop — not allowed!', true);
        return;
    }
    
    // For split rules: calculate default percentage based on existing connections
    let defaultPercentage = null;  // null = no percentage field
    const sourceNode = nodes[fromNode];
    if (sourceNode && sourceNode.type === 'rule' && sourceNode.data.rule_type === 'split') {
        // Only split rules get percentages
        const existingEdges = edges.filter(e => String(e.fromNode) === fromNode);
        
        if (existingEdges.length > 0) {
            // Calculate how many edges will exist after adding this one
            const totalEdgesAfter = existingEdges.length + 1;
            // Distribute 100% equally among all edges (including the new one)
            defaultPercentage = Math.floor(100 / totalEdgesAfter);
            
            // Redistribute ALL existing edges to equal amounts
            const perEdge = Math.floor(100 / totalEdgesAfter);
            existingEdges.forEach(e => { e.percentage = perEdge; });
        } else {
            // First edge on this rule gets 100%
            defaultPercentage = 100;
        }
    }
    
    edges.push({ 
        id: 'e' + edgeCounter++, 
        fromNode, 
        fromPort, 
        toNode, 
        toPort,
        percentage: defaultPercentage
    });
    
    updateAllocationFields();
}

// DFS cycle check: would adding fromNode→toNode create a cycle?
function wouldCreateCycle(fromNode, toNode) {
    // Convert to strings for consistent comparison
    fromNode = String(fromNode);
    toNode = String(toNode);
    
    const adj = {};
    Object.keys(nodes).forEach(id => { adj[String(id)] = []; });
    edges.forEach(e => { 
        const from = String(e.fromNode);
        const to = String(e.toNode);
        if (adj[from]) adj[from].push(to); 
    });
    if (adj[fromNode]) adj[fromNode].push(toNode); // tentative

    const visited = new Set();
    function dfs(id) {
        id = String(id);
        if (id === fromNode) return true;   // found a back-edge
        if (visited.has(id)) return false;
        visited.add(id);
        return (adj[id] || []).some(dfs);
    }
    return dfs(toNode);
}

// Prompt for percentage allocation on split rule connections
function promptForPercentage(edge) {
    const percentage = prompt('Enter percentage allocation for this connection (0-100):', edge.percentage || 100);
    if (percentage !== null) {
        const val = parseFloat(percentage);
        if (!isNaN(val) && val >= 0 && val <= 100) {
            edge.percentage = val;
            updateAllocationFields();
            drawEdges();
        } else {
            showToast('Invalid percentage! Must be between 0 and 100.', true);
        }
    }
}

// ── drawEdges: bezier curves on the overlay canvas ─────────────────────
function syncCanvasSize() {
    const r = flowCanvas.getBoundingClientRect();
    edgeCvs.width  = r.width;
    edgeCvs.height = r.height;
}

function getPortPos(nid, port, dir) {
    nid = String(nid); // Ensure string comparison
    const dot = inner.querySelector(`[data-node="${nid}"][data-port="${port}"][data-dir="${dir}"]`);
    if (!dot) return null;
    const cr = flowCanvas.getBoundingClientRect();
    const dr = dot.getBoundingClientRect();
    return { x: dr.left + dr.width / 2 - cr.left, y: dr.top + dr.height / 2 - cr.top };
}

function drawEdges() {
    // Only sync canvas size if it's actually changed
    const r = flowCanvas.getBoundingClientRect();
    if (edgeCvs.width !== r.width || edgeCvs.height !== r.height) {
        edgeCvs.width  = r.width;
        edgeCvs.height = r.height;
    }
    
    ctx.clearRect(0, 0, edgeCvs.width, edgeCvs.height);
    
    const highlightedCount = edges.filter(e => e._highlighted).length;
    if (highlightedCount > 0) {
        // Drawing highlighted edges
    }

    edges.forEach(e => {
        const a = getPortPos(e.fromNode, e.fromPort, 'out');
        const b = getPortPos(e.toNode,   e.toPort,   'in');
        if (!a || !b) return;
        const cx = (a.x + b.x) / 2;
        
        // Highlight arrow if marked during animation
        if (e._highlighted) {
            ctx.lineWidth   = 5;
            ctx.strokeStyle = ANIMATION_CONFIG.arrowColor;
            ctx.shadowColor = ANIMATION_CONFIG.arrowColor;
            ctx.shadowBlur  = ANIMATION_CONFIG.arrowGlow;
            ctx.shadowOffsetX = 0;
            ctx.shadowOffsetY = 0;
        } else {
            ctx.lineWidth   = 2.5;
            ctx.strokeStyle = '#818cf8';
            ctx.shadowColor = '#6366f1aa';
            ctx.shadowBlur  = 6;
            ctx.shadowOffsetX = 0;
            ctx.shadowOffsetY = 0;
        }
        
        ctx.beginPath();
        ctx.moveTo(a.x, a.y);
        ctx.bezierCurveTo(cx, a.y, cx, b.y, b.x, b.y);
        ctx.stroke();
    });

    if (connecting) {
        const a  = getPortPos(connecting.fromNode, connecting.fromPort, 'out');
        const cr = flowCanvas.getBoundingClientRect();
        if (a) {
            const bx = connecting.x - cr.left, by = connecting.y - cr.top;
            ctx.lineWidth   = 2.5;
            ctx.strokeStyle = '#22d3ee';
            ctx.shadowBlur  = 8;
            ctx.shadowColor = '#22d3eeaa';
            ctx.beginPath();
            ctx.moveTo(a.x, a.y);
            ctx.bezierCurveTo((a.x + bx) / 2, a.y, (a.x + bx) / 2, by, bx, by);
            ctx.stroke();
        }
    }
    ctx.shadowBlur = 0;
}


window.addEventListener('resize', () => { syncCanvasSize(); drawEdges(); });
syncCanvasSize();

// ── Zoom & Pan ─────────────────────────────────────────────────────────
function applyTransform() {
    inner.style.transform = `translate(${panX}px,${panY}px) scale(${zoom})`;
    drawEdges();
}

// Mouse-wheel → zoom toward cursor
flowCanvas.addEventListener('wheel', e => {
    e.preventDefault();
    const newZoom = Math.min(2.5, Math.max(0.25, zoom + (e.deltaY > 0 ? -0.1 : 0.1)));
    const r  = flowCanvas.getBoundingClientRect();
    const mx = e.clientX - r.left, my = e.clientY - r.top;
    panX = mx - (mx - panX) * (newZoom / zoom);
    panY = my - (my - panY) * (newZoom / zoom);
    zoom = newZoom;
    applyTransform();
}, { passive: false });

// Middle-click or Alt+left-click → pan
flowCanvas.addEventListener('mousedown', e => {
    if (e.button === 1 || (e.button === 0 && e.altKey)) {
        e.preventDefault();
        panning  = true;
        panStart = { x: e.clientX - panX, y: e.clientY - panY };
        flowCanvas.style.cursor = 'grabbing';
    }
});

document.addEventListener('mousemove', e => {
    if (panning && panStart) {
        panX = e.clientX - panStart.x;
        panY = e.clientY - panStart.y;
        applyTransform();
        return;
    }
    if (dragging) {
        const r  = flowCanvas.getBoundingClientRect();
        const nx = Math.max(0, (e.clientX - r.left - panX) / zoom - dragging.ox / zoom);
        const ny = Math.max(0, (e.clientY - r.top  - panY) / zoom - dragging.oy / zoom);
        const n  = nodes[dragging.nodeId];
        n.el.style.left = nx + 'px';
        n.el.style.top  = ny + 'px';
        n.x = nx; n.y = ny;
        throttledDrawEdges();  // Use throttled version for better performance
    }
    if (connecting) {
        connecting.x = e.clientX;
        connecting.y = e.clientY;
        throttledDrawEdges();  // Use throttled version for better performance
    }
});

document.addEventListener('mouseup', () => {
    if (panning) { panning = false; panStart = null; flowCanvas.style.cursor = 'grab'; }
    if (dragging) {
        dragging = null;
        pendingDraw = false;  // Clear pending flag
        drawEdges();  // Force final draw after dragging ends
    }
    if (connecting) { connecting = null; drawEdges(); }
});

// ── Save / Load ────────────────────────────────────────────────────────
async function saveWorkflow() {
    const payload = {
        nodes: Object.values(nodes).map(n => ({
            id: n.id, type: n.type, name: n.name,
            position_x: n.x, position_y: n.y, data_json: n.data,
        })),
        edges: edges.map(e => ({
            source: e.fromNode, source_port: e.fromPort,
            target: e.toNode,   target_port: e.toPort,
            percentage: e.percentage || 100,
        })),
    };
    try {
        const r   = await fetch(`/workflows/${WORKFLOW_ID}/sync`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            body: JSON.stringify(payload),
        });
        const res = await r.json();
        if (res.status === 'success') showToast('Workflow disimpan!');
        else showToast(res.message || 'Gagal menyimpan.', true);
    } catch (err) { console.error(err); showToast('Gagal menyimpan.', true); }
}

async function loadWorkflow() {
    try {
        const r  = await fetch(`/workflows/${WORKFLOW_ID}/state`);
        const wf = await r.json();
        
        if (wf.objects && wf.objects.length > 0) {
            wf.objects.forEach(obj => {
                const objId = String(obj.id);  // Ensure string ID
                
                // Ensure data_json is properly parsed
                let dataJson = obj.data_json;
                if (typeof dataJson === 'string') {
                    try {
                        dataJson = JSON.parse(dataJson);
                    } catch (e) {
                        console.warn(`Could not parse data_json for ${objId}:`, e);
                        dataJson = {};
                    }
                }
                
                // Ensure label is set
                if (!dataJson.label) {
                    dataJson.label = obj.name;
                }
                
                addNode(obj.type, obj.position_x || 0, obj.position_y || 0, dataJson || {}, objId);
            });
            
            (wf.connections ?? []).forEach(c => {
                const ed = c.edge_data ?? {};
                const srcId = String(c.source_object_id);
                const tgtId = String(c.target_object_id);
                addEdge(srcId, ed.source_output ?? 'out',
                        tgtId, ed.target_input  ?? 'in',
                        ed.percentage);
            });
        }
        updateAllocationFields();
        drawEdges();
    } catch (err) { 
        showToast('Error loading workflow: ' + err.message, true);
    }
}

// ── Simulation ─────────────────────────────────────────────────────────
let netWorthChart  = null;
let incomeExpChart = null;

function openSimModal()  { document.getElementById('simModal').style.display = 'flex'; }
function closeSimModal() { document.getElementById('simModal').style.display = 'none'; }

async function runSimulation() {
    const periods  = parseInt(document.getElementById('simPeriods').value) || 12;
    const timeUnit = document.getElementById('simUnit').value;
    closeSimModal();
    simulationCompleted = false;  // Reset flag for new simulation
    animationCancelled = false;   // Reset cancellation flag for new simulation
    currentPeriod = 1;            // Reset to period 1 when starting new simulation
    await saveWorkflow();
    showToast('Running simulation…');
    try {
        const r      = await fetch(`/workflows/${WORKFLOW_ID}/simulate`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            body: JSON.stringify({ periods, time_unit: timeUnit }),
        });
        const result = await r.json();
        renderSimResults(result);
        // Start animation immediately (no delay)
        playSimulationAnimation(result);
    } catch (err) { console.error(err); showToast('Simulation failed!', true); }
}

// ── Animation: step-by-step flow visualization ────────────────────────
const ANIMATION_CONFIG = {
    nodeDuration: 200,        // How long to highlight each node (ms)
    arrowDuration: 200,       // How long to highlight each arrow (ms)
    nodeSleep: 0,             // Extra sleep after node highlight (ms)
    arrowSleep: 100,          // Extra sleep after arrow highlight (ms)
    nodeColor: '#c52d2dff',     // Node highlight color
    nodeGlow: 50,             // Node glow blur (px)
    arrowColor: '#c52d2dff',    // Arrow highlight color
    arrowGlow: 20,            // Arrow glow blur (px)
};

let animationRunning = false;
let animationCancelled = false;
let simulationCompleted = false;  // Track if simulation has been run
let lastSimulationData = null;  // Store last simulation results for saving
let animationPaused = false;    // Track if animation is paused

async function playSimulationAnimation(data) {
    lastSimulationData = data;  // Store for later use
    const logs = data.logs || [];
    if (logs.length === 0) return;

    animationRunning = true;
    animationCancelled = false;
    animationPaused = false;

    // Show period marker
    let marker = document.getElementById('animMarker');
    if (!marker) {
        marker = document.createElement('div');
        marker.id = 'animMarker';
        marker.style.cssText = `position:absolute;top:12px;left:12px;z-index:25;
            background:rgba(99,102,241,.25);border:2px solid #6366f1;border-radius:8px;
            padding:8px 14px;font-weight:700;color:#6366f1;font-size:.85rem;
            backdrop-filter:blur(6px);box-shadow:0 4px 12px rgba(99,102,241,.3);`;
        document.getElementById('canvasWrapper').appendChild(marker);
    }

    // Group logs by period
    const byPeriod = {};
    logs.forEach(log => {
        if (!byPeriod[log.period]) byPeriod[log.period] = [];
        byPeriod[log.period].push(log);
    });

    // Animate ALL periods
    for (let p = 1; p <= data.periods; p++) {
        if (animationCancelled) break;
        
        // Wait if paused
        while (animationPaused && !animationCancelled) {
            await sleep(100);
        }
        
        marker.textContent = `Period ${p} / ${data.periods}`;
        marker.style.display = 'block';

        const periodLogs = byPeriod[p] || [];
        if (periodLogs.length === 0) {
            await sleep(100);
            continue;
        }

        // Get all income nodes that appear in this period's logs
        const incomeNodesInPeriod = new Set();
        periodLogs.forEach(log => {
            const nodeId = String(Object.keys(nodes).find(id => nodes[id].name === log.node) || '');
            if (nodeId && nodes[nodeId]?.type === 'income') {
                incomeNodesInPeriod.add(nodeId);
            }
        });

        // Animate each income branch separately
        for (const incomeId of incomeNodesInPeriod) {
            if (animationCancelled) break;
            
            // Wait if paused
            while (animationPaused && !animationCancelled) {
                await sleep(100);
            }
            
            // Build animation steps for this specific income
            const steps = buildAnimationStepsForIncome(incomeId);
            
            // Execute each step
            let lastNodeId = null;
            for (let i = 0; i < steps.length; i++) {
                if (animationCancelled) break;
                
                // Wait if paused
                while (animationPaused && !animationCancelled) {
                    await sleep(100);
                }
                
                const step = steps[i];
                
                if (step.type === 'node') {
                    // Clear previous node highlight if exists
                    if (lastNodeId !== null) {
                        clearNodeHighlight(lastNodeId);
                    }
                    highlightNode(step.id);
                    lastNodeId = step.id;
                    await sleep(ANIMATION_CONFIG.nodeDuration + ANIMATION_CONFIG.nodeSleep);
                } else if (step.type === 'arrow') {
                    // Clear the current node highlight before showing arrow
                    if (lastNodeId !== null) {
                        clearNodeHighlight(lastNodeId);
                        lastNodeId = null;
                    }
                    highlightArrow(step.from, step.to);
                    await animateFlowParticle(step.from, step.to, ANIMATION_CONFIG.arrowDuration);
                    clearArrowHighlight(step.from, step.to);
                    await sleep(ANIMATION_CONFIG.arrowSleep);
                }
            }

            // Clear the last node highlight if still active
            if (lastNodeId !== null) {
                clearNodeHighlight(lastNodeId);
            }
        }
        
        await sleep(200);
    }

    marker.style.display = 'none';
    animationRunning = false;
    animationPaused = false;
    document.getElementById('pausePlayBtn').textContent = '⏸ Pause';
}

function stopAnimation() {
    animationCancelled = true;
    animationRunning = false;
    // Clear all highlights immediately
    Object.keys(nodes).forEach(id => clearNodeHighlight(id));
    edges.forEach(e => {
        e._highlighted = false;
    });
    drawEdges();
    const marker = document.getElementById('animMarker');
    if (marker) marker.style.display = 'none';
}

function enableDragging() {
    // Stop any running animation
    animationCancelled = true;
    animationRunning = false;
    simulationCompleted = false;
    
    // Clear all highlights
    Object.keys(nodes).forEach(id => clearNodeHighlight(id));
    edges.forEach(e => {
        e._highlighted = false;
    });
    drawEdges();
    
    // Hide marker
    const marker = document.getElementById('animMarker');
    if (marker) marker.style.display = 'none';
}

// Build step sequence for a single income: node → arrow → node → arrow → ...
function buildAnimationStepsForIncome(incomeId) {
    const steps = [];
    incomeId = String(incomeId);
    
    const visited = new Set();
    const addedEdges = new Set();
    
    function traceFlow(nodeId) {
        nodeId = String(nodeId);
        
        if (visited.has(nodeId)) return;
        visited.add(nodeId);
        
        // Skip one-time outcome nodes from animation
        const node = nodes[nodeId];
        if (node && node.type === 'outcome') {
            const freq = node.data?.frequency || 'monthly';
            if (freq === 'one-time') {
                return; // Don't animate one-time expenses
            }
        }
        
        // Add this node
        steps.push({ type: 'node', id: nodeId });
        
        // Find outgoing edges and trace them
        const outgoingEdges = edges.filter(e => String(e.fromNode) === nodeId);
        
        for (const edge of outgoingEdges) {
            const targetId = String(edge.toNode);
            const edgeKey = `${nodeId}->${targetId}`;
            
            // Only add edge once per income trace
            if (!addedEdges.has(edgeKey)) {
                steps.push({ type: 'arrow', from: nodeId, to: targetId });
                addedEdges.add(edgeKey);
            }
            
            // Recurse into the target
            if (!visited.has(targetId)) {
                traceFlow(targetId);
            }
        }
    }
    
    // Start tracing from this income
    traceFlow(incomeId);
    
    return steps;
}

// Find shortest path between two nodes using BFS
function findPath(startId, endId) {
    // Convert to strings for consistent comparison
    startId = String(startId);
    endId = String(endId);
    
    if (startId === endId) return [startId];
    
    const queue = [[startId]];
    const visited = new Set([startId]);
    
    console.log(`  BFS from ${startId} to ${endId}, edges available:`, edges.filter(e => String(e.fromNode) === startId).map(e => `${e.fromNode}→${e.toNode}`));
    
    while (queue.length > 0) {
        const path = queue.shift();
        const currentId = path[path.length - 1];
        
        // Get all outgoing edges from current node (compare as strings)
        const outgoingEdges = edges.filter(e => String(e.fromNode) === String(currentId));
        
        for (const edge of outgoingEdges) {
            const nextId = String(edge.toNode);
            
            if (nextId === endId) {
                return [...path, nextId];
            }
            
            if (!visited.has(nextId)) {
                visited.add(nextId);
                queue.push([...path, nextId]);
            }
        }
    }
    
    // No path found
    return [];
}

function highlightNode(nid) {
    nid = String(nid);
    const node = nodes[nid];
    if (!node) return;
    // Change border color to configured color
    node.el.style.outline = `3px solid ${ANIMATION_CONFIG.nodeColor}`;
    node.el.style.outlineOffset = '2px';
    const rgb = ANIMATION_CONFIG.nodeColor === '#6366f1' ? '99, 102, 241' : '245, 158, 11';
    node.el.style.boxShadow = `0 0 ${ANIMATION_CONFIG.nodeGlow}px 3px rgba(${rgb}, 0.6), 0 4px 20px rgba(0,0,0,.4)`;
    node.el.style.transition = 'all 0.2s ease-out';
}

function clearNodeHighlight(nid) {
    nid = String(nid);
    const node = nodes[nid];
    if (!node) return;
    node.el.style.outline = 'none';
    node.el.style.outlineOffset = '0px';
    node.el.style.boxShadow = '0 4px 20px rgba(0,0,0,.45)';
    node.el.style.transition = '';  // Remove transition to prevent drag lag
}

function highlightArrow(fromId, toId) {
    // Find the edge and temporarily increase its visual weight
    fromId = String(fromId);
    toId = String(toId);
    const edge = edges.find(e => String(e.fromNode) === fromId && String(e.toNode) === toId);
    console.log(`highlightArrow: ${nodes[fromId]?.name} (${fromId}) → ${nodes[toId]?.name} (${toId}), edge found: ${!!edge}`);
    if (edge) {
        edge._highlighted = true;
        drawEdges(); // redraw with highlight
    } else {
        console.warn(`  Edge not found! Available edges from ${fromId}:`, edges.filter(e => String(e.fromNode) === fromId).map(e => `${e.fromNode}→${e.toNode}`));
    }
}

function clearArrowHighlight(fromId, toId) {
    fromId = String(fromId);
    toId = String(toId);
    const edge = edges.find(e => String(e.fromNode) === fromId && String(e.toNode) === toId);
    if (edge) {
        edge._highlighted = false;
        drawEdges();
    }
}

async function animateFlowParticle(fromId, toId, duration = 400) {
    return new Promise(resolve => {
        // Just wait for the duration, no particle animation
        setTimeout(resolve, duration);
    });
}

function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

function fmt(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }

function renderSimResults(data) {
    document.getElementById('simResults').style.display = 'block';

    const tl           = data.timeline || [];
    const labels       = tl.map(t => t.label);
    const totalIncome  = tl.reduce((s, t) => s + t.income,  0);
    const totalExpense = tl.reduce((s, t) => s + t.expense, 0);
    const finalBal     = data.final_balance;

    // Summary cards
    document.getElementById('simSummary').innerHTML = [
        { label: 'Final',   value: fmt(finalBal),     color: finalBal >= 0 ? '#22d3ee' : '#f87171' },
        { label: 'Income',  value: fmt(totalIncome),  color: '#4ade80' },
        { label: 'Expense', value: fmt(totalExpense), color: '#f87171' },
    ].map(s => `
        <div style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);
                    border-radius:6px;padding:.5rem;text-align:center;">
            <div style="font-size:.62rem;color:#64748b;margin-bottom:.2rem;">${s.label}</div>
            <div style="font-size:.82rem;font-weight:700;color:${s.color};">${s.value}</div>
        </div>`).join('');

    // Shared chart options
    const scales = {
        x: { ticks: { color: '#64748b', font: { size: 8 } }, grid: { color: 'rgba(255,255,255,.05)' } },
        y: { ticks: { color: '#64748b', font: { size: 8 },
                      callback: v => (v / 1e6).toFixed(1) + 'M' },
             grid: { color: 'rgba(255,255,255,.05)' } },
    };
    const legend = { labels: { color: '#94a3b8', font: { size: 9 } }, position: 'bottom' };

    if (netWorthChart)  netWorthChart.destroy();
    if (incomeExpChart) incomeExpChart.destroy();

    netWorthChart = new Chart(document.getElementById('netWorthChart'), {
        type: 'line',
        data: { labels, datasets: [{
            label: 'Balance', data: tl.map(t => t.balance),
            borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,.12)',
            fill: true, tension: 0.35, pointRadius: 2, pointBackgroundColor: '#6366f1',
        }]},
        options: { responsive: true, maintainAspectRatio: false,
                   plugins: { legend }, scales },
    });

    incomeExpChart = new Chart(document.getElementById('incomeExpenseChart'), {
        type: 'bar',
        data: { labels, datasets: [
            { label: 'In',  data: tl.map(t => t.income),  backgroundColor: 'rgba(74,222,128,.65)' },
            { label: 'Out', data: tl.map(t => t.expense), backgroundColor: 'rgba(248,113,113,.65)' },
        ]},
        options: { responsive: true, maintainAspectRatio: false,
                   plugins: { legend }, scales },
    });

    // Transaction log rows - show all logs
    const rows = (data.logs || []).map(l => {
        let color, bg;
        
        // Determine color based on type and status
        if (l.type === 'income') {
            color = '#4ade80';
            bg = 'rgba(74,222,128,.15)';
        } else if (l.type === 'rule') {
            color = '#f59e0b';
            bg = 'rgba(245,158,11,.15)';
        } else if (l.type === 'trigger') {
            color = '#fbbf24';
            bg = 'rgba(251,191,36,.15)';
        } else if (l.type === 'expense') {
            // Check if this is a paid expense with no debt/surplus
            if (!l.node.includes('(debt)') && !l.node.includes('(surplus)')) {
                // Fully paid expense - use purple
                color = '#a78bfa';
                bg = 'rgba(167,139,250,.15)';
            } else if (l.node.includes('(debt)')) {
                // Debt - use red
                color = '#f87171';
                bg = 'rgba(248,113,113,.15)';
            } else if (l.node.includes('(surplus)')) {
                // Surplus - use green
                color = '#4ade80';
                bg = 'rgba(74,222,128,.15)';
            } else {
                color = '#f87171';
                bg = 'rgba(248,113,113,.15)';
            }
        } else {
            color = '#f87171';
            bg = 'rgba(248,113,113,.15)';
        }
        
        // Handle "Split" text for rule amounts
        const amountDisplay = (typeof l.amount === 'string') ? l.amount : fmt(l.amount);
        
        return `<tr>
            <td style="padding:.25rem .5rem;color:#94a3b8;">${l.period}</td>
            <td style="padding:.25rem .5rem;">
                <span style="padding:.1rem .4rem;border-radius:4px;font-size:.65rem;font-weight:600;background:${bg};color:${color};">${l.type}</span>
                <span style="color:#64748b;margin-left:.3rem;font-size:.68rem;">${l.node}</span>
            </td>
            <td style="text-align:right;padding:.25rem .5rem;color:${color};">${amountDisplay}</td>
        </tr>`;
    }).join('');

    document.getElementById('simLogBody').innerHTML =
        rows || '<tr><td colspan="3" style="text-align:center;color:#475569;padding:.6rem;">No logs</td></tr>';
}

// ── Utilities ──────────────────────────────────────────────────────────
function csrfToken() {
    return document.querySelector('meta[name=csrf-token]')?.content || '';
}

function showToast(msg, err = false) {
    const t = document.createElement('div');
    t.textContent = msg;
    Object.assign(t.style, {
        position: 'fixed', bottom: '2rem', right: '2rem', zIndex: 9999,
        padding: '.7rem 1.2rem', borderRadius: '8px', fontWeight: '600',
        background: err ? '#dc2626' : '#16a34a', color: '#fff',
        boxShadow: '0 4px 16px rgba(0,0,0,.4)', transition: 'opacity .4s',
    });
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 2500);
}

// ── Save Simulation Transactions ──────────────────────────────────────
async function saveSimulationTransactions() {
    if (!lastSimulationData || !lastSimulationData.logs) {
        showToast('No simulation data to save!', true);
        return;
    }

    const saveTxnBtn = document.getElementById('saveTxnBtn');
    saveTxnBtn.disabled = true;
    saveTxnBtn.textContent = 'Saving...';

    try {
        // Prepare transactions from simulation logs
        const transactions = [];
        const today = new Date();
        
        // Track which expenses we've already saved per period
        const savedExpenses = new Set();
        
        lastSimulationData.logs.forEach((log) => {
            // Only process income and expense types
            if (log.type !== 'income' && log.type !== 'expense') {
                return;
            }

            if (log.type === 'income') {
                // Income: save as-is
                const amount = Math.abs(log.amount);
                
                // Calculate date based on period and time unit
                const txnDate = new Date(today);
                const period = log.period - 1; // 0-indexed
                
                if (lastSimulationData.time_unit === 'month') {
                    txnDate.setMonth(txnDate.getMonth() + period);
                } else if (lastSimulationData.time_unit === 'week') {
                    txnDate.setDate(txnDate.getDate() + (period * 7));
                } else if (lastSimulationData.time_unit === 'year') {
                    txnDate.setFullYear(txnDate.getFullYear() + period);
                }
                
                transactions.push({
                    amount: amount,
                    type: 'income',
                    category_name: log.node,
                    description: `Simulated income from period ${log.period}`,
                    date: txnDate.toISOString().split('T')[0]
                });
            } else if (log.type === 'expense') {
                // Expense: save each expense once per period
                const expenseKey = `${log.period}_${log.node}`;
                if (savedExpenses.has(expenseKey)) {
                    return; // Already saved this expense for this period
                }
                savedExpenses.add(expenseKey);
                
                const amount = Math.abs(log.amount);
                
                // Calculate date based on period and time unit
                const txnDate = new Date(today);
                const period = log.period - 1; // 0-indexed
                
                if (lastSimulationData.time_unit === 'month') {
                    txnDate.setMonth(txnDate.getMonth() + period);
                } else if (lastSimulationData.time_unit === 'week') {
                    txnDate.setDate(txnDate.getDate() + (period * 7));
                } else if (lastSimulationData.time_unit === 'year') {
                    txnDate.setFullYear(txnDate.getFullYear() + period);
                }
                
                transactions.push({
                    amount: amount,
                    type: 'expense',
                    category_name: log.node,
                    description: `Simulated expense from period ${log.period}`,
                    date: txnDate.toISOString().split('T')[0]
                });
            }
        });

        if (transactions.length === 0) {
            showToast('No transactions to save!', true);
            saveTxnBtn.disabled = false;
            saveTxnBtn.textContent = 'Save Transactions';
            return;
        }

        // Send to server
        const response = await fetch('/transactions/save-simulation', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken()
            },
            body: JSON.stringify({ transactions })
        });

        const result = await response.json();

        if (result.success) {
            showToast(`✅ Saved ${result.count} transactions!`);
            saveTxnBtn.textContent = '✓ Saved';
            setTimeout(() => {
                saveTxnBtn.textContent = 'Save Transactions';
                saveTxnBtn.disabled = false;
            }, 2000);
        } else {
            showToast(result.message || 'Failed to save transactions', true);
            saveTxnBtn.disabled = false;
            saveTxnBtn.textContent = 'Save Transactions';
        }
    } catch (error) {
        showToast('Error saving transactions: ' + error.message, true);
        saveTxnBtn.disabled = false;
        saveTxnBtn.textContent = 'Save Transactions';
    }
}

// ── Period Navigation & Chart Updates ──────────────────────────────────
function toggleAnimationPause() {
    if (!animationRunning) return;
    animationPaused = !animationPaused;
    const btn = document.getElementById('pausePlayBtn');
    btn.textContent = animationPaused ? '▶ Play' : '⏸ Pause';
}

// ── Auto Layout using Dagre ───────────────────────────────────────────
function autoLayoutDiagram() {
    if (Object.keys(nodes).length === 0) {
        showToast('No nodes to layout!', true);
        return;
    }

    // Create a new Dagre graph
    const g = new dagre.graphlib.Graph({ compound: false });
    g.setGraph({ rankdir: 'TB', nodesep: 80, ranksep: 100 });
    g.setDefaultEdgeLabel(() => ({}));

    // Add all nodes to the graph with their current dimensions
    Object.values(nodes).forEach(node => {
        const width = node.el.offsetWidth || 200;
        const height = node.el.offsetHeight || 150;
        g.setNode(String(node.id), { width, height });
    });

    // Add all edges to the graph
    edges.forEach(edge => {
        g.setEdge(String(edge.fromNode), String(edge.toNode));
    });

    // Run Dagre layout algorithm
    dagre.layout(g);

    // Apply the calculated positions to nodes
    g.nodes().forEach(nodeId => {
        const node = nodes[nodeId];
        if (node) {
            const pos = g.node(nodeId);
            // Dagre positions are centered, but we need top-left for absolute positioning
            const x = pos.x - (pos.width / 2);
            const y = pos.y - (pos.height / 2);
            
            node.x = Math.max(0, x);
            node.y = Math.max(0, y);
            node.el.style.left = node.x + 'px';
            node.el.style.top = node.y + 'px';
        }
    });

    // Redraw edges with new positions
    drawEdges();
    showToast('✨ Diagram auto-laid out!');
}

// ── Boot ───────────────────────────────────────────────────────────────
loadWorkflow();
</script>
@endsection
