<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KelompokBahanResource\Pages\CreateKelompokBahan;
use App\Filament\Resources\KelompokBahanResource\Pages\EditKelompokBahan;
use App\Filament\Resources\KelompokBahanResource\Pages\ListKelompokBahan;
use App\Models\KelompokBahan;
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

class KelompokBahanResource extends Resource
{
    protected static ?string $model = KelompokBahan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Kelompok Bahan';

    protected static ?string $modelLabel = 'Kelompok Bahan';

    protected static ?string $pluralModelLabel = 'Kelompok Bahan Makanan';

    protected static ?string $slug = 'kelompok-bahan';

    protected static ?int $navigationSort = 15;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kelompok Bahan Makanan (SQ-FFQ)')
                    ->description('Kategori kelompok bahan makanan pada formulir wawancara SQ-FFQ (kode A sampai Z).')
                    ->schema([
                        TextInput::make('kode')
                            ->label('Kode kelompok')
                            ->placeholder('Contoh: A')
                            ->maxLength(1)
                            ->unique(ignoreRecord: true)
                            ->required(),

                        TextInput::make('urutan')
                            ->label('Urutan tampil')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->unique(ignoreRecord: true)
                            ->required(),

                        TextInput::make('nama')
                            ->label('Nama kelompok bahan')
                            ->placeholder('Contoh: Bahan makanan pokok')
                            ->maxLength(60)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('urutan')
                    ->label('No.')
                    ->sortable(),

                TextColumn::make('kode')
                    ->label('Kode')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('nama')
                    ->label('Nama kelompok bahan')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('bahan_makanan_count')
                    ->label('Jumlah bahan')
                    ->counts('bahanMakanan')
                    ->suffix(' bahan')
                    ->sortable(),
            ])
            ->defaultSort('urutan')
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
            'index' => ListKelompokBahan::route('/'),
            'create' => CreateKelompokBahan::route('/create'),
            'edit' => EditKelompokBahan::route('/{record}/edit'),
        ];
    }
}
