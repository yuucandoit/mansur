<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Email_Template extends Model
{
    protected $table = 'email_templates';

    public $timestamps = false;

    protected $fillable = ['name', 'details', 'slug', 'language', 'html'];
}
