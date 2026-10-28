<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TempatMakanResource\Pages\CreateTempatMakan;
use App\Filament\Resources\TempatMakanResource\Pages\EditTempatMakan;
use App\Filament\Resources\TempatMakanResource\Pages\ListTempatMakan;
use App\Models\TempatMakan;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class TempatMakanResource extends Resource
{
    protected static ?string $model = TempatMakan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Tempat Makan';

    protected static ?string $modelLabel = 'Tempat Makan';

    protected static ?string $pluralModelLabel = 'Tempat Makan';

    protected static ?string $slug = 'tempat-makan';

    protected static ?int $navigationSort = 19;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Referensi Tempat Sarapan')
                    ->description('Daftar pilihan lokasi sarapan untuk rencana sarapan dan food record (misal: Rumah, Sekolah, Jajan).')
                    ->schema([
                        TextInput::make('kode')
                            ->label('Kode tempat')
                            ->placeholder('Contoh: RUMAH')
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->required(),

                        TextInput::make('nama')
                            ->label('Nama tempat makan')
                            ->placeholder('Contoh: Rumah')
                            ->maxLength(40)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')
                    ->label('Kode')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('nama')
                    ->label('Nama tempat makan')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('tempat_makan_id')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTempatMakan::route('/'),
            'create' => CreateTempatMakan::route('/create'),
            'edit' => EditTempatMakan::route('/{record}/edit'),
        ];
    }
}
