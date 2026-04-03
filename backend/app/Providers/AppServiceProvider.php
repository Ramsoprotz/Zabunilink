<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->applyDbMailConfig();
    }

    /**
     * Override Laravel's mail config with values stored in the settings table.
     * Falls back gracefully if the table doesn't exist yet (e.g. during migrations).
     */
    protected function applyDbMailConfig(): void
    {
        try {
            $map = [
                'mail_mailer'       => fn($v) => Config::set('mail.default', $v),
                'mail_host'         => fn($v) => Config::set('mail.mailers.smtp.host', $v),
                'mail_port'         => fn($v) => Config::set('mail.mailers.smtp.port', (int) $v),
                'mail_encryption'   => fn($v) => Config::set('mail.mailers.smtp.encryption', $v ?: null),
                'mail_username'     => fn($v) => Config::set('mail.mailers.smtp.username', $v),
                'mail_password'     => fn($v) => Config::set('mail.mailers.smtp.password', $v),
                'mail_from_address' => fn($v) => Config::set('mail.from.address', $v),
                'mail_from_name'    => fn($v) => Config::set('mail.from.name', $v),
            ];

            foreach ($map as $key => $apply) {
                $value = Setting::get($key);
                if ($value !== null && $value !== '') {
                    $apply($value);
                }
            }
        } catch (\Exception) {
            // Settings table may not exist during initial setup — skip silently.
        }
    }
}
