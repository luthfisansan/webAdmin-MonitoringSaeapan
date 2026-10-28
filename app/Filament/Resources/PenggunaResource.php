<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenggunaResource\Pages\CreatePengguna;
use App\Filament\Resources\PenggunaResource\Pages\EditPengguna;
use App\Filament\Resources\PenggunaResource\Pages\ListPengguna;
use App\Models\Pengguna;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class PenggunaResource extends Resource
{
    protected static ?string $model = Pengguna::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Akun Pengguna';

    protected static ?string $modelLabel = 'Akun Pengguna';

    protected static ?string $pluralModelLabel = 'Akun Pengguna';

    protected static ?string $slug = 'pengguna';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Akun Pengguna & Petugas')
                    ->description('Kelola akun administrator, petugas gizi, maupun akun siswa.')
                    ->schema([
                        TextInput::make('nama_lengkap')
                            ->label('Nama lengkap')
                            ->placeholder('Contoh: Petugas Gizi 1')
                            ->maxLength(120)
                            ->required(),

                        TextInput::make('email')
                            ->label('Alamat email')
                            ->email()
                            ->placeholder('Contoh: petugas@example.com')
                            ->maxLength(150)
                            ->unique(ignoreRecord: true)
                            ->required(),

                        Select::make('peran_id')
                            ->label('Peran akses')
                            ->relationship('peran', 'nama')
                            ->preload()
                            ->required(),

                        TextInput::make('kata_sandi_hash')
                            ->label('Kata sandi')
                            ->password()
                            ->revealable()
                            ->placeholder('Minimal 8 karakter')
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create'),

                        Toggle::make('aktif')
                            ->label('Akun aktif')
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
                TextColumn::make('nama_lengkap')
                    ->label('Nama lengkap')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('peran.nama')
                    ->label('Peran')
                    ->badge()
                    ->color(fn (Pengguna $record): string => match ($record->peran?->kode) {
                        'ADMIN' => 'primary',
                        'PETUGAS' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('aktif')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('peran_id')
                    ->label('Peran')
                    ->relationship('peran', 'nama'),
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
            'index' => ListPengguna::route('/'),
            'create' => CreatePengguna::route('/create'),
            'edit' => EditPengguna::route('/{record}/edit'),
        ];
    }
}
