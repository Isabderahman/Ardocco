<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FicheJuridique extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'fiches_juridiques';

    protected $fillable = [
        'listing_id',
        'statut_foncier',
        'numero_titre',
        'proprietaire_legal',
        'servitudes',
        'restrictions',
        'litiges',
        'documents_manquants',
        'points_vigilance',
        'conformite_urbanisme',
        'generated_by_ai',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'servitudes' => 'array',
        'restrictions' => 'array',
        'litiges' => 'array',
        'documents_manquants' => 'array',
        'points_vigilance' => 'array',
        'conformite_urbanisme' => 'boolean',
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

    public function hasLegalIssues(): bool
    {
        return !empty($this->litiges) || $this->conformite_urbanisme === false;
    }
}
