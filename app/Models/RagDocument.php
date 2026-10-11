<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RagDocument extends Model
{
    protected $guarded = [];
    protected $casts = ['metadata' => 'array'];

    public function chunks()
    {
        return $this->hasMany(RagChunk::class, 'document_id');
    }
}
