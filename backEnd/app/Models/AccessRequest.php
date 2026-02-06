<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessRequest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'promoteur_id',
        'listing_id',
        'status',
        'message',
        'approved_by',
        'approved_at',
        'expires_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function promoteur()
    {
        return $this->belongsTo(User::class, 'promoteur_id');
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'approved')
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function isActive(): bool
    {
        return $this->status === 'approved' &&
               (!$this->expires_at || $this->expires_at->isFuture());
    }
}
