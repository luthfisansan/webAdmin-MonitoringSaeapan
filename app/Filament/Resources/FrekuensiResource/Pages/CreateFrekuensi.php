<?php

namespace App\Filament\Resources\FrekuensiResource\Pages;

use App\Filament\Resources\FrekuensiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFrekuensi extends CreateRecord
{
    protected static string $resource = FrekuensiResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
