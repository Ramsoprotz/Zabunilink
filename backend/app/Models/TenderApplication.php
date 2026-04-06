<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderApplication extends Model
{
    protected $fillable = [
        'user_id',
        'tender_id',
        'status',
        'previous_status',
        'notes',
        'admin_notes',
        'tenderee_notes',
        'documents',
        'submitted_at',
        'awarded_at',
    ];

    protected function casts(): array
    {
        return [
            'documents' => 'array',
            'submitted_at' => 'datetime',
            'awarded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }
}
