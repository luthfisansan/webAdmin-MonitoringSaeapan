<?php

namespace App\Filament\Resources\StatusRealisasiResource\Pages;

use App\Filament\Resources\StatusRealisasiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStatusRealisasi extends EditRecord
{
    protected static string $resource = StatusRealisasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
