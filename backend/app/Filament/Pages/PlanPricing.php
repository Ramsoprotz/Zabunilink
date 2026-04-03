<?php

namespace App\Filament\Pages;

use App\Models\Plan;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
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

class PlanPricing extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-currency-dollar';
    protected static ?string                $navigationLabel = 'Plan Pricing';
    protected static string|UnitEnum|null   $navigationGroup = 'Billing';
    protected static ?int                   $navigationSort  = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $plans = Plan::orderBy('tier')->get();

        $planData = [];
        foreach ($plans as $plan) {
            $planData[] = [
                'id'               => $plan->id,
                'name'             => $plan->name,
                'description'      => $plan->description,
                'monthly_price'    => $plan->monthly_price,
                'quarterly_price'  => $plan->quarterly_price,
                'semi_annual_price'=> $plan->semi_annual_price,
                'annual_price'     => $plan->annual_price,
                'features'         => $plan->features ?? [],
                'is_active'        => $plan->is_active,
            ];
        }

        $this->form->fill(['plans' => $planData]);
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
                Section::make('Subscription Plans')
                    ->description('Configure pricing for each plan across all billing cycles. Prices are in TZS.')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Repeater::make('plans')
                            ->label('')
                            ->schema([
                                TextInput::make('id')->hidden(),

                                TextInput::make('name')
                                    ->label('Plan Name')
                                    ->disabled()
                                    ->dehydrated(),

                                Textarea::make('description')
                                    ->label('Description')
                                    ->rows(2)
                                    ->maxLength(500),

                                TextInput::make('monthly_price')
                                    ->label('Monthly Price (TZS)')
                                    ->numeric()
                                    ->required()
                                    ->prefix('TZS')
                                    ->minValue(0),

                                TextInput::make('quarterly_price')
                                    ->label('Quarterly Price (TZS)')
                                    ->numeric()
                                    ->required()
                                    ->prefix('TZS')
                                    ->minValue(0),

                                TextInput::make('semi_annual_price')
                                    ->label('6-Month Price (TZS)')
                                    ->numeric()
                                    ->required()
                                    ->prefix('TZS')
                                    ->minValue(0),

                                TextInput::make('annual_price')
                                    ->label('Annual Price (TZS)')
                                    ->numeric()
                                    ->required()
                                    ->prefix('TZS')
                                    ->minValue(0),

                                TagsInput::make('features')
                                    ->label('Features')
                                    ->placeholder('Add a feature')
                                    ->helperText('Type a feature and press Enter.'),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->helperText('Deactivated plans are hidden from users but existing subscriptions continue.')
                                    ->inline(false),
                            ])
                            ->columns(2)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Plan'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state['plans'] as $planData) {
            $plan = Plan::find($planData['id']);
            if (! $plan) {
                continue;
            }

            $plan->update([
                'description'       => $planData['description'],
                'monthly_price'     => $planData['monthly_price'],
                'quarterly_price'   => $planData['quarterly_price'],
                'semi_annual_price' => $planData['semi_annual_price'],
                'annual_price'      => $planData['annual_price'],
                'features'          => $planData['features'] ?? [],
                'is_active'         => $planData['is_active'],
            ]);
        }

        Notification::make()
            ->title('Plan pricing updated successfully.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Pricing')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }
}
