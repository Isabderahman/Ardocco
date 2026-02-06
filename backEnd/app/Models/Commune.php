<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commune extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'province_id',
        'name_fr',
        'name_ar',
        'type',
        'code_postal',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function listings()
    {
        return $this->hasMany(Listing::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->name_fr}, {$this->province->name_fr}";
    }
}
