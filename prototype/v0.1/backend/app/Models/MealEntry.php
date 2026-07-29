<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MealEntry extends Model
{
    protected $fillable = [
        'date',
        'breakfast',
        'lunch',
        'snack',
        'dinner',
        'notes',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];
}
