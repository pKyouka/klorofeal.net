<?php

namespace App\Modules\Products\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalBarcode extends Model
{
    use HasFactory;

    protected $fillable = [
        'barcode',
        'product_name',
        'brand',
        'country_code',
        'source_primary',
        'sources',
        'source_meta',
        'first_seen_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'sources' => 'array',
            'source_meta' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }
}
