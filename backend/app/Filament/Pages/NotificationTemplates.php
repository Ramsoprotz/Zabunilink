<?php

namespace App\Filament\Pages;

use App\Models\NotificationTemplate;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class NotificationTemplates extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-chat-bubble-bottom-center-text';
    protected static ?string                $navigationLabel = 'Notification Templates';
    protected static string|UnitEnum|null   $navigationGroup = 'Notifications';
    protected static ?int                   $navigationSort  = 1;

    public ?string $selectedType = null;
    public ?array  $data         = [];

    public function mount(): void
    {
        $this->selectedType = array_key_first(NotificationTemplate::TYPES);
        $this->loadTemplates();
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
                Section::make('Select Notification Type')
                    ->schema([
                        Select::make('selectedType')
                            ->label('Notification Type')
                            ->options(collect(NotificationTemplate::TYPES)->mapWithKeys(
                                fn ($cfg, $key) => [$key => $cfg['label']]
                            ))
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->loadTemplates()),

                        Placeholder::make('placeholders_info')
                            ->label('Available Placeholders')
                            ->content(function () {
                                $type = $this->selectedType;
                                if (! $type || ! isset(NotificationTemplate::TYPES[$type])) {
                                    return '';
                                }
                                $placeholders = NotificationTemplate::TYPES[$type]['placeholders'];
                                return collect($placeholders)->map(fn ($p) => '{{' . $p . '}}')->join('  ,  ');
                            }),
                    ]),

                Section::make('Email Templates')
                    ->description('Edit the email subject and body for each language. You can use HTML and the placeholders listed above.')
                    ->icon('heroicon-o-envelope')
                    ->schema([
                        Tabs::make('email_tabs')
                            ->tabs([
                                Tab::make('English')
                                    ->icon('heroicon-o-language')
                                    ->schema([
                                        TextInput::make('email_en_subject')
                                            ->label('Subject')
                                            ->required()
                                            ->maxLength(255),
                                        Textarea::make('email_en_body')
                                            ->label('Body (HTML)')
                                            ->required()
                                            ->rows(12)
                                            ->helperText('HTML content that renders inside the email layout. Use the card/btn CSS classes from the layout.'),
                                    ]),
                                Tab::make('Swahili')
                                    ->icon('heroicon-o-language')
                                    ->schema([
                                        TextInput::make('email_sw_subject')
                                            ->label('Mada')
                                            ->required()
                                            ->maxLength(255),
                                        Textarea::make('email_sw_body')
                                            ->label('Mwili (HTML)')
                                            ->required()
                                            ->rows(12)
                                            ->helperText('Yaliyomo ya HTML yanayoonyeshwa ndani ya mpangilio wa barua pepe.'),
                                    ]),
                            ]),
                    ]),

                Section::make('SMS Templates')
                    ->description('Edit the SMS text for each language. Keep SMS under 160 characters when possible.')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->schema([
                        Tabs::make('sms_tabs')
                            ->tabs([
                                Tab::make('English')
                                    ->icon('heroicon-o-language')
                                    ->schema([
                                        Textarea::make('sms_en_body')
                                            ->label('SMS Text')
                                            ->required()
                                            ->rows(3)
                                            ->helperText(fn () => 'Plain text. Use placeholders above.'),
                                    ]),
                                Tab::make('Swahili')
                                    ->icon('heroicon-o-language')
                                    ->schema([
                                        Textarea::make('sms_sw_body')
                                            ->label('Ujumbe wa SMS')
                                            ->required()
                                            ->rows(3)
                                            ->helperText('Maandishi tupu. Tumia vibadilishi hapo juu.'),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function loadTemplates(): void
    {
        $type = $this->data['selectedType'] ?? $this->selectedType ?? array_key_first(NotificationTemplate::TYPES);
        $this->selectedType = $type;

        $templates = NotificationTemplate::where('type', $type)->get();

        $formData = ['selectedType' => $type];

        foreach ($templates as $tpl) {
            $key = $tpl->channel . '_' . $tpl->locale;
            if ($tpl->channel === 'email') {
                $formData["{$key}_subject"] = $tpl->subject;
                $formData["{$key}_body"]    = $tpl->body;
            } else {
                $formData["{$key}_body"] = $tpl->body;
            }
        }

        $this->form->fill($formData);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $type  = $state['selectedType'] ?? $this->selectedType;

        $updates = [
            ['channel' => 'email', 'locale' => 'en', 'subject' => $state['email_en_subject'] ?? '', 'body' => $state['email_en_body'] ?? ''],
            ['channel' => 'email', 'locale' => 'sw', 'subject' => $state['email_sw_subject'] ?? '', 'body' => $state['email_sw_body'] ?? ''],
            ['channel' => 'sms',   'locale' => 'en', 'subject' => null, 'body' => $state['sms_en_body'] ?? ''],
            ['channel' => 'sms',   'locale' => 'sw', 'subject' => null, 'body' => $state['sms_sw_body'] ?? ''],
        ];

        foreach ($updates as $upd) {
            NotificationTemplate::updateOrCreate(
                ['type' => $type, 'channel' => $upd['channel'], 'locale' => $upd['locale']],
                ['subject' => $upd['subject'], 'body' => $upd['body']],
            );
        }

        NotificationTemplate::clearCache();

        Notification::make()
            ->title('Templates saved successfully.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Templates')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }
}
