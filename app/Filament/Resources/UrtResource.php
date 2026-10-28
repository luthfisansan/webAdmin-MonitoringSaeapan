<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UrtResource\Pages\CreateUrt;
use App\Filament\Resources\UrtResource\Pages\EditUrt;
use App\Filament\Resources\UrtResource\Pages\ListUrt;
use App\Models\Urt;
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

class UrtResource extends Resource
{
    protected static ?string $model = Urt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Ukuran URT';

    protected static ?string $modelLabel = 'Ukuran Rumah Tangga (URT)';

    protected static ?string $pluralModelLabel = 'Ukuran Rumah Tangga (URT)';

    protected static ?string $slug = 'urt';

    protected static ?int $navigationSort = 17;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Satuan Ukuran Rumah Tangga (URT)')
                    ->description('Satuan takaran rumah tangga yang dipilih siswa pada planner dan food record (misal: piring, mangkuk, sendok makan, potong).')
                    ->schema([
                        TextInput::make('nama_urt')
                            ->label('Nama satuan URT')
                            ->placeholder('Contoh: sendok makan')
                            ->maxLength(40)
                            ->unique(ignoreRecord: true)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_urt')
                    ->label('Nama satuan URT')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('konversi_urt_count')
                    ->label('Bahan terkonversi')
                    ->counts('konversiUrt')
                    ->suffix(' bahan')
                    ->sortable(),
            ])
            ->defaultSort('nama_urt')
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
            'index' => ListUrt::route('/'),
            'create' => CreateUrt::route('/create'),
            'edit' => EditUrt::route('/{record}/edit'),
        ];
    }
}
