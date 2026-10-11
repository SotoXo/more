<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RagEmbedding extends Model
{
    protected $guarded = [];
    protected $hidden = ['embedding'];
    protected $casts = ['dimensions' => 'integer', 'norm' => 'float'];

    public function chunk()
    {
        return $this->belongsTo(RagChunk::class);
    }
}
