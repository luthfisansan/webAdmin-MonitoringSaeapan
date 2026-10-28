<?php

namespace App\Filament\Resources\KuesionerResource\Pages;

use App\Filament\Resources\KuesionerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKuesioner extends ListRecords
{
    protected static string $resource = KuesionerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Kuesioner'),
        ];
    }
}
