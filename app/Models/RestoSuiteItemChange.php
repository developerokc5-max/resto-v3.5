<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestoSuiteItemChange extends Model
{
    protected $table = 'restosuite_item_changes';

    protected $fillable = [
        'run_id',
        'shop_id',
        'shop_name',
        'item_id',
        'item_name',
        'change_type',
        'before_json',
        'after_json',
    ];

    protected $casts = [
        'before_json' => 'array',
        'after_json' => 'array',
    ];
}
