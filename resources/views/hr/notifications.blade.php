<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Notifications | {{ config('app.name', 'NU HRIS') }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#eceef1] text-slate-900 antialiased overflow-x-hidden overflow-y-auto">
    <div class="flex min-h-screen flex-col lg:flex-row">
        @include('partials.hr-sidebar', ['activeNav' => ''])

        <main class="min-h-screen flex-1">
            <header class="border-b border-slate-300 bg-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-[30px] font-bold leading-none text-[#1f2b5d]">Notifications</h1>
                        <p class="text-sm text-slate-500">National University HRIS</p>
                    </div>

                    @include('partials.header-actions')
                </div>
            </header>

            <section class="space-y-4 px-5 py-5 sm:px-6 sm:py-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-3xl font-bold text-[#1f2b5d]">Notifications</h2>
                        <p class="text-sm text-slate-500">All caught up!</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($notifications->where('is_read', false)->isNotEmpty())
                            <form method="POST" action="{{ route('notifications.read-all') }}">
                                @csrf
                                <button type="submit" class="rounded-lg border border-blue-300 bg-white px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">Read All</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('notifications.clear-all') }}" onsubmit="return confirm('Clear all notifications? This will remove them from your inbox.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear All</button>
                        </form>
                    </div>
                </div>

                <article class="rounded-xl border border-slate-300 bg-white shadow-sm">
                    @if ($notifications->isEmpty())
                        <div class="flex min-h-[320px] flex-col items-center justify-center px-6 py-10 text-center">
                            <svg class="h-14 w-14 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"></path>
                                <path d="M10 20a2 2 0 0 0 4 0"></path>
                            </svg>
                            <h3 class="mt-4 text-4xl font-bold text-slate-400">No Notifications Yet</h3>
                        </div>
                    @else
                        <div class="divide-y divide-slate-200">
                            @foreach ($notifications as $notification)
                                @php
                                    $announcement = $notification->announcement;
                                    $priorityLabel = $announcement?->priority_label ?? 'Medium';
                                    $priorityBadgeClass = $announcement?->priority_badge_class ?? 'bg-blue-100 text-blue-700';
                                @endphp
                                <a href="{{ route('notifications.open', $notification) }}" class="block px-6 py-4 transition hover:bg-slate-50 {{ $notification->is_read ? 'bg-slate-100' : 'bg-white' }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="text-sm font-semibold {{ $notification->is_read ? 'text-slate-500' : 'text-slate-900' }}">
                                            {{ $notification->title_text }}
                                        </p>
                                        <div class="flex items-center gap-2">
                                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $priorityBadgeClass }}">{{ $priorityLabel }}</span>
                                            @unless ($notification->is_read)
                                                <span class="mt-0.5 inline-flex shrink-0 items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-blue-700" aria-label="Unread notification">
                                                    <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                                                    Unread
                                                </span>
                                            @endunless
                                        </div>
                                    </div>
                                    <p class="mt-1 text-sm {{ $notification->is_read ? 'text-slate-500' : 'text-slate-700' }}">{{ $notification->content_text }}</p>
                                    <p class="mt-2 text-xs text-slate-400">{{ $notification->created_at->format('M d, Y h:i A') }}</p>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </article>
            </section>
        </main>
    </div>

    @auth
        @include('partials.logout-modal')
    @endauth
</body>
</html>
