<?php

namespace App\Filament\Resources\UrtResource\Pages;

use App\Filament\Resources\UrtResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUrt extends EditRecord
{
    protected static string $resource = UrtResource::class;

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
