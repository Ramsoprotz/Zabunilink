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
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use UnitEnum;

class BrandingSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-photo';
    protected static ?string                $navigationLabel = 'Branding';
    protected static string|UnitEnum|null   $navigationGroup = 'Settings';
    protected static ?int                   $navigationSort  = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $logo = Setting::get('system_logo');
        $favicon = Setting::get('system_favicon');

        $this->form->fill([
            'system_logo' => $logo ? [$logo] : [],
            'system_favicon' => $favicon ? [$favicon] : [],
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
                            ->helperText('Recommended: 200×60 px, PNG with transparent background or SVG. Max 2 MB.'),

                        Placeholder::make('logo_guidelines')
                            ->label('Logo Guidelines')
                            ->content('• Use a horizontal/landscape logo for best results in the sidebar.
• Recommended dimensions: 200×60 px (width × height).
• PNG with transparent background or SVG works best.
• If no logo is set, the default "ZabuniLink" text branding is shown.'),
                    ]),

                Section::make('Favicon')
                    ->description('Upload a favicon for browser tabs and bookmarks. It will be automatically resized to 48×48 pixels.')
                    ->icon('heroicon-o-star')
                    ->schema([
                        FileUpload::make('system_favicon')
                            ->label('Favicon')
                            ->image()
                            ->disk('public')
                            ->directory('branding')
                            ->maxSize(1024)
                            ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/svg+xml', 'image/jpeg'])
                            ->helperText('Upload a square image (PNG, ICO, SVG, or JPG). It will be resized to 48×48 px automatically. Max 1 MB.'),

                        Placeholder::make('favicon_guidelines')
                            ->label('Favicon Guidelines')
                            ->content('• Upload a square image — ideally your logo icon or monogram.
• Recommended: 512×512 px source image (will be resized to 48×48 px).
• PNG with transparent background works best.
• The favicon appears in browser tabs, bookmarks, and mobile home screens.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // FileUpload returns array or string depending on context
        $logoPath = is_array($state['system_logo'] ?? null)
            ? collect($state['system_logo'])->first()
            : ($state['system_logo'] ?? null);

        $faviconPath = is_array($state['system_favicon'] ?? null)
            ? collect($state['system_favicon'])->first()
            : ($state['system_favicon'] ?? null);

        // Handle logo
        $oldLogo = Setting::get('system_logo');
        if ($oldLogo && $oldLogo !== $logoPath) {
            Storage::disk('public')->delete($oldLogo);
        }
        Setting::set('system_logo', $logoPath, 'branding');

        // Handle favicon
        $oldFavicon = Setting::get('system_favicon');
        if ($oldFavicon && $oldFavicon !== $faviconPath) {
            Storage::disk('public')->delete($oldFavicon);
        }

        // Resize favicon to 48x48 if it's a raster image
        if ($faviconPath && !str_ends_with($faviconPath, '.svg') && !str_ends_with($faviconPath, '.ico')) {
            try {
                $fullPath = Storage::disk('public')->path($faviconPath);
                $manager = new ImageManager(new Driver());
                $image = $manager->read($fullPath);
                $image->cover(48, 48);
                $image->toPng()->save($fullPath);
            } catch (\Exception $e) {
                // If resize fails, keep original
            }
        }

        Setting::set('system_favicon', $faviconPath, 'branding');

        Notification::make()
            ->title('Branding settings saved successfully.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Branding')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }
}
