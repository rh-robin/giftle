<?php

namespace App\Filament\Resources\ServiceDetailsResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ServiceDetailsResource;

class ListServiceDetails extends ListRecords
{
    protected static string $resource = ServiceDetailsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
