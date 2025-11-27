<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = ['name', 'type', 'value', 'code', 'start_date', 'end_date', 'is_active', 'conditions'];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'is_active' => 'boolean',
            'conditions' => 'array',
        ];
    }
}
