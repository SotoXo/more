<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiProductEmbedding extends Model
{
    protected $guarded = [];
    protected $hidden = ['embedding'];
    protected $casts = [
        'metadata' => 'array', 'dimensions' => 'integer',
        'norm' => 'float', 'input_norm' => 'float',
    ];

    public function prenda()
    {
        return $this->belongsTo(Prenda::class, 'prenda_id', 'id_prenda');
    }

    public function image()
    {
        return $this->belongsTo(ImagenPrenda::class, 'image_id', 'id_imagen_prenda');
    }

    public function sourceRun()
    {
        return $this->belongsTo(AiRun::class, 'source_run_id');
    }

    /** Internal only: the native vector is deliberately hidden from serialized UI data. */
    public function toEmbedding(): array
    {
        return [
            'values' => json_decode((string) $this->getRawOriginal('embedding'), true, 512, JSON_THROW_ON_ERROR),
            'dimension' => $this->dimensions, 'dimensions' => $this->dimensions,
            'model_id' => data_get($this->metadata, 'model_id'),
            'model_revision' => data_get($this->metadata, 'model_revision'),
            'pipeline_version' => data_get($this->metadata, 'pipeline_version'),
            'embedding_version' => $this->embedding_version,
            'preprocess_version' => $this->preprocess_version,
            'normalization' => $this->normalization, 'normalized' => true, 'norm' => $this->norm,
        ];
    }
}
