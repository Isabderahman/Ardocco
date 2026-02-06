<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'region_id',
        'name_fr',
        'name_ar',
        'code',
        'latitude',
        'longitude',
        'properties',
        'bbox',
        'geometry',
    ];

    protected $casts = [
        'properties' => 'array',
        'bbox' => 'array',
        'geometry' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function communes()
    {
        return $this->hasMany(Commune::class);
    }
}
