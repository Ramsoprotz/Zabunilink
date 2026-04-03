<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class BrandingSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-photo';
    protected static ?string                $navigationLabel = 'System Logo';
    protected static string|UnitEnum|null   $navigationGroup = 'Settings';
    protected static ?int                   $navigationSort  = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'system_logo' => Setting::get('system_logo'),
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
                Section::make('System Logo')
                    ->description('Upload your organisation logo. It will appear on the sidebar and login/register pages.')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        FileUpload::make('system_logo')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('branding')
                            ->maxSize(2048)
                            ->imageResizeMode('contain')
                            ->imageCropAspectRatio(null)
                            ->helperText('Recommended size: 200×60 pixels (PNG or SVG with transparent background). Max 2 MB.'),

                        Placeholder::make('guidelines')
                            ->label('Guidelines')
                            ->content('• Use a horizontal/landscape logo for best results in the sidebar.
• Recommended dimensions: 200×60 px (width × height).
• PNG with transparent background or SVG works best.
• If no logo is set, the default "ZabuniLink" text branding is shown.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $logoPath = $state['system_logo'] ?? null;

        // Delete old logo file if replaced
        $oldLogo = Setting::get('system_logo');
        if ($oldLogo && $oldLogo !== $logoPath) {
            Storage::disk('public')->delete($oldLogo);
        }

        Setting::set('system_logo', $logoPath, 'branding');

        Notification::make()
            ->title('System logo saved successfully.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Logo')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }
}
