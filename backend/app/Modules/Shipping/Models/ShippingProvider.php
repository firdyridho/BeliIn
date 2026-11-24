<?php

namespace App\Modules\Shipping\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'provider_type',
        'is_active',
        'config_reference',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'config_reference' => 'array',
        ];
    }

    public function services(): HasMany
    {
        return $this->hasMany(ShippingService::class);
    }
}
