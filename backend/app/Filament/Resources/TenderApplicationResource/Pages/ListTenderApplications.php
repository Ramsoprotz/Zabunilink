<?php

namespace App\Filament\Resources\TenderApplicationResource\Pages;

use App\Filament\Resources\TenderApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenderApplications extends ListRecords
{
    protected static string $resource = TenderApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
