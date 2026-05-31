<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionLog extends Model
{
    protected $fillable = ['simulation_id', 'trigger_creator_id', 'source_rule_id', 'target_outcome_id', 'amount', 'running_balance', 'meta_json'];
    protected $casts = ['meta_json' => 'array'];
    public function simulation() { return $this->belongsTo(Simulation::class); }
    public function triggerCreator() { return $this->belongsTo(FlowObject::class, 'trigger_creator_id'); }
    public function sourceRule() { return $this->belongsTo(FlowObject::class, 'source_rule_id'); }
    public function targetOutcome() { return $this->belongsTo(FlowObject::class, 'target_outcome_id'); }
}
