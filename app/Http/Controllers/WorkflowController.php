<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Workflow;
use App\Models\FlowObject;
use App\Models\ObjectConnection;
use App\Services\SimulationEngine;

class WorkflowController extends Controller
{
    public function state(Workflow $workflow)
    {
        $workflow->load('objects', 'connections');
        return response()->json($workflow);
    }

    public function sync(Request $request, Workflow $workflow)
    {
        $nodes = $request->input('nodes', []);
        $edges = $request->input('edges', []);

        // Validate no rule loops
        if ($this->hasRuleLoop($nodes, $edges)) {
            return response()->json(['status' => 'error', 'message' => 'Rules cannot form loops'], 400);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($workflow, $nodes, $edges) {
            $workflow->connections()->delete();
            $workflow->objects()->delete();

            $idMap = [];

            foreach ($nodes as $nd) {
                $obj = $workflow->objects()->create([
                    'type'       => $nd['type'],
                    'name'       => $nd['name'] ?? $nd['type'],
                    'data_json'  => $nd['data_json'] ?? [],
                    'position_x' => $nd['position_x'] ?? 0,
                    'position_y' => $nd['position_y'] ?? 0,
                ]);
                $idMap[$nd['id']] = $obj->id;
            }

            foreach ($edges as $ed) {
                $srcId = $idMap[$ed['source']] ?? null;
                $tgtId = $idMap[$ed['target']] ?? null;
                if (!$srcId || !$tgtId) continue;
                $workflow->connections()->create([
                    'source_object_id' => $srcId,
                    'target_object_id' => $tgtId,
                    'edge_data'        => [
                        'source_output' => $ed['source_port'] ?? 'out', 
                        'target_input' => $ed['target_port'] ?? 'in',
                        'percentage' => $ed['percentage'] ?? 100,
                    ],
                ]);
            }
        });

        return response()->json(['status' => 'success']);
    }

    /**
     * Detect any cycle in the full graph using DFS.
     * A cycle anywhere (not just rule→rule) is rejected so the
     * simulation engine never enters an infinite loop.
     */
    private function hasRuleLoop($nodes, $edges): bool
    {
        // Build adjacency list keyed by node id
        $adj = [];
        foreach ($nodes as $node) {
            $adj[$node['id']] = [];
        }
        foreach ($edges as $edge) {
            $adj[$edge['source']][] = $edge['target'];
        }

        $visited = [];  // 0 = unvisited, 1 = in-stack, 2 = done

        // DFS from every node
        foreach (array_keys($adj) as $startId) {
            if (($visited[$startId] ?? 0) === 0) {
                if ($this->dfsCycleCheck($startId, $adj, $visited)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function dfsCycleCheck(string $nodeId, array &$adj, array &$visited): bool
    {
        $visited[$nodeId] = 1; // mark as in-stack

        foreach ($adj[$nodeId] ?? [] as $neighbour) {
            $state = $visited[$neighbour] ?? 0;
            if ($state === 1) return true;  // back-edge → cycle
            if ($state === 0 && $this->dfsCycleCheck($neighbour, $adj, $visited)) {
                return true;
            }
        }

        $visited[$nodeId] = 2; // done
        return false;
    }

    public function simulate(Request $request, Workflow $workflow)
    {
        $periods  = (int) $request->input('periods', 12);
        $timeUnit = $request->input('time_unit', 'month');

        $periods  = max(1, min($periods, 120));

        $engine = new SimulationEngine($workflow);
        $result = $engine->run($periods, $timeUnit);

        // DEBUG: Log first period logs
        if ($request->input('debug')) {
            $firstPeriodLogs = array_filter($result['logs'], fn($log) => $log['period'] === 1);
            \Log::info('First period logs:', ['logs' => $firstPeriodLogs]);
        }

        return response()->json($result);
    }
}
