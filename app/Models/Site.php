<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $fillable = [
        'site_id',
        'api_url',
        'api_key',
        'response_path',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'site_id' => 'integer',
    ];
}
