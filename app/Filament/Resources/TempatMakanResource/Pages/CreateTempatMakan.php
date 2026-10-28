<?php

namespace App\Filament\Resources\TempatMakanResource\Pages;

use App\Filament\Resources\TempatMakanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTempatMakan extends CreateRecord
{
    protected static string $resource = TempatMakanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
