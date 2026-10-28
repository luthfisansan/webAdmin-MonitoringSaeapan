<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KuesionerResource\Pages\CreateKuesioner;
use App\Filament\Resources\KuesionerResource\Pages\EditKuesioner;
use App\Filament\Resources\KuesionerResource\Pages\ListKuesioner;
use App\Filament\Resources\KuesionerResource\RelationManagers\SoalRelationManager;
use App\Models\Kuesioner;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
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

class KuesionerResource extends Resource
{
    protected static ?string $model = Kuesioner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Kuesioner';

    protected static ?string $modelLabel = 'Kuesioner';

    protected static ?string $pluralModelLabel = 'Kuesioner';

    protected static ?string $slug = 'kuesioner';

    protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pengaturan Kuesioner')
                    ->description('Kelola paket kuesioner Pretest dan Post test pengetahuan gizi.')
                    ->schema([
                        Select::make('jenis')
                            ->label('Jenis kuesioner')
                            ->options([
                                'PRETEST' => 'Pretest',
                                'POSTTEST' => 'Post test',
                            ])
                            ->required(),

                        TextInput::make('judul')
                            ->label('Judul kuesioner')
                            ->placeholder('Contoh: Pretest Pengetahuan Gizi Sarapan')
                            ->required()
                            ->maxLength(150),

                        Toggle::make('aktif')
                            ->label('Kuesioner aktif')
                            ->helperText('Mengaktifkan kuesioner ini akan menonaktifkan kuesioner lain dengan jenis yang sama.')
                            ->default(true)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'PRETEST' ? 'warning' : 'primary')
                    ->sortable(),

                TextColumn::make('judul')
                    ->label('Judul kuesioner')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('soal_count')
                    ->label('Jumlah soal')
                    ->counts('soal')
                    ->suffix(' butir')
                    ->sortable(),

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

    public static function getRelations(): array
    {
        return [
            SoalRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKuesioner::route('/'),
            'create' => CreateKuesioner::route('/create'),
            'edit' => EditKuesioner::route('/{record}/edit'),
        ];
    }
}
