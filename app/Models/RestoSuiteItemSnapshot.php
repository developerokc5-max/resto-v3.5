<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestoSuiteItemSnapshot extends Model
{
    protected $table = 'restosuite_item_snapshots';

    protected $fillable = [
        'run_id',
        'shop_id',
        'shop_name',
        'brand_name',
        'org_code',
        'item_id',
        'item_name',
        'item_code',
        'status',
        'price',
        'raw_json',
        'fingerprint',
    ];

    protected $casts = [
        'raw_json' => 'array',
        'price' => 'float',
    ];
}
