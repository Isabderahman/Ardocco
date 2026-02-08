<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'email',
        'password',
        'role',
        'first_name',
        'last_name',
        'phone',
        'company_name',
        'address',
        'city',
        'cin',
        'is_verified',
        'is_active',
        'account_status',
        'contract_token',
        'contract_signed_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'last_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'contract_token',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'last_login' => 'datetime',
        'email_verified_at' => 'datetime',
        'contract_signed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relations
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function ownedListings()
    {
        return $this->hasMany(Listing::class, 'owner_id');
    }

    public function managedListings()
    {
        return $this->hasMany(Listing::class, 'agent_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function accessRequests()
    {
        return $this->hasMany(AccessRequest::class, 'promoteur_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    // Helpers
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    public function isVendeur(): bool
    {
        return $this->role === 'vendeur';
    }

    public function isAcheteur(): bool
    {
        return $this->role === 'acheteur';
    }

    public function isExpert(): bool
    {
        return $this->role === 'expert';
    }

    public function isPromoteur(): bool
    {
        return $this->role === 'promoteur';
    }

    public function canAccessFullListingDetails(): bool
    {
        // Acheteur, Vendeur, Agent, Expert, and Admin can access full details
        return in_array($this->role, ['acheteur', 'vendeur', 'agent', 'expert', 'admin']);
    }

    public function hasActiveContract(): bool
    {
        return $this->contracts()
            ->where('status', 'signed')
            ->where(function($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    // Account status helpers
    public function isPendingContract(): bool
    {
        return $this->account_status === 'pending_contract';
    }

    public function isPendingApproval(): bool
    {
        return $this->account_status === 'pending_approval';
    }

    public function isAccountActive(): bool
    {
        return $this->account_status === 'active' && $this->is_active;
    }

    public function isRejected(): bool
    {
        return $this->account_status === 'rejected';
    }

    public function requiresApproval(): bool
    {
        return in_array($this->role, ['acheteur', 'vendeur']);
    }

    // Scopes
    public function scopePendingApproval($query)
    {
        return $query->where('account_status', 'pending_approval');
    }

    public function scopePendingContract($query)
    {
        return $query->where('account_status', 'pending_contract');
    }

    public function scopeAccountActive($query)
    {
        return $query->where('account_status', 'active')->where('is_active', true);
    }
}
