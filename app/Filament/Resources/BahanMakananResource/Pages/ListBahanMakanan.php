<?php

namespace App\Filament\Resources\BahanMakananResource\Pages;

use App\Filament\Resources\BahanMakananResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBahanMakanan extends ListRecords
{
    protected static string $resource = BahanMakananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Bahan Makanan'),
        ];
    }
}
