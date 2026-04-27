<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Employee Portal') | {{ config('app.name', 'NU HRIS') }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-8px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .nav-item { animation: slideInLeft 0.4s ease-out backwards; }
        .nav-item:nth-child(1) { animation-delay: 0.02s; }
        .nav-item:nth-child(2) { animation-delay: 0.06s; }
        .nav-item:nth-child(3) { animation-delay: 0.10s; }
        .nav-item:nth-child(4) { animation-delay: 0.14s; }
        .nav-item:nth-child(5) { animation-delay: 0.18s; }
        .nav-item:nth-child(6) { animation-delay: 0.22s; }
    </style>
</head>
<body class="min-h-screen bg-[#eceef1] text-slate-900 antialiased overflow-x-hidden overflow-y-auto">
    @php
        $name = auth()->user()->name ?? 'Martinez, Ian Isaac';
        $email = auth()->user()->email ?? 'martinezian@gmail.com';
        $unreadNotificationCount = auth()->check()
            ? auth()->user()->unreadAnnouncementNotifications()->count()
            : 0;
        $navItems = [
            ['label' => 'Dashboard', 'route' => 'employee.dashboard', 'match' => 'employee.dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['label' => 'Credentials', 'route' => 'employee.credentials', 'match' => 'employee.credentials*', 'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
            ['label' => 'Attendance & DTR', 'route' => 'employee.attendance', 'match' => 'employee.attendance', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => 'Leave Monitoring', 'route' => 'employee.leave', 'match' => 'employee.leave', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['label' => 'Notifications', 'route' => 'employee.notifications', 'match' => 'employee.notifications', 'icon' => 'M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0'],
            ['label' => 'Account', 'route' => 'employee.account', 'match' => 'employee.account', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
        ];
    @endphp

    <div class="min-h-screen lg:flex">
        <aside class="flex w-full flex-col bg-gradient-to-b from-blue-950 via-[#0b1a52] to-[#060f36] text-white lg:min-h-screen lg:w-72 shadow-xl">
            {{-- Logo --}}
            <div class="border-b border-white/10 px-6 py-6">
                <div class="flex items-center gap-3">
                    <div class="grid h-11 w-11 place-content-center rounded-xl bg-yellow-400 text-base font-black text-blue-950 shadow-lg shadow-yellow-400/20">N</div>
                    <div>
                        <p class="text-xl font-extrabold leading-none tracking-wide">NU Lipa</p>
                        <p class="mt-1 text-[11px] font-medium uppercase tracking-[0.15em] text-blue-200">HRIS Self-Service</p>
                    </div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto px-3 py-5" data-testid="employee-sidebar-nav">
                <ul class="space-y-1.5">
                    @foreach ($navItems as $item)
                        @php($active = request()->routeIs($item['match']))
                        <li class="nav-item">
                            <a href="{{ route($item['route']) }}"
                               class="group flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-[14px] font-semibold transition-all duration-200 {{ $active ? 'bg-yellow-400 text-blue-950 shadow-md shadow-yellow-400/20' : 'text-blue-100 hover:bg-white/10 hover:text-white hover:translate-x-0.5' }}"
                               data-testid="employee-nav-{{ \Illuminate\Support\Str::slug($item['label']) }}">
                                <svg class="h-[18px] w-[18px] shrink-0 {{ $active ? 'text-blue-950' : 'text-blue-200 group-hover:text-white' }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="{{ $item['icon'] }}"/>
                                </svg>
                                <span>{{ $item['label'] }}</span>
                                @if ($active)
                                    <span class="ml-auto h-2 w-2 rounded-full bg-blue-950"></span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="border-t border-white/10 px-6 py-4">
                <p class="text-[11px] font-medium uppercase tracking-widest text-blue-200/70">National University</p>
                <p class="mt-1 text-xs text-blue-100/80">HRIS · v1.0</p>
            </div>
        </aside>

        <main class="min-h-screen flex-1">
            <header class="border-b border-slate-300 bg-white px-6 py-4 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-[30px] font-bold leading-none text-blue-900">@yield('page_title')</h1>
                        <p class="text-sm text-slate-500">National University HRIS</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('employee.notifications') }}" class="relative rounded-full border border-slate-300 p-2 text-slate-500 hover:bg-slate-100" aria-label="Notifications {{ $unreadNotificationCount > 0 ? '('.$unreadNotificationCount.' unread)' : '' }}">
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
                            <summary class="flex cursor-pointer list-none items-center gap-1 rounded-full px-1 py-1 text-slate-600 hover:bg-slate-100">
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
                                    <p class="text-sm font-semibold">{{ $name }}</p>
                                    <p class="text-xs text-slate-500">{{ $email }}</p>
                                </div>

                                @auth
                                    <div class="p-2">
                                        <button type="button"
                                                class="logout-trigger w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-red-600 hover:bg-red-50"
                                                data-testid="employee-signout-button">
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
                </div>
            </header>

            <section class="space-y-5 px-5 py-5 sm:px-6 sm:py-6">
                @yield('content')
            </section>
        </main>
    </div>

    {{-- Sign-out confirmation modal --}}
    @auth
        @include('partials.logout-modal')
    @endauth

    @stack('scripts')
</body>
</html>
