<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
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

class FreeTrialSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-gift';
    protected static ?string                $navigationLabel = 'Free Trial';
    protected static string|UnitEnum|null   $navigationGroup = 'Billing';
    protected static ?int                   $navigationSort  = 4;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'free_trial_enabled' => (bool) Setting::get('free_trial_enabled', false),
            'free_trial_days'    => Setting::get('free_trial_days', '14'),
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
                Section::make('Free Trial Configuration')
                    ->description('Allow new users to try the Basic plan for free. The trial starts when the user signs up or activates it — no payment required.')
                    ->icon('heroicon-o-gift')
                    ->schema([
                        Toggle::make('free_trial_enabled')
                            ->label('Enable Free Trial')
                            ->helperText('When enabled, new users can start a free Basic plan trial. When disabled, all plans require payment.')
                            ->inline(false)
                            ->live(),

                        Select::make('free_trial_days')
                            ->label('Trial Duration')
                            ->options([
                                '14' => '14 Days',
                                '30' => '30 Days',
                            ])
                            ->required()
                            ->visible(fn ($get) => $get('free_trial_enabled'))
                            ->helperText('How long the free trial lasts before the user must subscribe.'),

                        Placeholder::make('trial_rules')
                            ->label('Trial Rules')
                            ->content('• Only the Basic plan is available as a free trial.
• Each user can only use one free trial (tracked per account).
• When the trial expires, the user must subscribe to continue.
• Upgrading to Pro or Business during a trial cancels the trial and requires payment.')
                            ->visible(fn ($get) => $get('free_trial_enabled')),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::set('free_trial_enabled', $state['free_trial_enabled'] ? '1' : '0', 'billing');
        Setting::set('free_trial_days', $state['free_trial_days'] ?? '14', 'billing');

        Notification::make()
            ->title('Free trial settings saved successfully.')
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
