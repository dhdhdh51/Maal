<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorageConfiguration extends Model
{
    protected $fillable = [
        'name', 'provider', 'disk', 'bucket', 'region', 'endpoint', 'cdn_url',
        'access_key', 'secret_key', 'use_path_style', 'is_active',
        'bytes_used', 'bandwidth_used', 'cost_per_gb', 'usage_synced_at',
    ];

    protected $hidden = ['access_key', 'secret_key'];

    protected function casts(): array
    {
        return [
            'access_key' => 'encrypted',
            'secret_key' => 'encrypted',
            'use_path_style' => 'boolean',
            'is_active' => 'boolean',
            'cost_per_gb' => 'decimal:4',
            'usage_synced_at' => 'datetime',
        ];
    }

    public function active(): bool
    {
        return $this->is_active;
    }

    public function estimatedMonthlyCost(): float
    {
        $gb = $this->bytes_used / (1024 ** 3);

        return round($gb * (float) $this->cost_per_gb, 2);
    }
}
