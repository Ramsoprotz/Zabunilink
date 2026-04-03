<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        // Only admins can delete users
        if (!auth()->user()?->isAdmin()) {
            return [];
        }

        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        // Support agents cannot edit admin or other support users
        if (auth()->user()?->isSupport() && in_array($this->record->role, ['admin', 'support'])) {
            abort(403, 'You cannot edit staff accounts.');
        }
    }
}
