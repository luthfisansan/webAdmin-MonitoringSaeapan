<?php

namespace App\Filament\Resources\KuesionerResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class SoalRelationManager extends RelationManager
{
    protected static string $relationship = 'soal';

    protected static ?string $title = 'Daftar Soal & Opsi Jawaban';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nomor')
                    ->label('Nomor urut soal')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required(),

                Textarea::make('teks_soal')
                    ->label('Teks pertanyaan')
                    ->rows(3)
                    ->required()
                    ->columnSpanFull(),

                Repeater::make('opsiJawaban')
                    ->label('Opsi jawaban')
                    ->relationship(
                        name: 'opsiJawaban',
                        modifyRecordsUsing: fn (Collection $records): Collection => $records->each->makeVisible('benar'),
                    )
                    ->schema([
                        TextInput::make('kode_opsi')
                            ->label('Kode')
                            ->placeholder('A')
                            ->maxLength(1)
                            ->required(),

                        TextInput::make('teks_opsi')
                            ->label('Teks opsi')
                            ->required()
                            ->columnSpan(2),

                        Toggle::make('benar')
                            ->label('Benar')
                            ->default(false)
                            ->inline(false),
                    ])
                    ->columns(4)
                    ->defaultItems(4)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('teks_soal')
            ->columns([
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
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Soal'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
