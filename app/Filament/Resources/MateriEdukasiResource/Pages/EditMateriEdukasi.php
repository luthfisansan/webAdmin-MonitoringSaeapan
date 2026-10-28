<?php

namespace App\Filament\Resources\MateriEdukasiResource\Pages;

use App\Filament\Resources\MateriEdukasiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMateriEdukasi extends EditRecord
{
    protected static string $resource = MateriEdukasiResource::class;

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
