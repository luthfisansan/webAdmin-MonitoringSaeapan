<?php

namespace App\Filament\Resources\SoalResource\Pages;

use App\Filament\Resources\SoalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSoal extends ListRecords
{
    protected static string $resource = SoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Soal'),
        ];
    }
}
