<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgramResource\Pages\CreateProgram;
use App\Filament\Resources\ProgramResource\Pages\EditProgram;
use App\Filament\Resources\ProgramResource\Pages\ListProgram;
use App\Models\Program;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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

class ProgramResource extends Resource
{
    protected static ?string $model = Program::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Program Intervensi';

    protected static ?string $modelLabel = 'Program Intervensi';

    protected static ?string $pluralModelLabel = 'Program Intervensi';

    protected static ?string $slug = 'program';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Periode Program Intervensi')
                    ->description('Atur jadwal dan durasi program pemantauan sarapan secara dinamis (misalnya 4 minggu / 1 bulan atau sesuai periode penelitian).')
                    ->schema([
                        TextInput::make('nama_program')
                            ->label('Nama program')
                            ->placeholder('Contoh: Program Sarapan Sehat Oktober 2026')
                            ->required()
                            ->maxLength(120)
                            ->columnSpanFull(),

                        DatePicker::make('tanggal_mulai')
                            ->label('Tanggal mulai')
                            ->default(fn () => now()->toDateString())
                            ->required(),

                        TextInput::make('durasi_minggu')
                            ->label('Durasi (minggu)')
                            ->numeric()
                            ->default(4)
                            ->minValue(1)
                            ->maxValue(52)
                            ->suffix('minggu')
                            ->helperText('Dapat diisi dinamis (misal 4 minggu untuk 1 bulan penuh).')
                            ->required(),

                        Toggle::make('aktif')
                            ->label('Program aktif')
                            ->helperText('Siswa yang selesai diverifikasi akan otomatis didaftarkan ke program aktif terbaru.')
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
                TextColumn::make('nama_program')
                    ->label('Nama program')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal_mulai')
                    ->label('Tanggal mulai')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('durasi_minggu')
                    ->label('Durasi')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (int $state): string => "{$state} minggu")
                    ->sortable(),

                TextColumn::make('peserta_program_count')
                    ->label('Peserta')
                    ->counts('pesertaProgram')
                    ->suffix(' siswa')
                    ->sortable(),

                TextColumn::make('aktif')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->defaultSort('tanggal_mulai', 'desc')
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
            'index' => ListProgram::route('/'),
            'create' => CreateProgram::route('/create'),
            'edit' => EditProgram::route('/{record}/edit'),
        ];
    }
}
