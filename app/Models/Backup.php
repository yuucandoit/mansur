<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    protected $fillable = ['name', 'details'];

    protected $casts = [
        'details' => 'array',
    ];
}
