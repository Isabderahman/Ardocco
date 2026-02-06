<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name_fr',
        'name_ar',
        'code',
    ];

    public function provinces()
    {
        return $this->hasMany(Province::class);
    }
}
