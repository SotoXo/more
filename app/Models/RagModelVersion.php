<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RagModelVersion extends Model
{
    protected $guarded = [];
    protected $casts = ['dimensions' => 'integer'];
}
