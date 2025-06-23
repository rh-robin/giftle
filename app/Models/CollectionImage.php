<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionImage extends Model
{
    protected $table = 'collection_images';

    protected $fillable = [
        'collection_id',
        'image',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function collection()
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

}
