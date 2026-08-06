<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KernelMasterData extends Model
{
    protected $table = 'kernel_master_data';

    protected $fillable = [
        'office',
        'kode',
        'nama_sample',
        'limit_operator',
        'limit_value',
        'is_active',
    ];

    protected $casts = [
        'limit_value' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Returns ['KODE' => 'Nama Sample', ...] for dropdowns.
     */
    public static function getKodeDropdown(?string $office = null): array
    {
        $officeCode = strtoupper(trim((string) ($office ?? auth()->user()?->office ?? '')));

        return static::query()
            ->where('is_active', true)
            ->when($officeCode !== '' && $officeCode !== 'ALL', fn($q) => $q->where('office', $officeCode))
            ->orderBy('kode')
            ->pluck('nama_sample', 'kode')
            ->toArray();
    }

    /**
     * Formatted limit string, e.g. "≤ 1.45" or "> 98.00".
     */
    public function getLimitLabelAttribute(): string
    {
        $symbol = match ($this->limit_operator) {
            'lt' => '<',
            'ge' => '≥',
            'gt' => '>',
            default => '≤',
        };
        return $symbol . ' ' . number_format((float) $this->limit_value, 2);
    }

    /**
     * Check if a given percentage value exceeds (fails) the limit.
     * @param float $valuePercent  Value as percentage, e.g. 1.5 means 1.5 %
     */
    public function isExceeded(float $valuePercent): bool
    {
        return match ($this->limit_operator) {
            'lt' => $valuePercent >= (float) $this->limit_value,
            'ge' => $valuePercent < (float) $this->limit_value,
            'gt' => $valuePercent <= (float) $this->limit_value,
            default => $valuePercent > (float) $this->limit_value,
        };
    }
}
