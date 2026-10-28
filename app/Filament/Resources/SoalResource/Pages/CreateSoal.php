<?php

namespace App\Filament\Resources\SoalResource\Pages;

use App\Filament\Resources\SoalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSoal extends CreateRecord
{
    protected static string $resource = SoalResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
