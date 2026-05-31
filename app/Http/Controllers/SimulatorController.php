<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Simulation;

use App\Models\Project;
use App\Models\Workflow;

class SimulatorController extends Controller
{
    public function index()
    {
        // Load the complex workflow that was seeded
        $workflow = Workflow::where('name', 'Complex Financial Flow')->first();
        
        // If it doesn't exist, create a default one
        if (!$workflow) {
            $project = Project::firstOrCreate(
                ['name' => 'Personal Finance Simulation'],
                ['user_id' => null]
            );

            $workflow = Workflow::firstOrCreate(
                ['project_id' => $project->id, 'name' => 'Complex Financial Flow'],
                ['viewport_json' => ['x' => 0, 'y' => 0, 'zoom' => 1]]
            );
        }

        // Load categories for income/outcome node creation
        $categories = \App\Models\Category::all();

        return view('simulator', compact('workflow', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
        ]);

        Simulation::create($request->all());

        return redirect()->route('Simulator.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Simulation $Simulation)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
        ]);

        $Simulation->update($request->all());

        return redirect()->route('Simulator.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Simulation $Simulation)
    {
        $Simulation->delete();
        return redirect()->route('Simulator.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
