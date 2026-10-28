<?php

namespace App\Filament\Resources\UrtResource\Pages;

use App\Filament\Resources\UrtResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUrt extends CreateRecord
{
    protected static string $resource = UrtResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
