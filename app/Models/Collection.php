<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'collections';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'sub_title',
        'content',
        'slug',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function images()
    {
        return $this->hasMany(CollectionImage::class, 'collection_id');
    }
}
