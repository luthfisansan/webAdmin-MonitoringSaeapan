<?php

namespace App\Filament\Resources\BahanMakananResource\Pages;

use App\Filament\Resources\BahanMakananResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBahanMakanan extends EditRecord
{
    protected static string $resource = BahanMakananResource::class;

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
