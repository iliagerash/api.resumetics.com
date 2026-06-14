<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaticRoute extends Model
{
    protected $fillable = [
        'recipient',
        'forward_to',
    ];
}
