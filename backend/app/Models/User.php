<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'support']);
    }

    protected $fillable = [
        'name',
        'email',
        'phone',
        'business_name',
        'role',
        'password',
        'fcm_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSupport(): bool
    {
        return $this->role === 'support';
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['admin', 'support']);
    }

    public function isPro(): bool
    {
        $sub = $this->activeSubscription;
        if (!$sub) return false;
        return in_array($sub->plan->name, ['Pro', 'Business']);
    }

    public function isBusiness(): bool
    {
        $sub = $this->activeSubscription;
        return $sub && $sub->plan->name === 'Business';
    }

    public function canPostTenders(): bool
    {
        return $this->isBusiness();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->latestOfMany();
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function tenderApplications(): HasMany
    {
        return $this->hasMany(TenderApplication::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function tenderNotifications(): HasMany
    {
        return $this->hasMany(TenderNotification::class);
    }

    public function postedTenders(): HasMany
    {
        return $this->hasMany(Tender::class, 'posted_by_user_id');
    }
}
