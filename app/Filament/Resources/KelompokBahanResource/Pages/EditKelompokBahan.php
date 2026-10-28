<?php

namespace App\Filament\Resources\KelompokBahanResource\Pages;

use App\Filament\Resources\KelompokBahanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKelompokBahan extends EditRecord
{
    protected static string $resource = KelompokBahanResource::class;

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
