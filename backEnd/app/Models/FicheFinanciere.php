<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FicheFinanciere extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'fiches_financieres';

    protected $fillable = [
        'listing_id',
        'prix_marche_estime',
        'comparables',
        'hypotheses_valorisation',
        'couts_viabilisation',
        'couts_amenagement',
        'taxes_estimees',
        'rentabilite_potentielle',
        'rentabilite',
        'expert_notes',
        'conclusion',
        'rating',
        'attached_documents',
        'generated_by_ai',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'prix_marche_estime' => 'decimal:2',
        'couts_viabilisation' => 'decimal:2',
        'couts_amenagement' => 'decimal:2',
        'rentabilite' => 'decimal:2',
        'comparables' => 'array',
        'hypotheses_valorisation' => 'array',
        'taxes_estimees' => 'array',
        'rentabilite_potentielle' => 'array',
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
}
