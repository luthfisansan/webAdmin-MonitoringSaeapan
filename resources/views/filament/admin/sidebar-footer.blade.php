@php
    $user = filament()->auth()->user();
    $userName = $user ? filament()->getUserName($user) : 'Peneliti';
    $initial = mb_strtoupper(mb_substr(trim((string) $userName), 0, 1)) ?: 'P';
@endphp

@if ($user)
    <div class="dbp-sidebar-user">
        <div class="dbp-sidebar-user-avatar" aria-hidden="true">
            {{ $initial }}
        </div>
        <div class="dbp-sidebar-user-info">
            <div class="dbp-sidebar-user-name">{{ $userName }}</div>
            <form method="POST" action="{{ filament()->getLogoutUrl() }}" class="dbp-sidebar-logout-form">
                @csrf
                <button type="submit" class="dbp-sidebar-logout-btn">
                    Keluar
                </button>
            </form>
        </div>
    </div>
@endif

