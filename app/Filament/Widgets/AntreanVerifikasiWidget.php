<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\VerifikasiSqffq;
use App\Models\AntreanVerifikasi;
use Filament\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\HtmlString;

class AntreanVerifikasiWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public ?int $kelasId = null;

    public function table(Table $table): Table
    {
        $kelasId = $this->kelasId ?? (request()->filled('kelas_id') ? request()->integer('kelas_id') : null);

        return $table
            ->query(
                AntreanVerifikasi::query()
                    ->with(['siswa', 'riwayatTerakhir'])
                    ->when($kelasId, fn ($query) => $query->whereHas('siswa', fn ($siswaQuery) => $siswaQuery->where('kelas_id', $kelasId)))
            )
            ->heading(new HtmlString('Antrean verifikasi <span class="sr-only">Antrean Verifikasi</span>'))
            ->headerActions([
                Action::make('lihat_semua')
                    ->label('Lihat semua')
                    ->link()
                    ->color('primary')
                    ->url(fn (): string => VerifikasiSqffq::getUrl()),
            ])
            ->columns([
                TextColumn::make('nama_lengkap')
                    ->label('Nama siswa')
                    ->weight(FontWeight::Bold)
                    ->sortable(false),

                TextColumn::make('nama_kelas')
                    ->label('Kelas')
                    ->sortable(false),

                TextColumn::make('sqffq_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state, AntreanVerifikasi $record): string => match ($state) {
                        'DRAF' => "SQ-FFQ sedang diisi ({$record->item_terisi}/{$record->item_total})",
                        'SELESAI' => 'Terverifikasi',
                        default => 'SQ-FFQ belum diisi',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'DRAF' => 'warning',
                        'SELESAI' => 'success',
                        default => 'warning',
                    }),

                TextColumn::make('sejak_menunggu')
                    ->label('Sejak menunggu')
                    ->state(fn (AntreanVerifikasi $record) => $record->riwayatTerakhir?->dibuat_pada ?? $record->siswa?->diubah_pada ?? $record->siswa?->dibuat_pada)
                    ->since()
                    ->description(fn (AntreanVerifikasi $record): ?string => ($record->riwayatTerakhir?->dibuat_pada ?? $record->siswa?->diubah_pada ?? $record->siswa?->dibuat_pada)?->translatedFormat('d M Y, H:i'))
                    ->placeholder('-'),
            ])
            ->recordActions([
                Action::make('isi_sqffq')
                    ->label(fn (AntreanVerifikasi $record): string => $record->sqffq_status === 'DRAF' ? 'Lanjutkan' : 'Isi SQ-FFQ')
                    ->button()
                    ->color('primary')
                    ->url(fn (AntreanVerifikasi $record): string => VerifikasiSqffq::getUrl(['siswa_id' => $record->siswa_id])),
            ])
            ->paginated(false)
            ->emptyStateHeading('Belum ada siswa dalam antrean verifikasi')
            ->emptyStateDescription('Siswa yang telah menyelesaikan pretest dan membaca seluruh materi edukasi wajib akan tampil di tabel ini.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }
}
