<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VillageProfile extends Model
{
    use HasFactory;

    protected $table = 'village_profiles';

    protected $fillable = [
        'village_id',
        'deskripsi',
        'sejarah',
        'visi',
        'misi',
        'foto_url',
        'area',
        'population',
        'households',
        'male_population',
        'female_population',
        'population_density',
        'address',
        'phone',
        'email',
        'website',
        'logo_url',
        'is_featured',
        'thumbnail_url',
        'updated_by',
    ];

    protected $casts = [
        'area' => 'float',
        'population' => 'integer',
        'households' => 'integer',
        'male_population' => 'integer',
        'female_population' => 'integer',
        'population_density' => 'float',
        'is_featured' => 'boolean',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'village_id');
    }
}
