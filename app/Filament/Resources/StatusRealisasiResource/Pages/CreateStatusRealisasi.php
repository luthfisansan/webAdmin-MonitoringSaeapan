<?php

namespace App\Filament\Resources\StatusRealisasiResource\Pages;

use App\Filament\Resources\StatusRealisasiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStatusRealisasi extends CreateRecord
{
    protected static string $resource = StatusRealisasiResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
