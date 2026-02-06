<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Listing extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'agent_id',
        'reference',
        'title',
        'description',
        'commune_id',
        'quartier',
        'address',
        'latitude',
        'longitude',
        'superficie',
        'prix_demande',
        'prix_estime',
        'prix_par_m2',
        'type_terrain',
        'status',
        'titre_foncier',
        'forme_terrain',
        'topographie',
        'viabilisation',
        'zonage',
        'coefficient_occupation',
        'hauteur_max',
        'is_exclusive',
        'is_urgent',
        'visibility',
        'views_count',
        'submitted_at',
        'validated_at',
        'published_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'superficie' => 'decimal:2',
        'prix_demande' => 'decimal:2',
        'prix_estime' => 'decimal:2',
        'prix_par_m2' => 'decimal:2',
        'coefficient_occupation' => 'decimal:2',
        'viabilisation' => 'array',
        'is_exclusive' => 'boolean',
        'is_urgent' => 'boolean',
        'submitted_at' => 'datetime',
        'validated_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($listing) {
            if (!$listing->reference) {
                $listing->reference = 'ARD-' . strtoupper(uniqid());
            }
        });
    }

    // Relations
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function commune()
    {
        return $this->belongsTo(Commune::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function ficheTechnique()
    {
        return $this->hasOne(FicheTechnique::class);
    }

    public function ficheFinanciere()
    {
        return $this->hasOne(FicheFinanciere::class);
    }

    public function ficheJuridique()
    {
        return $this->hasOne(FicheJuridique::class);
    }

    public function accessRequests()
    {
        return $this->hasMany(AccessRequest::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'publie');
    }

    public function scopeAvailable($query)
    {
        return $query->whereIn('status', ['publie', 'valide']);
    }

    public function scopeInRegion($query, $regionId)
    {
        return $query->whereHas('commune.province', function($q) use ($regionId) {
            $q->where('region_id', $regionId);
        });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type_terrain', $type);
    }

    public function scopePriceRange($query, $min, $max)
    {
        return $query->whereBetween('prix_demande', [$min, $max]);
    }

    public function scopeSuperficieRange($query, $min, $max)
    {
        return $query->whereBetween('superficie', [$min, $max]);
    }

    public function scopeNearby($query, $lat, $lng, $radiusKm = 10)
    {
        $earthRadius = 6371; // km

        return $query->selectRaw("
            *,
            ( {$earthRadius} * acos(
                cos( radians(?) ) *
                cos( radians( latitude ) ) *
                cos( radians( longitude ) - radians(?) ) +
                sin( radians(?) ) *
                sin( radians( latitude ) )
            ) ) AS distance
        ", [$lat, $lng, $lat])
        ->having('distance', '<', $radiusKm)
        ->orderBy('distance');
    }

    // Helpers
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function canBeEditedBy(User $user): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->isAgent()) return true;
        if ($user->id === $this->owner_id && in_array($this->status, ['brouillon', 'refuse', 'en_revision'])) {
            return true;
        }
        return false;
    }

    public function isPublished(): bool
    {
        return $this->status === 'publie';
    }

    public function hasCompleteData(): bool
    {
        return $this->ficheTechnique()->exists()
            && $this->ficheFinanciere()->exists()
            && $this->ficheJuridique()->exists();
    }
}
