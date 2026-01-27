<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $table = 'shops';

    protected $fillable = [
        'shop_id',
        'shop_name',
        'organization_name',
        'has_items',
        'last_synced_at',
    ];

    protected $casts = [
        'has_items' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get all items for this shop
     */
    public function items()
    {
        return $this->hasMany(Item::class, 'shop_name', 'shop_name');
    }

    /**
     * Scope to get shops with items
     */
    public function scopeWithItems($query)
    {
        return $query->where('has_items', true);
    }

    /**
     * Scope to get shops without items
     */
    public function scopeWithoutItems($query)
    {
        return $query->where('has_items', false);
    }
}
