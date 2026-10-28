<?php

namespace App\Filament\Resources\MateriEdukasiResource\Pages;

use App\Filament\Resources\MateriEdukasiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMateriEdukasi extends CreateRecord
{
    protected static string $resource = MateriEdukasiResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
