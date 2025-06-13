<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceDetails extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'service_id',
        'name',
        'title',
        'subtitle',
        'slug',
        'description',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(ServiceFAQ::class);
    }

    public function whatIncludes(): HasMany
    {
        return $this->hasMany(ServiceWhatInclude::class);
    }

    public function caseStudies(): HasMany
    {
        return $this->hasMany(ServiceCaseStudy::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ServiceImage::class, 'service_details_id');
    }
     public function getServiceDetailsAttribute($value): ?string
    {
        return empty($value) ? null : (filter_var($value, FILTER_VALIDATE_URL) ? $value : (request()->is('api/*') ? url($value) : $value));
    }
}
