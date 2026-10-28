<?php

namespace App\Filament\Resources\TempatMakanResource\Pages;

use App\Filament\Resources\TempatMakanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTempatMakan extends ListRecords
{
    protected static string $resource = TempatMakanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Tempat Makan'),
        ];
    }
}
