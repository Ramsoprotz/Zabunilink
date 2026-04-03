<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tier',
        'description',
        'monthly_price',
        'quarterly_price',
        'semi_annual_price',
        'annual_price',
        'features',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'monthly_price' => 'decimal:2',
            'quarterly_price' => 'decimal:2',
            'semi_annual_price' => 'decimal:2',
            'annual_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function getPriceForCycle(string $cycle): float
    {
        return match ($cycle) {
            'monthly' => $this->monthly_price,
            'quarterly' => $this->quarterly_price,
            'semi_annual' => $this->semi_annual_price,
            'annual' => $this->annual_price,
            default => $this->monthly_price,
        };
    }
}
