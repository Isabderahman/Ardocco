<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'contract_type',
        'status',
        'signed_at',
        'expires_at',
        'document_url',
        'terms',
        'signature',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'expires_at' => 'datetime',
        'terms' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'signed')
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeSigned($query)
    {
        return $query->where('status', 'signed');
    }

    public function isActive(): bool
    {
        return $this->status === 'signed' &&
               (!$this->expires_at || $this->expires_at->isFuture());
    }
}
