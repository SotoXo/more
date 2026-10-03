<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiRun extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = ['result' => 'array', 'input_metadata' => 'array', 'embedding' => 'array', 'expires_at' => 'datetime'];

    public function suggestions()
    {
        return $this->hasMany(AiSuggestion::class, 'run_id');
    }
}
