<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StatusRealisasiResource\Pages\CreateStatusRealisasi;
use App\Filament\Resources\StatusRealisasiResource\Pages\EditStatusRealisasi;
use App\Filament\Resources\StatusRealisasiResource\Pages\ListStatusRealisasi;
use App\Models\StatusRealisasi;
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

class StatusRealisasiResource extends Resource
{
    protected static ?string $model = StatusRealisasi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Status Realisasi';

    protected static ?string $modelLabel = 'Status Realisasi';

    protected static ?string $pluralModelLabel = 'Status Realisasi Food Record';

    protected static ?string $slug = 'status-realisasi';

    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Status Realisasi Food Record')
                    ->description('Kategori kesesuaian antara rencana sarapan malam hari dengan realisasi food record pagi hari.')
                    ->schema([
                        TextInput::make('kode')
                            ->label('Kode status')
                            ->placeholder('Contoh: SESUAI')
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->required(),

                        TextInput::make('nama')
                            ->label('Nama status realisasi')
                            ->placeholder('Contoh: Sesuai rencana')
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
                    ->color(fn (string $state): string => match ($state) {
                        'SESUAI' => 'success',
                        'DIUBAH', 'TAMBAHAN' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('nama')
                    ->label('Nama status realisasi')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('status_realisasi_id')
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
            'index' => ListStatusRealisasi::route('/'),
            'create' => CreateStatusRealisasi::route('/create'),
            'edit' => EditStatusRealisasi::route('/{record}/edit'),
        ];
    }
}
