<?php

namespace App\Filament\Resources\TenderApplicationResource\Pages;

use App\Filament\Resources\TenderApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderApplication extends EditRecord
{
    protected static string $resource = TenderApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
