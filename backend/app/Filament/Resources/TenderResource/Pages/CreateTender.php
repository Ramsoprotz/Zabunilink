<?php

namespace App\Filament\Resources\TenderResource\Pages;

use App\Filament\Resources\TenderResource;
use App\Jobs\SendTenderNotifications;
use Filament\Resources\Pages\CreateRecord;

class CreateTender extends CreateRecord
{
    protected static string $resource = TenderResource::class;

    protected function afterCreate(): void
    {
        SendTenderNotifications::dispatch($this->record);
    }
}
