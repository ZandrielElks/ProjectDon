<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = ['amount', 'type', 'category_id', 'description', 'date', 'simulation_group'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
