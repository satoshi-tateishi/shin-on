<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceProduction extends Model
{
    use HasFactory;

    protected $table = 'performance_production';

    protected $fillable = [
        'performance_id',
        'production_id',
    ];

    /**
     * 公演との関連
     */
    public function performance(): BelongsTo
    {
        return $this->belongsTo(Performance::class);
    }

    /**
     * プロダクションとの関連
     */
    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }
}
