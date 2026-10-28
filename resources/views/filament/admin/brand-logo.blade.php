@php
    $isAuthPage = request()->routeIs('filament.admin.auth.*');
@endphp

@if ($isAuthPage)
    <div class="dbp-auth-brand">
        <div class="dbp-brand-icon dbp-brand-icon-lg">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="4"></circle>
                <path d="M12 3v2"></path>
                <path d="M12 19v2"></path>
                <path d="m5.64 5.64 1.41 1.41"></path>
                <path d="m16.95 16.95 1.41 1.41"></path>
                <path d="M3 12h2"></path>
                <path d="M19 12h2"></path>
                <path d="m5.64 18.36 1.41-1.41"></path>
                <path d="m16.95 7.05 1.41-1.41"></path>
            </svg>
        </div>
        <div class="dbp-auth-brand-text">
            <span class="dbp-auth-brand-title">Digital Breakfast Planner</span>
            <span class="dbp-auth-brand-subtitle">Admin petugas gizi</span>
        </div>
    </div>
@else
    <div class="dbp-sidebar-brand">
        <div class="dbp-brand-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="4"></circle>
                <path d="M12 3v2"></path>
                <path d="M12 19v2"></path>
                <path d="m5.64 5.64 1.41 1.41"></path>
                <path d="m16.95 16.95 1.41 1.41"></path>
                <path d="M3 12h2"></path>
                <path d="M19 12h2"></path>
                <path d="m5.64 18.36 1.41-1.41"></path>
                <path d="m16.95 7.05 1.41-1.41"></path>
            </svg>
        </div>
        <div class="dbp-sidebar-brand-text">
            <span class="dbp-sidebar-brand-title"><span class="sr-only">Digital </span>Breakfast Planner</span>
            <span class="dbp-sidebar-brand-subtitle">Admin petugas gizi</span>
        </div>
    </div>
@endif

