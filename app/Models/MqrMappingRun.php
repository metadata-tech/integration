<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MqrMappingRun extends Model
{
    use HasUlids;

    public const STATUS_RUNNING = 'running';

    public const STATUS_FINALIZING = 'finalizing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'max_mqr_id' => 'integer',
            'active_slot' => 'integer',
            'last_mqr_id' => 'integer',
            'total_records' => 'integer',
            'processed_count' => 'integer',
            'success_count' => 'integer',
            'failure_count' => 'integer',
            'batch_number' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}
