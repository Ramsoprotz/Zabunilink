<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use UnitEnum;

class EmailSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-envelope';
    protected static ?string                $navigationLabel = 'Email Settings';
    protected static string|UnitEnum|null   $navigationGroup = 'Settings';
    protected static ?int                   $navigationSort  = 20;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'mail_mailer'       => Setting::get('mail_mailer',       config('mail.default', 'log')),
            'mail_host'         => Setting::get('mail_host',         config('mail.mailers.smtp.host', '')),
            'mail_port'         => Setting::get('mail_port',         config('mail.mailers.smtp.port', '587')),
            'mail_encryption'   => Setting::get('mail_encryption',   config('mail.mailers.smtp.encryption', 'tls')),
            'mail_username'     => Setting::get('mail_username',     config('mail.mailers.smtp.username', '')),
            'mail_password'     => Setting::get('mail_password',     config('mail.mailers.smtp.password', '')),
            'mail_from_address' => Setting::get('mail_from_address', config('mail.from.address', '')),
            'mail_from_name'    => Setting::get('mail_from_name',    config('mail.from.name', 'ZabuniLink')),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])->id('form'),
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('SMTP Configuration')
                    ->description('Configure the outgoing mail server. These settings are stored in the database and override your .env values.')
                    ->icon('heroicon-o-server')
                    ->schema([
                        Select::make('mail_mailer')
                            ->label('Mailer')
                            ->options([
                                'smtp'     => 'SMTP',
                                'sendmail' => 'Sendmail',
                                'log'      => 'Log (development only)',
                            ])
                            ->required()
                            ->live(),

                        TextInput::make('mail_host')
                            ->label('SMTP Host')
                            ->placeholder('smtp.gmail.com')
                            ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),

                        TextInput::make('mail_port')
                            ->label('SMTP Port')
                            ->numeric()
                            ->placeholder('587')
                            ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),

                        Select::make('mail_encryption')
                            ->label('Encryption')
                            ->options([
                                'tls' => 'TLS (port 587)',
                                'ssl' => 'SSL (port 465)',
                                ''    => 'None',
                            ])
                            ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),

                        TextInput::make('mail_username')
                            ->label('Username / Email')
                            ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),

                        TextInput::make('mail_password')
                            ->label('Password / App Password')
                            ->password()
                            ->revealable()
                            ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),
                    ])->columns(2),

                Section::make('Sender Identity')
                    ->description('The name and address that recipients will see in the "From" field.')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        TextInput::make('mail_from_address')
                            ->label('From Address')
                            ->email()
                            ->required()
                            ->placeholder('noreply@zabunilink.co.tz'),

                        TextInput::make('mail_from_name')
                            ->label('From Name')
                            ->required()
                            ->placeholder('ZabuniLink'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $key => $value) {
            Setting::set($key, $value ?? '', 'email');
        }

        Notification::make()
            ->title('Email settings saved successfully.')
            ->success()
            ->send();
    }

    public function sendTestEmail(): void
    {
        $state = $this->form->getState();

        $this->applyMailConfig($state);

        $adminEmail = auth()->user()->email;

        try {
            Mail::raw(
                "This is a test email from ZabuniLink admin panel.\nYour email configuration is working correctly.\n\nSent at: " . now()->toDateTimeString(),
                function ($message) use ($adminEmail) {
                    $message->to($adminEmail)->subject('ZabuniLink — Email Configuration Test');
                }
            );

            Notification::make()
                ->title("Test email sent to {$adminEmail}. Check your inbox.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to send test email: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    protected function applyMailConfig(array $state): void
    {
        Config::set('mail.default', $state['mail_mailer'] ?? 'log');
        Config::set('mail.mailers.smtp.host',       $state['mail_host'] ?? '');
        Config::set('mail.mailers.smtp.port',       (int) ($state['mail_port'] ?? 587));
        Config::set('mail.mailers.smtp.encryption', $state['mail_encryption'] ?: null);
        Config::set('mail.mailers.smtp.username',   $state['mail_username'] ?? '');
        Config::set('mail.mailers.smtp.password',   $state['mail_password'] ?? '');
        Config::set('mail.from.address',            $state['mail_from_address'] ?? '');
        Config::set('mail.from.name',               $state['mail_from_name'] ?? 'ZabuniLink');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestEmail')
                ->label('Send Test Email')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->action('sendTestEmail'),

            Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }
}
