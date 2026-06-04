<?php

namespace App\Services;

use App\Models\Workflow;

/**
 * SimulationEngine
 *
 * Runs a period-by-period financial simulation over a node graph.
 *
 * Node types:
 *   income          – generates money each period
 *   rule            – routes money (split by percentage, or priority based on expense amounts)
 *   outcome         – deducts money (expense) — only fires when connected in the graph
 *   trigger_creator – logs a marker event, no money movement
 *
 * Graph is stored as adjacency lists built from the workflow's
 * FlowObject and ObjectConnection records.
 */
class SimulationEngine
{
    /** @var array<int, \App\Models\FlowObject> */
    protected array $nodes = [];

    /** @var array<int, int[]>  nodeId → [targetId, ...] */
    protected array $adjOut = [];
    
    /** @var array<string, array> edge metadata (percentage, etc.) */
    protected array $edgeData = [];

    public function __construct(Workflow $workflow)
    {
        $workflow->load('objects', 'connections');

        foreach ($workflow->objects as $obj) {
            $this->nodes[$obj->id] = $obj;
            $this->adjOut[$obj->id] = [];
        }

        foreach ($workflow->connections as $conn) {
            $this->adjOut[$conn->source_object_id][] = $conn->target_object_id;
            $edgeKey = $conn->source_object_id . '->' . $conn->target_object_id;
            $this->edgeData[$edgeKey] = $conn->edge_data ?? [];
        }
    }

    // ── Public entry point ────────────────────────────────────────────────

    public function run(int $periods = 12, string $timeUnit = 'month'): array
    {
        $balance = 0.0;
        $timeline = [];
        $logs = [];
        $firedOnce = [];
        $periodFiredExpenses = [];  // Track which expenses fired per period

        for ($p = 1; $p <= $periods; $p++) {
            $periodIncome = 0.0;
            $periodExpense = 0.0;
            $periodEvents = [];
            $periodFiredExpenses[$p] = [];  // Track expenses for this period

            // Fire every income node
            foreach ($this->nodes as $id => $obj) {
                if ($obj->type !== 'income')
                    continue;

                $data = $obj->data_json ?? [];
                $amount = (float) ($data['amount'] ?? 0);
                $freq = $data['frequency'] ?? 'monthly';
                $taxRate = (float) ($data['tax_rate'] ?? 0) / 100;
                $startDelay = (int) ($data['start_delay'] ?? 0);

                if ($p <= $startDelay)
                    continue;

                // Returns how many times this node fires this period (e.g. weekly in a month = 4)
                $count = $this->fireCount($freq, $p, $startDelay, $timeUnit, $firedOnce, $id);

                if ($count === 0)
                    continue;

                if ($freq === 'one-time')
                    $firedOnce[$id] = true;

                // Multiply net amount by fire count to account for
                // higher-frequency income within a coarser time unit
                $net = $amount * (1 - $taxRate) * $count;

                $balance += $net;
                $periodIncome += $net;
                $periodEvents[] = [
                    'period' => $p,
                    'type' => 'income',
                    'node' => $obj->name,
                    'amount' => round($net, 2),
                    'balance' => round($balance, 2),
                ];

                // Propagate money downstream through rules / outcomes
                $this->pushFlow($id, $net, $p, $timeUnit, $balance, $periodEvents, $periodExpense, $firedOnce, [], $periodFiredExpenses[$p]);
            }

            $timeline[] = [
                'period' => $p,
                'label' => $this->periodLabel($p, $timeUnit),
                'income' => round($periodIncome, 2),
                'expense' => round($periodExpense, 2),
                'net' => round($periodIncome - $periodExpense, 2),
                'balance' => round($balance, 2),
            ];

            $logs = array_merge($logs, $periodEvents);
        }

        return [
            'periods' => $periods,
            'time_unit' => $timeUnit,
            'final_balance' => round($balance, 2),
            'timeline' => $timeline,
            'logs' => $logs,
        ];
    }

    // ── Helper: how many times a node fires this period ───────────────────

    /**
     * Returns the number of times a node fires in this period.
     *
     * When a frequency is finer-grained than the time unit (e.g. weekly
     * income in a monthly simulation), the node fires multiple times and
     * the caller should multiply the amount accordingly.
     *
     * Returns 0 when the node should not fire at all this period.
     */
    protected function fireCount(
        string $freq,
        int $period,
        int $startDelay,
        string $timeUnit,
        array $firedOnce,
        ?int $nodeId
    ): int {
        $adjusted = $period - $startDelay;

        if ($adjusted <= 0)
            return 0;

        return match ($freq) {
            'one-time' => isset($firedOnce[$nodeId]) ? 0 : 1,

            'weekly' => match ($timeUnit) {
                    'week' => 1,
                    'month' => 4,   // ~4 weeks per month
                    'year' => 52,  // 52 weeks per year
                    default => 1,
                },

            'monthly' => match ($timeUnit) {
                    'week' => ($adjusted % 4 === 0) ? 1 : 0,  // every 4th week
                    'month' => 1,
                    'year' => 12,  // 12 months per year
                    default => 1,
                },

            'yearly' => match ($timeUnit) {
                    'week' => ($adjusted >= 52 && $adjusted % 52 === 0) ? 1 : 0,
                    'month' => ($adjusted >= 12 && $adjusted % 12 === 0) ? 1 : 0,
                    'year' => 1,
                    default => 1,
                },

            default => 1,
        };
    }

    // ── Recursive money propagation ───────────────────────────────────────

    /**
     * Walk downstream from $srcId and propagate through rule nodes.
     * Outcome nodes are deducted only when reached via a connection.
     * Uses a visited set to prevent infinite loops if the graph has cycles.
     */
    protected function pushFlow(
        int $srcId,
        float $amount,
        int $period,
        string $timeUnit,
        float &$balance,
        array &$events,
        float &$periodExpense,
        array &$firedOnce,
        array $visited = [],
        array &$periodFiredExpenses = []
    ): void {
        if (isset($visited[$srcId]))
            return;
        $visited[$srcId] = true;

        $targets = $this->adjOut[$srcId] ?? [];
        if (empty($targets))
            return;

        foreach ($targets as $tgtId) {
            $tgt = $this->nodes[$tgtId] ?? null;
            if (!$tgt)
                continue;

            // Only skip if this is an outcome and already visited
            // Allow rules to flow through even if visited (they're just routers)
            if ($tgt->type === 'outcome' && isset($visited[$tgtId]))
                continue;

            switch ($tgt->type) {

                case 'outcome':
                    $data = $tgt->data_json ?? [];
                    $expFreq = $data['frequency'] ?? 'monthly';
                    $expAmt = (float) ($data['amount'] ?? 0);

                    if ($expAmt <= 0)
                        break;

                    $count = $this->fireCount($expFreq, $period, 0, $timeUnit, $firedOnce, $tgtId);

                    if ($count === 0)
                        break;

                    if ($expFreq === 'one-time')
                        $firedOnce[$tgtId] = true;

                    $total = $expAmt * $count;
                    
                    // Mark outcome as visited to prevent duplicate processing
                    $visited[$tgtId] = true;
                    
                    // Check if this specific outcome was fired in THIS period
                    $deductionKey = 'period_' . $period . '_expense_' . $tgtId;
                    if (!isset($periodFiredExpenses[$deductionKey])) {
                        $periodFiredExpenses[$deductionKey] = true;
                        // Only deduct once
                        $balance -= $total;
                        $periodExpense += $total;
                    }
                    
                    $events[] = [
                        'period' => $period,
                        'type' => 'expense',
                        'node' => $tgt->name,
                        'amount' => round($total, 2),
                        'balance' => round($balance, 2),
                    ];
                    break;

                case 'rule':
                    $data = $tgt->data_json ?? [];
                    $ruleType = $data['rule_type'] ?? 'split';
                    
                    $events[] = [
                        'period' => $period,
                        'type' => 'rule',
                        'node' => $tgt->name,
                        'amount' => ucfirst($ruleType),
                        'balance' => round($balance, 2),
                    ];

                    // Get all targets from this rule node
                    $ruleTargets = $this->adjOut[$tgtId] ?? [];
                    
                    if (empty($ruleTargets))
                        break;
                    
                    if ($ruleType === 'split') {
                        // Split by percentage
                        $this->processSplitRule($tgtId, $ruleTargets, $amount, $period, $timeUnit, $balance, $events, $periodExpense, $firedOnce, $visited, $periodFiredExpenses);
                    } else {
                        // Priority rule
                        $this->processPriorityRule($tgtId, $ruleTargets, $amount, $period, $timeUnit, $balance, $events, $periodExpense, $firedOnce, $visited, $periodFiredExpenses);
                    }
                    break;

                case 'trigger_creator':
                    $events[] = [
                        'period' => $period,
                        'type' => 'trigger',
                        'node' => $tgt->name,
                        'amount' => round($amount, 2),
                        'balance' => round($balance, 2),
                    ];
                    break;
            }
        }
    }
    
    /**
     * Process split rule: distribute income by percentage to connected targets
     * Logs show the ALLOCATED amount (split percentage)
     * Balance deducts FULL expense amount each time
     */
    protected function processSplitRule(
        int $ruleId,
        array $targets,
        float $amount,
        int $period,
        string $timeUnit,
        float &$balance,
        array &$events,
        float &$periodExpense,
        array &$firedOnce,
        array &$visited,
        array &$periodFiredExpenses = []
    ): void {
        foreach ($targets as $targetId) {
            $edgeKey = $ruleId . '->' . $targetId;
            $edgeData = $this->edgeData[$edgeKey] ?? [];
            $percentage = (float) ($edgeData['percentage'] ?? 100);
            
            // Amount allocated to this target based on percentage
            $allocatedAmount = $amount * ($percentage / 100);
            
            $target = $this->nodes[$targetId] ?? null;
            if (!$target)
                continue;
            
            if ($target->type === 'outcome') {
                // Mark as visited to prevent duplicate processing
                if (isset($visited[$targetId]))
                    continue;
                $visited[$targetId] = true;
                
                // Get the full expense amount
                $fullExpenseAmount = $this->getOutcomeAmount($targetId, $period, $timeUnit, $firedOnce);
                
                if ($fullExpenseAmount > 0) {
                    // Check if already deducted in this period
                    $deductionKey = 'period_' . $period . '_expense_' . $targetId;
                    if (!isset($periodFiredExpenses[$deductionKey])) {
                        $periodFiredExpenses[$deductionKey] = true;
                        
                        // Deduct only the allocated amount
                        $balance -= $allocatedAmount;
                        $periodExpense += $allocatedAmount;
                        
                        // Log the allocated expense amount (purple)
                        $events[] = [
                            'period' => $period,
                            'type' => 'expense',
                            'node' => $target->name,
                            'amount' => round($allocatedAmount, 2),
                            'balance' => round($balance, 2),
                        ];
                        
                        // Calculate remaining debt or surplus
                        $remaining = $fullExpenseAmount - $allocatedAmount;
                        
                        if ($remaining > 0) {
                            // Debt: expense is more than allocated
                            // Log the debt amount (red) with same category
                            $balance -= $remaining;
                            $periodExpense += $remaining;
                            $events[] = [
                                'period' => $period,
                                'type' => 'expense',
                                'node' => $target->name,
                                'amount' => round($remaining, 2),
                                'balance' => round($balance, 2),
                                'is_debt' => true,
                            ];
                        } elseif ($remaining < 0) {
                            // Surplus: allocated is more than expense
                            // Just log as income with same category (green color)
                            $surplus = abs($remaining);
                            $balance += $surplus;
                            $events[] = [
                                'period' => $period,
                                'type' => 'income',
                                'node' => $target->name,
                                'amount' => round($surplus, 2),
                                'balance' => round($balance, 2),
                            ];
                        }
                    }
                    
                    if (!isset($firedOnce[$targetId])) {
                        $data = $target->data_json ?? [];
                        $expFreq = $data['frequency'] ?? 'monthly';
                        if ($expFreq === 'one-time') {
                            $firedOnce[$targetId] = true;
                        }
                    }
                }
            } elseif ($target->type === 'rule') {
                // Rule-to-rule: DON'T mark as visited, allow it to process normally
                // Pass to the downstream rule with the allocated amount
                $this->pushFlow($targetId, $allocatedAmount, $period, $timeUnit, $balance, $events, $periodExpense, $firedOnce, $visited, $periodFiredExpenses);
            }
        }
    }
    
    /**
     * Process priority rule: compare expense amounts and prioritize higher expenses
     * Deducts FULL expense amounts in priority order (can go negative)
     */
    protected function processPriorityRule(
        int $ruleId,
        array $targets,
        float $amount,
        int $period,
        string $timeUnit,
        float &$balance,
        array &$events,
        float &$periodExpense,
        array &$firedOnce,
        array &$visited,
        array &$periodFiredExpenses = []
    ): void {
        // Calculate priority for each target
        $priorities = [];
        
        foreach ($targets as $targetId) {
            if (isset($visited[$targetId]))
                continue;
                
            $target = $this->nodes[$targetId] ?? null;
            if (!$target)
                continue;
            
            if ($target->type === 'outcome') {
                // Direct outcome - use its expense amount
                $expenseAmount = $this->getOutcomeAmount($targetId, $period, $timeUnit, $firedOnce);
                $priorities[] = [
                    'id' => $targetId,
                    'amount' => $expenseAmount,
                    'type' => 'outcome'
                ];
            } elseif ($target->type === 'rule') {
                // Rule (e.g., split) - calculate total expenses from all its outcomes
                $totalExpenses = $this->calculateBranchExpenses($targetId, $period, $timeUnit, $firedOnce);
                $priorities[] = [
                    'id' => $targetId,
                    'amount' => $totalExpenses,
                    'type' => 'rule'
                ];
            }
        }
        
        // Sort by amount descending (highest priority first)
        usort($priorities, fn($a, $b) => $b['amount'] <=> $a['amount']);
        
        // Process in priority order - all expenses are deducted regardless of available funds
        foreach ($priorities as $priority) {
            $targetId = $priority['id'];
            if (isset($visited[$targetId]))
                continue;
            $visited[$targetId] = true;
            
            $target = $this->nodes[$targetId] ?? null;
            
            if (!$target)
                continue;
            
            if ($target->type === 'outcome') {
                $expenseAmount = $this->getOutcomeAmount($targetId, $period, $timeUnit, $firedOnce);
                
                if ($expenseAmount > 0) {
                    // Check if already deducted in this period
                    $deductionKey = 'period_' . $period . '_expense_' . $targetId;
                    if (!isset($periodFiredExpenses[$deductionKey])) {
                        $periodFiredExpenses[$deductionKey] = true;
                        $balance -= $expenseAmount;
                        $periodExpense += $expenseAmount;
                    }
                    
                    $events[] = [
                        'period' => $period,
                        'type' => 'expense',
                        'node' => $target->name,
                        'amount' => round($expenseAmount, 2),
                        'balance' => round($balance, 2),
                    ];
                    
                    if (!isset($firedOnce[$targetId])) {
                        $data = $target->data_json ?? [];
                        $expFreq = $data['frequency'] ?? 'monthly';
                        if ($expFreq === 'one-time') {
                            $firedOnce[$targetId] = true;
                        }
                    }
                }
            } elseif ($target->type === 'rule') {
                // Pass to the rule
                $this->pushFlow($targetId, $amount, $period, $timeUnit, $balance, $events, $periodExpense, $firedOnce, $visited, $periodFiredExpenses);
            }
        }
    }
    
    /**
     * Calculate total expenses from all outcomes in a branch (for priority comparison)
     */
    protected function calculateBranchExpenses(
        int $ruleId,
        int $period,
        string $timeUnit,
        array &$firedOnce
    ): float {
        $total = 0.0;
        $targets = $this->adjOut[$ruleId] ?? [];
        
        foreach ($targets as $targetId) {
            $target = $this->nodes[$targetId] ?? null;
            if (!$target)
                continue;
            
            if ($target->type === 'outcome') {
                $total += $this->getOutcomeAmount($targetId, $period, $timeUnit, $firedOnce);
            } elseif ($target->type === 'rule') {
                // Recursively calculate expenses from nested rules
                $total += $this->calculateBranchExpenses($targetId, $period, $timeUnit, $firedOnce);
            }
        }
        
        return $total;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Get the full expense amount for an outcome node (without deducting yet).
     */
    protected function getOutcomeAmount(
        int $outcomeId,
        int $period,
        string $timeUnit,
        array &$firedOnce
    ): float {
        $outcome = $this->nodes[$outcomeId] ?? null;
        if (!$outcome || $outcome->type !== 'outcome') {
            return 0.0;
        }

        $data = $outcome->data_json ?? [];
        $expFreq = $data['frequency'] ?? 'monthly';
        $expAmt = (float) ($data['amount'] ?? 0);

        if ($expAmt <= 0) {
            return 0.0;
        }

        $count = $this->fireCount($expFreq, $period, 0, $timeUnit, $firedOnce, $outcomeId);

        if ($count === 0) {
            return 0.0;
        }

        return $expAmt * $count;
    }

    protected function periodLabel(int $p, string $unit): string
    {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return match ($unit) {
            'month' => $months[($p - 1) % 12] . (intdiv($p - 1, 12) > 0 ? ' +' . intdiv($p - 1, 12) . 'y' : ''),
            'week' => 'Week ' . $p,
            'year' => 'Year ' . $p,
            default => 'P' . $p,
        };
    }
}