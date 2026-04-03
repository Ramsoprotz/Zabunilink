<?php

namespace App\Filament\Pages;

use App\Integrations\NextSmsService;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class SmsSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static ?string                $navigationLabel = 'SMS Settings';
    protected static string|UnitEnum|null   $navigationGroup = 'Settings';
    protected static ?int                   $navigationSort  = 10;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'nextsms_username'  => Setting::get('nextsms_username', config('services.nextsms.username', '')),
            'nextsms_password'  => Setting::get('nextsms_password', config('services.nextsms.password', '')),
            'nextsms_sender_id' => Setting::get('nextsms_sender_id', config('services.nextsms.sender_id', 'ZABUNILINK')),
            'nextsms_test_mode' => (bool) Setting::get('nextsms_test_mode', app()->environment() !== 'production'),
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
                Section::make('NextSMS API Credentials')
                    ->description('Configure your NextSMS (messaging-service.co.tz) account details. These settings override the .env values.')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->schema([
                        TextInput::make('nextsms_username')
                            ->label('Username')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('nextsms_password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('nextsms_sender_id')
                            ->label('Sender ID')
                            ->required()
                            ->maxLength(11)
                            ->helperText('Alphanumeric, max 11 characters (e.g. ZABUNILINK).'),

                        Toggle::make('nextsms_test_mode')
                            ->label('Test Mode')
                            ->helperText('When enabled, messages are sent to the NextSMS test endpoint and not delivered to real phones.')
                            ->inline(false),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::set('nextsms_username',  $state['nextsms_username'],  'sms');
        Setting::set('nextsms_password',  $state['nextsms_password'],  'sms');
        Setting::set('nextsms_sender_id', $state['nextsms_sender_id'], 'sms');
        Setting::set('nextsms_test_mode', $state['nextsms_test_mode'] ? '1' : '0', 'sms');

        Notification::make()
            ->title('SMS settings saved successfully.')
            ->success()
            ->send();
    }

    public function sendTestSms(): void
    {
        $state = $this->form->getState();

        if (empty($state['nextsms_username']) || empty($state['nextsms_password'])) {
            Notification::make()
                ->title('Please save credentials before sending a test SMS.')
                ->warning()
                ->send();
            return;
        }

        Setting::set('nextsms_username',  $state['nextsms_username'],  'sms');
        Setting::set('nextsms_password',  $state['nextsms_password'],  'sms');
        Setting::set('nextsms_sender_id', $state['nextsms_sender_id'], 'sms');
        Setting::set('nextsms_test_mode', '1', 'sms');

        $adminPhone = auth()->user()->phone ?? null;

        if (! $adminPhone) {
            Notification::make()
                ->title('Your admin account has no phone number set. Add one to your profile first.')
                ->warning()
                ->send();
            return;
        }

        $result = app(NextSmsService::class)->sendSms(
            $adminPhone,
            'ZabuniLink SMS test: Your NextSMS integration is working correctly!'
        );

        if (isset($result['status']) && in_array($result['status'], ['failed', 'skipped'])) {
            Notification::make()
                ->title('Test SMS failed: ' . ($result['error'] ?? $result['reason'] ?? 'Unknown error'))
                ->danger()
                ->send();
        } else {
            Notification::make()
                ->title('Test SMS sent to ' . $adminPhone . ' (test mode — not actually delivered).')
                ->success()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestSms')
                ->label('Send Test SMS')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->action('sendTestSms'),

            Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }
}
