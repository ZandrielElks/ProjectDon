<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObjectTemplate extends Model
{
    protected $fillable = ['type', 'name', 'data_json'];
    protected $casts = ['data_json' => 'array'];
}
