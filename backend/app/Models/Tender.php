<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tender extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'reference_number',
        'description',
        'organization',
        'category_id',
        'location_id',
        'type',
        'source',
        'value',
        'deadline',
        'published_date',
        'status',
        'requirements',
        'documents_url',
        'contact_info',
        'created_by',
        'posted_by_user_id',
        'views_count',
        'applications_count',
        'is_published',
        'documents',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'published_date' => 'date',
            'value' => 'decimal:2',
            'is_published' => 'boolean',
            'documents' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_user_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(TenderApplication::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open')->where('deadline', '>=', now());
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->when($categoryId, fn($q) => $q->where('category_id', $categoryId));
    }

    public function scopeByLocation($query, $locationId)
    {
        return $query->when($locationId, fn($q) => $q->where('location_id', $locationId));
    }

    public function scopeByType($query, $type)
    {
        return $query->when($type, fn($q) => $q->where('type', $type));
    }
}
