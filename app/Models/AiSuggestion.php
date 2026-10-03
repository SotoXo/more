<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSuggestion extends Model
{
    protected $guarded = [];
    protected $casts = ['value' => 'json', 'evidence' => 'array', 'confidence_score' => 'float'];

    public function run()
    {
        return $this->belongsTo(AiRun::class, 'run_id');
    }

    public function reviews()
    {
        return $this->hasMany(AiHumanReview::class, 'suggestion_id');
    }
}
