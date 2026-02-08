<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FicheTechnique extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'fiches_techniques';

    protected $fillable = [
        'listing_id',
        'accessibilite',
        'voisinage',
        'contraintes_techniques',
        'opportunites',
        'equipements',
        'photos_analyse',
        'expert_notes',
        'conclusion',
        'rating',
        'attached_documents',
        'generated_by_ai',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'accessibilite' => 'array',
        'voisinage' => 'array',
        'contraintes_techniques' => 'array',
        'opportunites' => 'array',
        'equipements' => 'array',
        'photos_analyse' => 'array',
        'attached_documents' => 'array',
        'rating' => 'integer',
        'generated_by_ai' => 'boolean',
        'validated_at' => 'datetime',
    ];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function scopeValidated($query)
    {
        return $query->whereNotNull('validated_at');
    }

    public function isValidated(): bool
    {
        return !is_null($this->validated_at);
    }
}
