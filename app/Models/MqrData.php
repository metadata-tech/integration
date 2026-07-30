<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MqrData extends Model
{
    use HasFactory;

    protected $table = 'mqr_data';

    // Benarkan semua column untuk mass assignment
    protected $guarded = [];
}
