<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') | {{ config('app.name', 'NU HRIS') }}</title>
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
        $sections = [
            [
                'label' => 'Dashboard',
                'route' => 'admin.dashboard',
                'match' => 'admin.dashboard',
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            ],
            [
                'label' => 'Credential Management',
                'route' => 'admin.credentials.index',
                'match' => 'admin.credentials.*',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            ],
            [
                'label' => 'DTR Management',
                'route' => 'admin.dtr.index',
                'match' => 'admin.dtr.*',
                'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label' => 'WFH Monitoring',
                'route' => 'admin.wfh-monitoring.index',
                'match' => 'admin.wfh-monitoring.*',
                'icon' => 'M5 13l4 4L19 7',
            ],
            [
                'label' => 'Leave Management',
                'route' => 'admin.leave.index',
                'match' => 'admin.leave.*',
                'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            ],
            [
                'label' => 'Schedule Management',
                'route' => 'admin.schedules.index',
                'match' => 'admin.schedules.*',
                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            ],
            [
                'label' => 'Employees',
                'route' => 'admin.employees.index',
                'match' => 'admin.employees.*',
                'icon' => 'M16 7a4 4 0 10-8 0 4 4 0 008 0z M4 21v-2a4 4 0 014-4h8a4 4 0 014 4v2',
            ],
            [
                'label' => 'Role Management',
                'route' => 'admin.roles.index',
                'match' => 'admin.roles.*',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 7H9',
            ],
            [
                'label' => 'Activity Logs',
                'route' => 'admin.activity-logs.index',
                'match' => 'admin.activity-logs.*',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            ],
        ];
    @endphp

    <div class="min-h-screen lg:flex">
        <aside class="flex w-full flex-col bg-gradient-to-b from-[#0a3f79] via-[#083b72] to-[#062a54] text-white lg:sticky lg:top-0 lg:h-screen lg:w-72 lg:shrink-0 shadow-xl">
            {{-- Logo --}}
            <div class="border-b border-white/10 px-6 py-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#ffdc00] shadow-lg shadow-[#ffdc00]/20">
                        <span class="text-base font-black text-[#0a3f79]">NU</span>
                    </div>
                    <div>
                        <p class="text-xl font-extrabold leading-none tracking-wide">NU HRIS</p>
                        <p class="mt-1 text-[11px] font-medium uppercase tracking-[0.15em] text-blue-200">Admin Panel</p>
                    </div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 space-y-1.5 overflow-y-auto px-3 py-5" id="admin-sidebar-nav" data-testid="admin-sidebar-nav">
                @foreach ($sections as $section)
                    @php($active = request()->routeIs($section['match']))
                    <div class="admin-section nav-item" data-has-children="{{ ! empty($section['children']) ? 'true' : 'false' }}">
                        @if (! empty($section['children']))
                            <button type="button"
                                    class="admin-section-toggle group flex w-full items-center justify-between rounded-xl px-3.5 py-2.5 text-left text-[14px] font-semibold transition-all duration-200 {{ $active ? 'bg-[#ffdc00] text-[#0a3f79] shadow-md shadow-[#ffdc00]/20' : 'text-blue-100 hover:bg-white/10 hover:text-white hover:translate-x-0.5' }}"
                                    data-expanded="{{ $active ? 'true' : 'false' }}"
                                    aria-expanded="{{ $active ? 'true' : 'false' }}"
                                    data-testid="admin-nav-{{ \Illuminate\Support\Str::slug($section['label']) }}">
                                <span class="flex items-center gap-3">
                                    <svg class="h-[18px] w-[18px] shrink-0 {{ $active ? 'text-[#0a3f79]' : 'text-blue-200 group-hover:text-white' }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $section['icon'] }}"/></svg>
                                    <span>{{ $section['label'] }}</span>
                                </span>
                                <svg class="h-4 w-4 shrink-0 transition-transform duration-200 {{ $active ? 'rotate-180' : '' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M5.25 7.5 10 12.25 14.75 7.5" />
                                </svg>
                            </button>

                            <div class="admin-section-children ml-4 mt-1.5 space-y-1 border-l-2 border-white/10 pl-3 {{ $active ? '' : 'hidden' }}">
                                @foreach ($section['children'] as $child)
                                    @php($childActive = request()->routeIs($child['match']))
                                    <a href="{{ route($child['route']) }}"
                                       class="flex items-center gap-2 rounded-lg px-3 py-2 text-[13px] transition-all duration-150 {{ $childActive ? 'bg-white/15 font-semibold text-white' : 'text-blue-200/90 hover:bg-white/10 hover:text-white hover:translate-x-0.5' }}"
                                       data-testid="admin-subnav-{{ \Illuminate\Support\Str::slug($child['label']) }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $childActive ? 'bg-[#ffdc00]' : 'bg-white/30' }}"></span>
                                        {{ $child['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <a href="{{ route($section['route']) }}"
                               class="group flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-[14px] font-semibold transition-all duration-200 {{ $active ? 'bg-[#ffdc00] text-[#0a3f79] shadow-md shadow-[#ffdc00]/20' : 'text-blue-100 hover:bg-white/10 hover:text-white hover:translate-x-0.5' }}"
                               data-testid="admin-nav-{{ \Illuminate\Support\Str::slug($section['label']) }}">
                                <svg class="h-[18px] w-[18px] shrink-0 {{ $active ? 'text-[#0a3f79]' : 'text-blue-200 group-hover:text-white' }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $section['icon'] }}"/></svg>
                                <span>{{ $section['label'] }}</span>
                            </a>
                        @endif
                    </div>
                @endforeach
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
                        <h1 class="text-[30px] font-bold leading-none text-[#1f2b5d]">{{ $pageHeading ?? ($pageTitle ?? 'Admin Dashboard') }}</h1>
                        <p class="text-sm text-slate-500">National University HRIS</p>
                    </div>

                    @include('partials.header-actions')
                </div>
            </header>

            <section class="space-y-4 px-4 py-4 sm:px-6">
                <div>
                    <h2 class="text-[36px] font-bold leading-none text-[#24358a]">@yield('page_title')</h2>
                    @hasSection('page_subtitle')
                        <p class="text-sm text-slate-500">@yield('page_subtitle')</p>
                    @endif
                </div>
                @if (session('success'))
                    <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
                @endif
                @yield('content')
            </section>
        </main>
    </div>

    {{-- Sign out confirmation modal --}}
    @auth
        @include('partials.logout-modal')
    @endauth

    <script>
        (() => {
            const nav = document.getElementById('admin-sidebar-nav');
            if (!nav) return;

            const sections = Array.from(nav.querySelectorAll('.admin-section'));
            const collapsible = sections.filter((section) => section.dataset.hasChildren === 'true');

            const closeAllExcept = (targetSection) => {
                collapsible.forEach((section) => {
                    if (section === targetSection) return;

                    const toggle = section.querySelector('.admin-section-toggle');
                    const children = section.querySelector('.admin-section-children');
                    const icon = toggle?.querySelector('svg:last-of-type');
                    if (!toggle || !children) return;

                    children.classList.add('hidden');
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.dataset.expanded = 'false';
                    icon?.classList.remove('rotate-180');
                });
            };

            collapsible.forEach((section) => {
                const toggle = section.querySelector('.admin-section-toggle');
                const children = section.querySelector('.admin-section-children');
                const icon = toggle?.querySelector('svg:last-of-type');
                if (!toggle || !children) return;

                toggle.addEventListener('click', () => {
                    const isOpen = toggle.dataset.expanded === 'true';

                    if (isOpen) {
                        children.classList.add('hidden');
                        toggle.setAttribute('aria-expanded', 'false');
                        toggle.dataset.expanded = 'false';
                        icon?.classList.remove('rotate-180');
                        return;
                    }

                    closeAllExcept(section);
                    children.classList.remove('hidden');
                    toggle.setAttribute('aria-expanded', 'true');
                    toggle.dataset.expanded = 'true';
                    icon?.classList.add('rotate-180');
                });
            });
        })();

    </script>

    @stack('scripts')
</body>
</html>
