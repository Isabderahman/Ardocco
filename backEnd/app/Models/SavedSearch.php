<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedSearch extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'filters',
        'notify_new_listings',
    ];

    protected $casts = [
        'filters' => 'array',
        'notify_new_listings' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
