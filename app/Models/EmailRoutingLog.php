<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EmailRoutingLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'resend_email_id',
        'recipient',
        'site_id',
        'user_id',
        'resolved_email',
        'status',
        'failure_reason',
        'received_at',
        'forwarded_at',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'user_id' => 'integer',
        'received_at' => 'datetime',
        'forwarded_at' => 'datetime',
    ];
}
