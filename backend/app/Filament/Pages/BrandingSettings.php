<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\View;
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
        $this->form->fill([
            'system_logo' => null,
            'system_favicon' => null,
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
        $currentLogo = Setting::get('system_logo');
        $currentFavicon = Setting::get('system_favicon');
        $logoUrl = $currentLogo ? Storage::disk('public')->url($currentLogo) : null;
        $faviconUrl = $currentFavicon ? Storage::disk('public')->url($currentFavicon) : null;

        return $form
            ->schema([
                Section::make('System Logo')
                    ->description('Upload your organisation logo. It will appear on the sidebar and login/register pages.')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        Placeholder::make('current_logo_preview')
                            ->label('Current Logo')
                            ->content(fn () => $logoUrl
                                ? new \Illuminate\Support\HtmlString('<img src="' . $logoUrl . '" alt="Current Logo" style="max-height:60px; max-width:200px; border:1px solid #e5e7eb; border-radius:8px; padding:8px; background:#f9fafb;">')
                                : 'No logo set')
                            ->visible((bool) $logoUrl),

                        FileUpload::make('system_logo')
                            ->label($currentLogo ? 'Replace Logo' : 'Upload Logo')
                            ->image()
                            ->disk('public')
                            ->directory('branding')
                            ->maxSize(2048)
                            ->helperText('Recommended: 200×60 px, PNG with transparent background or SVG. Max 2 MB.'),

                        Placeholder::make('logo_guidelines')
                            ->label('Guidelines')
                            ->content('• Use a horizontal/landscape logo for best results in the sidebar.
• PNG with transparent background or SVG works best.
• If no logo is set, the default "ZabuniLink" text branding is shown.'),
                    ]),

                Section::make('Favicon')
                    ->description('Upload a favicon for browser tabs and bookmarks. It will be automatically resized to 48×48 pixels.')
                    ->icon('heroicon-o-star')
                    ->schema([
                        Placeholder::make('current_favicon_preview')
                            ->label('Current Favicon')
                            ->content(fn () => $faviconUrl
                                ? new \Illuminate\Support\HtmlString('<img src="' . $faviconUrl . '" alt="Current Favicon" style="width:48px; height:48px; border:1px solid #e5e7eb; border-radius:8px; padding:4px; background:#f9fafb;">')
                                : 'No favicon set')
                            ->visible((bool) $faviconUrl),

                        FileUpload::make('system_favicon')
                            ->label($currentFavicon ? 'Replace Favicon' : 'Upload Favicon')
                            ->image()
                            ->disk('public')
                            ->directory('branding')
                            ->maxSize(1024)
                            ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/svg+xml', 'image/jpeg'])
                            ->helperText('Upload a square image (PNG, ICO, SVG, or JPG). Auto-resized to 48×48 px. Max 1 MB.'),

                        Placeholder::make('favicon_guidelines')
                            ->label('Guidelines')
                            ->content('• Upload a square image — ideally your logo icon or monogram.
• PNG with transparent background works best.
• The favicon appears in browser tabs, bookmarks, and mobile home screens.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // Extract path from FileUpload (returns string or null for single uploads)
        $newLogo = $state['system_logo'] ?? null;
        $newFavicon = $state['system_favicon'] ?? null;

        // Handle logo — only update if a new file was uploaded
        if ($newLogo) {
            $oldLogo = Setting::get('system_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
            Setting::set('system_logo', $newLogo, 'branding');
        }

        // Handle favicon — only update if a new file was uploaded
        if ($newFavicon) {
            $oldFavicon = Setting::get('system_favicon');
            if ($oldFavicon) {
                Storage::disk('public')->delete($oldFavicon);
            }

            // Resize to 48x48 if raster image
            if (!str_ends_with($newFavicon, '.svg') && !str_ends_with($newFavicon, '.ico')) {
                try {
                    $fullPath = Storage::disk('public')->path($newFavicon);
                    $manager = ImageManager::usingDriver('gd');
                    $image = $manager->decodePath($fullPath);
                    $image->cover(48, 48);
                    $image->toPng()->save($fullPath);
                } catch (\Exception $e) {
                    // Keep original if resize fails
                }
            }

            Setting::set('system_favicon', $newFavicon, 'branding');
        }

        Notification::make()
            ->title('Branding settings saved successfully.')
            ->success()
            ->send();
    }

    public function removeLogo(): void
    {
        $logo = Setting::get('system_logo');
        if ($logo) {
            Storage::disk('public')->delete($logo);
            Setting::set('system_logo', null, 'branding');
        }

        Notification::make()
            ->title('Logo removed.')
            ->success()
            ->send();

        $this->redirect(static::getUrl());
    }

    public function removeFavicon(): void
    {
        $favicon = Setting::get('system_favicon');
        if ($favicon) {
            Storage::disk('public')->delete($favicon);
            Setting::set('system_favicon', null, 'branding');
        }

        Notification::make()
            ->title('Favicon removed.')
            ->success()
            ->send();

        $this->redirect(static::getUrl());
    }

    protected function getHeaderActions(): array
    {
        $hasLogo = (bool) Setting::get('system_logo');
        $hasFavicon = (bool) Setting::get('system_favicon');

        return array_filter([
            $hasLogo ? Action::make('removeLogo')
                ->label('Remove Logo')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action('removeLogo') : null,
            $hasFavicon ? Action::make('removeFavicon')
                ->label('Remove Favicon')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action('removeFavicon') : null,
            Action::make('save')
                ->label('Save Branding')
                ->icon('heroicon-o-check')
                ->action('save'),
        ]);
    }
}
