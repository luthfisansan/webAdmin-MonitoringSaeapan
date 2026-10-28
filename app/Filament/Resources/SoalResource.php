<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SoalResource\Pages\CreateSoal;
use App\Filament\Resources\SoalResource\Pages\EditSoal;
use App\Filament\Resources\SoalResource\Pages\ListSoal;
use App\Models\Kuesioner;
use App\Models\Soal;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class SoalResource extends Resource
{
    protected static ?string $model = Soal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Bank Soal';

    protected static ?string $modelLabel = 'Soal Kuesioner';

    protected static ?string $pluralModelLabel = 'Bank Soal';

    protected static ?string $slug = 'soal';

    protected static ?int $navigationSort = 13;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Butir Soal Kuesioner')
                    ->description('Tulis pertanyaan dan tentukan urutan nomor soal pada kuesioner terkait.')
                    ->schema([
                        Select::make('kuesioner_id')
                            ->label('Kuesioner')
                            ->relationship(
                                name: 'kuesioner',
                                titleAttribute: 'judul',
                            )
                            ->getOptionLabelFromRecordUsing(fn (Kuesioner $record): string => "[{$record->jenis}] {$record->judul}")
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('nomor')
                            ->label('Nomor urut soal')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),

                        Textarea::make('teks_soal')
                            ->label('Teks pertanyaan')
                            ->placeholder('Masukkan bunyi pertanyaan pretest / post test...')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Opsi Jawaban')
                    ->description('Tambahkan pilihan jawaban (A, B, C, D) dan tandai tepat satu jawaban yang benar.')
                    ->schema([
                        Repeater::make('opsiJawaban')
                            ->label('Daftar opsi jawaban')
                            ->relationship(
                                name: 'opsiJawaban',
                                modifyRecordsUsing: fn (Collection $records): Collection => $records->each->makeVisible('benar'),
                            )
                            ->schema([
                                TextInput::make('kode_opsi')
                                    ->label('Kode (A-E)')
                                    ->placeholder('A')
                                    ->maxLength(1)
                                    ->required(),

                                TextInput::make('teks_opsi')
                                    ->label('Teks pilihan jawaban')
                                    ->placeholder('Isi pilihan jawaban...')
                                    ->required()
                                    ->columnSpan(2),

                                Toggle::make('benar')
                                    ->label('Jawaban benar')
                                    ->default(false)
                                    ->inline(false),
                            ])
                            ->columns(4)
                            ->defaultItems(4)
                            ->addActionLabel('Tambah Opsi Jawaban')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kuesioner.jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'PRETEST' ? 'warning' : 'primary')
                    ->sortable(),

                TextColumn::make('nomor')
                    ->label('No.')
                    ->sortable(),

                TextColumn::make('teks_soal')
                    ->label('Pertanyaan')
                    ->weight(FontWeight::Bold)
                    ->limit(80)
                    ->searchable(),

                TextColumn::make('opsi_jawaban_count')
                    ->label('Opsi')
                    ->counts('opsiJawaban')
                    ->suffix(' opsi'),
            ])
            ->defaultSort('nomor')
            ->filters([
                SelectFilter::make('kuesioner_id')
                    ->label('Kuesioner')
                    ->relationship('kuesioner', 'judul'),
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
            'index' => ListSoal::route('/'),
            'create' => CreateSoal::route('/create'),
            'edit' => EditSoal::route('/{record}/edit'),
        ];
    }
}
