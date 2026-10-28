<?php

namespace App\Filament\Resources\KelompokBahanResource\Pages;

use App\Filament\Resources\KelompokBahanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKelompokBahan extends CreateRecord
{
    protected static string $resource = KelompokBahanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
