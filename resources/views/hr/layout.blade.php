<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'HR' }} | {{ config('app.name', 'NU HRIS') }}</title>
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
        .nav-item:nth-child(7) { animation-delay: 0.26s; }
    </style>
</head>
<body class="min-h-screen bg-[#eceef1] text-slate-900 antialiased overflow-x-hidden overflow-y-auto">
    @php
        $navItems = [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['key' => 'employees', 'label' => 'Employees', 'route' => 'employees.index', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 10-8 0 4 4 0 008 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z'],
            ['key' => 'credentials', 'label' => 'Credentials', 'route' => 'credentials.index', 'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
            ['key' => 'schedules', 'label' => 'Schedule Approval', 'route' => 'schedules.index', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['key' => 'timekeeping', 'label' => 'Time Keeping', 'route' => 'timekeeping.index', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['key' => 'wfh-monitoring', 'label' => 'WFH Monitoring', 'route' => 'wfh-monitoring.index', 'icon' => 'M4 7h16M4 12h16M4 17h10M7 3v18'],
            ['key' => 'leave', 'label' => 'Leave Management', 'route' => 'leave.index', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['key' => 'announcements', 'label' => 'Announcements', 'route' => 'announcements.index', 'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'],
        ];
    @endphp

    <div class="flex min-h-screen flex-col lg:flex-row">
        <aside class="w-full bg-gradient-to-b from-[#00386f] via-[#002d5a] to-[#001f42] text-white lg:sticky lg:top-0 lg:h-screen lg:w-72 lg:shrink-0 shadow-xl flex flex-col">
            {{-- Logo --}}
            <div class="border-b border-white/10 px-6 py-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#ffdc00] shadow-lg shadow-[#ffdc00]/20">
                        <span class="text-base font-black text-[#00386f]">NU</span>
                    </div>
                    <div>
                        <p class="text-xl font-extrabold leading-none tracking-wide">NU HRIS</p>
                        <p class="mt-1 text-[11px] font-medium uppercase tracking-[0.15em] text-blue-200">Human Resources</p>
                    </div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto px-3 py-5" data-testid="hr-sidebar-nav">
                <ul class="space-y-1.5">
                    @foreach ($navItems as $item)
                        @php($active = ($activeNav ?? '') === $item['key'])
                        <li class="nav-item">
                            <a href="{{ route($item['route']) }}"
                               class="group flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-[14px] font-semibold transition-all duration-200 {{ $active ? 'bg-[#ffdc00] text-[#00386f] shadow-md shadow-[#ffdc00]/20' : 'text-blue-100 hover:bg-white/10 hover:text-white hover:translate-x-0.5' }}"
                               data-testid="hr-nav-{{ $item['key'] }}">
                                <svg class="h-[18px] w-[18px] shrink-0 {{ $active ? 'text-[#00386f]' : 'text-blue-200 group-hover:text-white' }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="{{ $item['icon'] }}"/>
                                </svg>
                                <span>{{ $item['label'] }}</span>
                                @if ($active)
                                    <span class="ml-auto h-2 w-2 rounded-full bg-[#00386f]"></span>
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
            <header class="border-b border-slate-300 bg-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-[30px] font-bold leading-none text-[#1f2b5d]">{{ $pageHeading ?? ($pageTitle ?? 'HR') }}</h1>
                        <p class="text-sm text-slate-500">National University HRIS</p>
                    </div>

                    @include('partials.header-actions')
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
