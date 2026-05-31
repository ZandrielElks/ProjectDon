<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimulationPeriod extends Model
{
    protected $fillable = ['simulation_id', 'period_index', 'expense_status_json', 'snapshots_json'];
    protected $casts = ['expense_status_json' => 'array', 'snapshots_json' => 'array'];
    public function simulation() { return $this->belongsTo(Simulation::class); }
}
