<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    protected $fillable = ['name', 'amount', 'due_date', 'status', 'is_recurring', 'frequency', 'category_id'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
