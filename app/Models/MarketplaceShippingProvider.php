<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceShippingProvider extends Model
{
    protected $fillable = ['name', 'is_active', 'notes'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(MarketplaceShippingRate::class)->orderBy('country_code')->orderBy('print_size');
    }

    public function tokenCostFor(string $countryCode, string $printSize): ?int
    {
        $rates = $this->relationLoaded('rates') ? $this->rates : $this->rates()->get();
        $countryCode = strtoupper($countryCode);

        foreach ([[$countryCode, $printSize], [$countryCode, null], [null, $printSize], [null, null]] as [$country, $size]) {
            $rate = $rates->first(fn (MarketplaceShippingRate $candidate): bool => $candidate->country_code === $country && $candidate->print_size === $size);
            if ($rate) {
                return $rate->token_cost;
            }
        }

        return null;
    }
}
