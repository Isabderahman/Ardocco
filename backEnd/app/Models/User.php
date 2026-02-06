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
        'is_verified',
        'is_active',
        'last_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'last_login' => 'datetime',
        'email_verified_at' => 'datetime',
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

    public function isPromoteur(): bool
    {
        return $this->role === 'promoteur';
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
}
