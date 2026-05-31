<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    protected $fillable = ['project_id', 'name', 'viewport_json'];
    protected $casts = ['viewport_json' => 'array'];
    public function objects() { return $this->hasMany(FlowObject::class); }
    public function connections() { return $this->hasMany(ObjectConnection::class); }
    public function simulations() { return $this->hasMany(Simulation::class); }
}
