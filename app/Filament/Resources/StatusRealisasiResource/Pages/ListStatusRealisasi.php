<?php

namespace App\Filament\Resources\StatusRealisasiResource\Pages;

use App\Filament\Resources\StatusRealisasiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStatusRealisasi extends ListRecords
{
    protected static string $resource = StatusRealisasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Status Realisasi'),
        ];
    }
}
