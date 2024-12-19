<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'current_step',
        'temp_data',
    ];

    protected $casts = [
        'temp_data' => 'array',
    ];
}
