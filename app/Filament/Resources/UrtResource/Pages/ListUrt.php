<?php

namespace App\Filament\Resources\UrtResource\Pages;

use App\Filament\Resources\UrtResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUrt extends ListRecords
{
    protected static string $resource = UrtResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Satuan URT'),
        ];
    }
}
