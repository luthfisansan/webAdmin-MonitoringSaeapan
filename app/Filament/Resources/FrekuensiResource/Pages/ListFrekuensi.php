<?php

namespace App\Filament\Resources\FrekuensiResource\Pages;

use App\Filament\Resources\FrekuensiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFrekuensi extends ListRecords
{
    protected static string $resource = FrekuensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Frekuensi'),
        ];
    }
}
