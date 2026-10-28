<?php

namespace App\Filament\Resources\KelompokBahanResource\Pages;

use App\Filament\Resources\KelompokBahanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKelompokBahan extends ListRecords
{
    protected static string $resource = KelompokBahanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Kelompok Bahan'),
        ];
    }
}
