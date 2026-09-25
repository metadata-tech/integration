<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MqrMappingFailure extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'mqr_data_id' => 'integer',
            'missing_institution' => 'boolean',
            'missing_specialization' => 'boolean',
            'ambiguous_institution' => 'boolean',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(MqrMappingRun::class, 'run_id');
    }

    public function allowsInstitutionRegistration(): bool
    {
        return $this->missing_institution
            && ! $this->ambiguous_institution
            && trim((string) $this->nama_institusi) !== '';
    }

    public function allowsSpecializationRegistration(): bool
    {
        return $this->missing_specialization
            && ! $this->ambiguous_institution
            && trim((string) $this->nama_kelayakan) !== '';
    }

    public function allowsRegistration(): bool
    {
        return $this->allowsInstitutionRegistration()
            || $this->allowsSpecializationRegistration();
    }
}
