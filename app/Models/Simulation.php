<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Simulation extends Model
{
    protected $fillable = ['name', 'type'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
