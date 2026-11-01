<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SekolahResource\Pages\CreateSekolah;
use App\Filament\Resources\SekolahResource\Pages\EditSekolah;
use App\Filament\Resources\SekolahResource\Pages\ListSekolah;
use App\Models\Sekolah;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SekolahResource extends Resource
{
    protected static ?string $model = Sekolah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Sekolah';

    protected static ?string $modelLabel = 'Sekolah';

    protected static ?string $pluralModelLabel = 'Sekolah';

    protected static ?string $slug = 'sekolah';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Sekolah')
                    ->description('Kelola data sekolah mitra penelitian monitoring sarapan gizi siswa.')
                    ->schema([
                        TextInput::make('nama_sekolah')
                            ->label('Nama sekolah')
                            ->placeholder('Contoh: SMP Negeri 1 Jakarta')
                            ->required()
                            ->maxLength(150),

                        TextInput::make('npsn')
                            ->label('NPSN')
                            ->placeholder('Contoh: 10203040')
                            ->maxLength(20),

                        TextInput::make('telepon')
                            ->label('Nomor telepon / kontak')
                            ->placeholder('Contoh: 021-1234567')
                            ->tel()
                            ->maxLength(30),

                        Textarea::make('alamat')
                            ->label('Alamat lengkap')
                            ->placeholder('Contoh: Jl. Budi Utomo No. 7, Jakarta Pusat')
                            ->rows(3)
                            ->columnSpanFull(),

                        Toggle::make('aktif')
                            ->label('Sekolah aktif')
                            ->helperText('Hanya sekolah aktif yang dapat dipilih saat pembuatan kelas dan pendaftaran.')
                            ->default(true)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_sekolah')
                    ->label('Nama sekolah')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('npsn')
                    ->label('NPSN')
                    ->badge()
                    ->color('gray')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kelas_count')
                    ->label('Jumlah kelas')
                    ->counts('kelas')
                    ->suffix(' kelas')
                    ->sortable(),

                TextColumn::make('alamat')
                    ->label('Alamat')
                    ->limit(40)
                    ->color('gray')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('aktif')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
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
            'index' => ListSekolah::route('/'),
            'create' => CreateSekolah::route('/create'),
            'edit' => EditSekolah::route('/{record}/edit'),
        ];
    }
}

