<?php

namespace App\Filament\Pages\Auth;

use App\Models\Pengguna;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable|null
    {
        return 'Masuk ke Panel Admin';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Kelola verifikasi SQ-FFQ dan pemantauan program sarapan siswa';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
            ]);
    }

    protected function throwFailureValidationException(): never
    {
        $data = $this->form->getState();
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        $user = Pengguna::query()
            ->with('peran')
            ->whereRaw('lower(email) = ?', [$email])
            ->first();

        $pesan = 'Email atau kata sandi yang Anda masukkan salah. Silakan periksa kembali.';

        if ($user) {
            if ($user->peran?->kode === 'SISWA') {
                $pesan = 'Akun ini terdaftar sebagai siswa. Panel admin hanya dapat diakses oleh petugas gizi dan administrator.';
            } elseif (! $user->aktif) {
                $pesan = 'Akun Anda sedang dinonaktifkan. Silakan hubungi administrator untuk mengaktifkannya.';
            }
        }

        throw ValidationException::withMessages([
            'data.email' => $pesan,
        ]);
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title('Terlalu Banyak Percobaan Masuk')
            ->body("Demi keamanan, percobaan login dibatasi. Silakan tunggu {$exception->secondsUntilAvailable} detik sebelum mencoba kembali.")
            ->danger();
    }
}
