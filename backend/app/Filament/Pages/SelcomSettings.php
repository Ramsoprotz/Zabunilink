<?php

namespace App\Filament\Pages;

use App\Integrations\SelcomService;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
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

class SelcomSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-credit-card';
    protected static ?string                $navigationLabel = 'Selcom Payments';
    protected static string|UnitEnum|null   $navigationGroup = 'Settings';
    protected static ?int                   $navigationSort  = 15;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'selcom_api_key'     => Setting::get('selcom_api_key', ''),
            'selcom_api_secret'  => Setting::get('selcom_api_secret', ''),
            'selcom_vendor'      => Setting::get('selcom_vendor', ''),
            'selcom_environment' => Setting::get('selcom_environment', 'sandbox'),
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
                Section::make('Selcom API Configuration')
                    ->description('Configure Selcom payment gateway credentials. Get these from your Selcom merchant dashboard.')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        TextInput::make('selcom_api_key')
                            ->label('API Key')
                            ->required()
                            ->password()
                            ->revealable()
                            ->helperText('Your Selcom API Key (Client ID).'),

                        TextInput::make('selcom_api_secret')
                            ->label('API Secret')
                            ->required()
                            ->password()
                            ->revealable()
                            ->helperText('Your Selcom API Secret.'),

                        TextInput::make('selcom_vendor')
                            ->label('Vendor / Till Number')
                            ->required()
                            ->helperText('Your Selcom vendor ID (till number).'),

                        Select::make('selcom_environment')
                            ->label('Environment')
                            ->options([
                                'sandbox' => 'Sandbox (Testing)',
                                'live'    => 'Live (Production)',
                            ])
                            ->required()
                            ->helperText('Use Sandbox for testing, Live for real payments.'),

                        Placeholder::make('webhook_info')
                            ->label('Webhook URL (for Selcom dashboard)')
                            ->content(url('/api/payments/callback'))
                            ->helperText('Copy this URL to your Selcom merchant dashboard webhook settings.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::set('selcom_api_key', $state['selcom_api_key'] ?? '', 'payments');
        Setting::set('selcom_api_secret', $state['selcom_api_secret'] ?? '', 'payments');
        Setting::set('selcom_vendor', $state['selcom_vendor'] ?? '', 'payments');
        Setting::set('selcom_environment', $state['selcom_environment'] ?? 'sandbox', 'payments');

        Notification::make()
            ->title('Selcom settings saved successfully.')
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
