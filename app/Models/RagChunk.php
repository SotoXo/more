<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RagChunk extends Model
{
    protected $guarded = [];
    protected $casts = ['metadata' => 'array', 'chunk_index' => 'integer'];

    public function document()
    {
        return $this->belongsTo(RagDocument::class);
    }
}
