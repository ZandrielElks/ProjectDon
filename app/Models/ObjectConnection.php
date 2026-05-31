<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObjectConnection extends Model
{
    protected $fillable = ['workflow_id', 'source_object_id', 'target_object_id', 'edge_data'];
    protected $casts = ['edge_data' => 'array'];
    public function workflow() { return $this->belongsTo(Workflow::class); }
    public function source() { return $this->belongsTo(FlowObject::class, 'source_object_id'); }
    public function target() { return $this->belongsTo(FlowObject::class, 'target_object_id'); }
}
