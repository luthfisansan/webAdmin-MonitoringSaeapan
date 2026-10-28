<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MateriEdukasiResource\Pages\CreateMateriEdukasi;
use App\Filament\Resources\MateriEdukasiResource\Pages\EditMateriEdukasi;
use App\Filament\Resources\MateriEdukasiResource\Pages\ListMateriEdukasi;
use App\Models\MateriEdukasi;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
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

class MateriEdukasiResource extends Resource
{
    protected static ?string $model = MateriEdukasi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Materi Edukasi';

    protected static ?string $modelLabel = 'Materi Edukasi';

    protected static ?string $pluralModelLabel = 'Materi Edukasi';

    protected static ?string $slug = 'materi-edukasi';

    protected static ?int $navigationSort = 14;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konten Materi Edukasi')
                    ->description('Materi yang wajib dibaca oleh siswa setelah menyelesaikan pretest sebelum lanjut ke verifikasi SQ-FFQ.')
                    ->schema([
                        TextInput::make('judul')
                            ->label('Judul materi')
                            ->placeholder('Contoh: Pentingnya Sarapan Bergizi Seimbang')
                            ->required()
                            ->maxLength(150),

                        TextInput::make('urutan')
                            ->label('Urutan tampil')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),

                        Textarea::make('ringkasan')
                            ->label('Ringkasan singkat')
                            ->placeholder('Ringkasan 1-2 kalimat untuk kartu daftar materi di aplikasi mobile...')
                            ->rows(2)
                            ->maxLength(255)
                            ->columnSpanFull(),

                        RichEditor::make('konten')
                            ->label('Isi materi edukasi')
                            ->required()
                            ->columnSpanFull(),

                        Toggle::make('wajib')
                            ->label('Materi wajib')
                            ->helperText('Jika aktif, siswa wajib menyelesaikan bacaan ini agar status naik ke Menunggu Verifikasi.')
                            ->default(true),

                        Toggle::make('aktif')
                            ->label('Materi aktif')
                            ->default(true),
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

                TextColumn::make('judul')
                    ->label('Judul materi')
                    ->weight(FontWeight::Bold)
                    ->description(fn (MateriEdukasi $record): ?string => $record->ringkasan)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('wajib')
                    ->label('Sifat')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Wajib' : 'Opsional')
                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray'),

                TextColumn::make('aktif')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
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
            'index' => ListMateriEdukasi::route('/'),
            'create' => CreateMateriEdukasi::route('/create'),
            'edit' => EditMateriEdukasi::route('/{record}/edit'),
        ];
    }
}
