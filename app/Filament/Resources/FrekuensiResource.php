<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FrekuensiResource\Pages\CreateFrekuensi;
use App\Filament\Resources\FrekuensiResource\Pages\EditFrekuensi;
use App\Filament\Resources\FrekuensiResource\Pages\ListFrekuensi;
use App\Models\Frekuensi;
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

class FrekuensiResource extends Resource
{
    protected static ?string $model = Frekuensi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Frekuensi SQ-FFQ';

    protected static ?string $modelLabel = 'Frekuensi Konsumsi';

    protected static ?string $pluralModelLabel = 'Frekuensi Konsumsi (SQ-FFQ)';

    protected static ?string $slug = 'frekuensi';

    protected static ?int $navigationSort = 18;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pilihan Frekuensi Konsumsi (SQ-FFQ)')
                    ->description('Atur opsi frekuensi konsumsi pangan beserta konversi kali per minggu dan bobot skor konsumsi pangan.')
                    ->schema([
                        TextInput::make('kode')
                            ->label('Kode frekuensi')
                            ->placeholder('Contoh: 1_HARI')
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->required(),

                        TextInput::make('label')
                            ->label('Label tampilan')
                            ->placeholder('Contoh: 1x/hari')
                            ->maxLength(40)
                            ->required(),

                        TextInput::make('urutan')
                            ->label('Urutan tampil')
                            ->numeric()
                            ->minValue(1)
                            ->unique(ignoreRecord: true)
                            ->required(),

                        TextInput::make('kali_per_minggu')
                            ->label('Konversi kali per minggu')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('x / minggu')
                            ->required(),

                        TextInput::make('skor')
                            ->label('Bobot skor (food score)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01),
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
                    ->color('gray'),

                TextColumn::make('label')
                    ->label('Frekuensi')
                    ->weight(FontWeight::Bold)
                    ->searchable(),

                TextColumn::make('kali_per_minggu')
                    ->label('Kali / minggu')
                    ->suffix(' x/minggu')
                    ->sortable(),

                TextColumn::make('skor')
                    ->label('Bobot skor')
                    ->badge()
                    ->color('primary')
                    ->placeholder('-'),
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
            'index' => ListFrekuensi::route('/'),
            'create' => CreateFrekuensi::route('/create'),
            'edit' => EditFrekuensi::route('/{record}/edit'),
        ];
    }
}
