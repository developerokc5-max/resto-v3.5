<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScraperLog extends Model
{
    protected $table = 'scraper_logs';

    protected $fillable = [
        'scraper_name',
        'executed_at',
        'status',
        'items_processed',
        'items_updated',
        'log_message',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
        'items_processed' => 'integer',
        'items_updated' => 'integer',
    ];
}
