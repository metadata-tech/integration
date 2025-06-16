<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class APILog extends Model
{
    public $timestamps = true;

    protected $table = 'api_logs';
    protected $primaryKey = 'id';

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'headers' => 'array'
    ];

    protected $fillable = [
        'url',
        'method',
        'status_code',
        'user_id',
        'token',
        'input',
        'output',
        'headers',
        'request',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
