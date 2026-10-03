<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiHumanReview extends Model
{
    protected $guarded = [];
    protected $casts = ['prediction' => 'json', 'confirmed_value' => 'json'];
}
