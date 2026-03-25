<?php

namespace App\Modules\POS\Models;

use App\Traits\BelongsToWorkspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrisPayment extends Model
{
    use BelongsToWorkspace;

    protected $fillable = [
        'workspace_id',
        'order_id',
        'user_id',
        'sale_id',
        'gross_amount',
        'transaction_status',
        'fraud_status',
        'payment_type',
        'qr_url',
        'expires_at',
        'is_paid',
        'paid_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'is_paid' => 'boolean',
            'paid_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
