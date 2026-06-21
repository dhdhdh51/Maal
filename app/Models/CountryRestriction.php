<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountryRestriction extends Model
{
    protected $fillable = ['country_code', 'country_name', 'mode', 'is_active', 'reason'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * Determine whether a given country code is allowed to access the platform.
     * - If any "allow" rules exist, only those countries are permitted.
     * - Otherwise countries present in "block" rules are denied.
     */
    public static function isCountryAllowed(?string $code): bool
    {
        if (empty($code)) {
            return true;
        }

        $code = strtoupper($code);
        $rules = static::where('is_active', true)->get();

        $allowList = $rules->where('mode', 'allow');
        if ($allowList->isNotEmpty()) {
            return $allowList->contains(fn ($r) => strtoupper($r->country_code) === $code);
        }

        $blockList = $rules->where('mode', 'block');

        return ! $blockList->contains(fn ($r) => strtoupper($r->country_code) === $code);
    }
}
