<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlowObject extends Model
{
    protected $fillable = ['workflow_id', 'type', 'name', 'data_json', 'position_x', 'position_y'];
    protected $casts = ['data_json' => 'array'];
    public function workflow() { return $this->belongsTo(Workflow::class); }
    public function sourceConnections() { return $this->hasMany(ObjectConnection::class, 'source_object_id'); }
    public function targetConnections() { return $this->hasMany(ObjectConnection::class, 'target_object_id'); }
}
