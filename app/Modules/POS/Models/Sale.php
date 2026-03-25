<?php

namespace App\Modules\POS\Models;

use App\Traits\BelongsToWorkspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory, BelongsToWorkspace;

    protected $fillable = [
        'workspace_id',
        'invoice_number',
        'user_id',
        'total_amount',
        'payment_method',
        'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'sold_at' => 'datetime',
        ];
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
