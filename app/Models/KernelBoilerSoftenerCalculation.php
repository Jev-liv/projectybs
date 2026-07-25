<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KernelBoilerSoftenerCalculation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kernel_boiler_softener_calculations';

    protected $fillable = [
        'user_id',
        'office',
        'rounded_time',
        'jenis',
        'parameter',
        'nilai',
        'satuan',
        'operator',
        'sampel_boy',
        'pengulangan',
        'remarks',
    ];

    protected $casts = [
        'rounded_time' => 'datetime',
        'nilai' => 'decimal:4',
        'pengulangan' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}