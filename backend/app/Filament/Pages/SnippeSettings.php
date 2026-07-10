<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
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

class SnippeSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-device-phone-mobile';
    protected static ?string                $navigationLabel = 'Snippe Payments';
    protected static string|UnitEnum|null   $navigationGroup = 'Settings';
    protected static ?int                   $navigationSort  = 14;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'snippe_enabled'        => Setting::get('snippe_enabled', '1') === '1',
            'snippe_api_key'        => Setting::get('snippe_api_key', ''),
            'snippe_webhook_secret' => Setting::get('snippe_webhook_secret', ''),
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
                Section::make('Snippe Mobile Money Configuration')
                    ->description('Configure Snippe for direct mobile money USSD push payments (M-Pesa, Airtel Money, Mixx by Yas, Halotel). Get credentials from your Snippe dashboard at snippe.sh.')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->schema([
                        Toggle::make('snippe_enabled')
                            ->label('Enable Snippe Mobile Money')
                            ->helperText('When enabled, users can pay via USSD push without leaving the app.')
                            ->inline(false),

                        TextInput::make('snippe_api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->helperText('Your Snippe API key (Dashboard → Settings → API Keys).'),

                        TextInput::make('snippe_webhook_secret')
                            ->label('Webhook Secret')
                            ->password()
                            ->revealable()
                            ->helperText('Used to verify payment webhooks from Snippe.'),

                        Placeholder::make('webhook_info')
                            ->label('Webhook URL (for Snippe dashboard)')
                            ->content(url('/api/payments/snippe/webhook'))
                            ->helperText('This URL is also sent automatically with each payment request.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::set('snippe_enabled', ! empty($state['snippe_enabled']) ? '1' : '0', 'payments');
        Setting::set('snippe_api_key', $state['snippe_api_key'] ?? '', 'payments');
        Setting::set('snippe_webhook_secret', $state['snippe_webhook_secret'] ?? '', 'payments');

        Notification::make()
            ->title('Snippe settings saved successfully.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }
}
