<?php

namespace App\Filament\Resources\MateriEdukasiResource\Pages;

use App\Filament\Resources\MateriEdukasiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMateriEdukasi extends ListRecords
{
    protected static string $resource = MateriEdukasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Materi Edukasi'),
        ];
    }
}
