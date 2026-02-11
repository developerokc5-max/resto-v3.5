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
        'duration_seconds',
        'items_processed',
        'items_added',
        'items_updated',
        'error_message',
        'log_file_path',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
        'duration_seconds' => 'integer',
        'items_processed' => 'integer',
        'items_added' => 'integer',
        'items_updated' => 'integer',
    ];
}
