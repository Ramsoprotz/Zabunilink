<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerApiUsage extends Model
{
    protected $table = 'partner_api_usage';

    protected $fillable = ['user_id', 'date', 'requests'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Increment today's request counter for a partner.
     */
    public static function record(int $userId): void
    {
        static::upsert(
            [['user_id' => $userId, 'date' => now()->toDateString(), 'requests' => 1]],
            ['user_id', 'date'],
            ['requests' => \Illuminate\Support\Facades\DB::raw('requests + 1')],
        );
    }
}
