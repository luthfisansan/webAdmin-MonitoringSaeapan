<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BahanMakananResource\Pages\CreateBahanMakanan;
use App\Filament\Resources\BahanMakananResource\Pages\EditBahanMakanan;
use App\Filament\Resources\BahanMakananResource\Pages\ListBahanMakanan;
use App\Models\BahanMakanan;
use App\Models\KelompokBahan;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class BahanMakananResource extends Resource
{
    protected static ?string $model = BahanMakanan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCake;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Bahan Makanan';

    protected static ?string $modelLabel = 'Bahan Makanan';

    protected static ?string $pluralModelLabel = 'Bahan Makanan';

    protected static ?string $slug = 'bahan-makanan';

    protected static ?int $navigationSort = 16;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Bahan Makanan')
                    ->description('Daftar bahan makanan untuk formulir SQ-FFQ, rencana sarapan, dan food record.')
                    ->schema([
                        Select::make('kelompok_bahan_id')
                            ->label('Kelompok bahan')
                            ->relationship(
                                name: 'kelompokBahan',
                                titleAttribute: 'nama',
                            )
                            ->getOptionLabelFromRecordUsing(fn (KelompokBahan $record): string => "{$record->kode} · {$record->nama}")
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('nama_bahan')
                            ->label('Nama bahan makanan')
                            ->placeholder('Contoh: Nasi putih')
                            ->maxLength(80)
                            ->required(),

                        TextInput::make('urutan')
                            ->label('Urutan dalam kelompok')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),

                        TextInput::make('porsi_standar_gram')
                            ->label('Satu porsi standar (gram)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('gram')
                            ->placeholder('Contoh: 100'),

                        Toggle::make('aktif')
                            ->label('Bahan makanan aktif')
                            ->default(true)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Konversi Ukuran Rumah Tangga (URT)')
                    ->description('Tentukan berat dalam gram untuk setiap satuan Ukuran Rumah Tangga (URT) bahan ini.')
                    ->schema([
                        Repeater::make('konversiUrt')
                            ->label('Daftar konversi URT ke gram')
                            ->relationship('konversiUrt')
                            ->schema([
                                Select::make('urt_id')
                                    ->label('Satuan URT')
                                    ->relationship('urt', 'nama_urt')
                                    ->searchable()
                                    ->preload()
                                    ->distinct()
                                    ->required(),

                                TextInput::make('berat_gram')
                                    ->label('Berat setara (gram)')
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->suffix('gram')
                                    ->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Tambah Konversi URT')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kelompokBahan.nama')
                    ->label('Kelompok')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (string $state, BahanMakanan $record): string => "{$record->kelompokBahan?->kode} · {$state}")
                    ->sortable(),

                TextColumn::make('urutan')
                    ->label('No.')
                    ->sortable(),

                TextColumn::make('nama_bahan')
                    ->label('Nama bahan makanan')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('porsi_standar_gram')
                    ->label('Porsi standar')
                    ->suffix(' g')
                    ->placeholder('-'),

                TextColumn::make('konversi_urt_count')
                    ->label('Konversi URT')
                    ->counts('konversiUrt')
                    ->suffix(' satuan'),

                TextColumn::make('aktif')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->defaultSort('kelompok_bahan_id')
            ->filters([
                SelectFilter::make('kelompok_bahan_id')
                    ->label('Kelompok bahan')
                    ->relationship('kelompokBahan', 'nama'),
            ])
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
            'index' => ListBahanMakanan::route('/'),
            'create' => CreateBahanMakanan::route('/create'),
            'edit' => EditBahanMakanan::route('/{record}/edit'),
        ];
    }
}
