@php
    $unreadNotificationCount = auth()->check()
        ? auth()->user()->unreadAnnouncementNotifications()->count()
        : 0;
@endphp

<div class="flex items-center gap-3">
    <a href="{{ route('notifications.index') }}" class="relative rounded-full border border-slate-300 p-2 text-slate-500 hover:bg-slate-100" aria-label="Notifications {{ $unreadNotificationCount > 0 ? '('.$unreadNotificationCount.' unread)' : '' }}" data-testid="header-notifications">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"></path>
            <path d="M10 20a2 2 0 0 0 4 0"></path>
        </svg>
        @if ($unreadNotificationCount > 0)
            <span
                class="absolute right-0 top-0 block h-2.5 w-2.5 -translate-y-1/4 translate-x-1/4 rounded-full"
                style="background-color: #2563eb;"
            ></span>
        @endif
    </a>

    <details class="group relative">
        <summary class="flex cursor-pointer list-none items-center gap-1 rounded-full px-1 py-1 text-slate-600 hover:bg-slate-100" data-testid="header-user-menu">
            <svg class="h-8 w-8 text-[#1f2b8b]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <circle cx="12" cy="8" r="4"></circle>
                <path d="M4 20c1.5-4 5-6 8-6s6.5 2 8 6"></path>
            </svg>
            <svg class="h-4 w-4 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor">
                <path d="M5.25 7.5 10 12.25 14.75 7.5" />
            </svg>
        </summary>

        <div class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white text-slate-900 shadow-xl">
            <div class="border-b border-slate-200 px-4 py-3">
                <p class="text-sm font-semibold">{{ auth()->user()->name ?? 'Isaac Ian Martinez' }}</p>
                <p class="text-xs text-slate-500">{{ auth()->user()->email ?? 'martinezian@gmail.com' }}</p>
            </div>

            @auth
                <div class="p-2">
                    <button type="button"
                            class="logout-trigger w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-red-600 hover:bg-red-50"
                            data-testid="header-signout-button">
                        Sign out
                    </button>
                </div>
            @else
                <div class="p-2">
                    <span class="block w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700">Sign out</span>
                </div>
            @endauth
        </div>
    </details>
</div>
