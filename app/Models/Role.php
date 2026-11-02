<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'create_workspace', 'create_project', 'access'];

    protected $casts = [
        'create_workspace' => 'boolean',
        'create_project'   => 'boolean',
        'access'           => 'array',
    ];
}
