<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Labels extends Model
{
    protected $fillable = ['task_id', 'name', 'color'];

    public function task(): BelongsTo { return $this->belongsTo(Task::class); }
}
