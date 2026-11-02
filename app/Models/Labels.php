<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Labels extends Model
{
    public $timestamps = false;

    protected $fillable = ['task_id', 'name', 'color', 'color_name'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
