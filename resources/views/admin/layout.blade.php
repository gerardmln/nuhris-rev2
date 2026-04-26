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
                'label' => 'User & Role Management',
                'route' => 'admin.users.accounts',
                'match' => 'admin.users.*',
                'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 10-8 0 4 4 0 008 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z',
                'children' => [
                    ['label' => 'User Accounts', 'route' => 'admin.users.accounts', 'match' => 'admin.users.accounts'],
                    ['label' => 'RBAC Permissions', 'route' => 'admin.users.rbac', 'match' => 'admin.users.rbac'],
                ],
            ],
            [
                'label' => 'Policy & Configuration',
                'route' => 'admin.policy.cutoff',
                'match' => 'admin.policy.*',
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z',
                'children' => [
                    ['label' => 'Cut-off & Schedules', 'route' => 'admin.policy.cutoff', 'match' => 'admin.policy.cutoff'],
                    ['label' => 'Leave Rules', 'route' => 'admin.policy.leave', 'match' => 'admin.policy.leave'],
                    ['label' => 'Compliance Rules', 'route' => 'admin.policy.compliance', 'match' => 'admin.policy.compliance'],
                    ['label' => 'Notification Templates', 'route' => 'admin.policy.templates', 'match' => 'admin.policy.templates'],
                ],
            ],
            [
                'label' => 'Integration & Governance',
                'route' => 'admin.integration.api',
                'match' => 'admin.integration.*',
                'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                'children' => [
                    ['label' => 'API Integrations', 'route' => 'admin.integration.api', 'match' => 'admin.integration.api'],
                    ['label' => 'Audit Logs', 'route' => 'admin.integration.audit', 'match' => 'admin.integration.audit'],
                    ['label' => 'Data Validation', 'route' => 'admin.integration.validation', 'match' => 'admin.integration.validation'],
                ],
            ],
            [
                'label' => 'Report & Oversight',
                'route' => 'admin.reports',
                'match' => 'admin.reports',
                'icon' => 'M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2M9 7h6m-6 4h6m2 9H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
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
            <header class="border-b border-slate-300 bg-[#0a3f79] px-8 py-4 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-[36px] font-bold leading-none text-white">Admin Dashboard</h1>
                        <p class="text-sm text-blue-100">National University HRIS</p>
                    </div>
                    <div class="flex items-center gap-2 text-white">
                        <div class="relative">
                            <button id="admin-bell-toggle" type="button" class="rounded-full p-2 hover:bg-white/10" aria-label="Notifications" aria-expanded="false" aria-controls="admin-notification-panel">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"></path>
                                    <path d="M10 20a2 2 0 0 0 4 0"></path>
                                </svg>
                            </button>

                            <div id="admin-notification-panel" class="absolute right-0 z-50 mt-2 hidden w-72 rounded-xl border border-slate-200 bg-white p-3 text-slate-900 shadow-xl">
                                <div class="mb-3 flex items-center justify-between">
                                    <h3 class="text-2xl font-semibold leading-none text-[#232f83]">Notifications</h3>
                                    <button type="button" class="text-xs font-medium text-red-600 hover:underline">Mark all as read</button>
                                </div>

                                <div class="space-y-3">
                                    <article>
                                        <p class="text-xl font-medium leading-tight">PRC License Expiring</p>
                                        <p class="text-xs text-slate-500">5 faculty members have licenses expiring in 30 days</p>
                                        <p class="text-xs text-slate-400">5 min ago</p>
                                    </article>

                                    <article>
                                        <p class="text-xl font-medium leading-tight">New User Registration</p>
                                        <p class="text-xs text-slate-500">John Smith has been added to the system</p>
                                        <p class="text-xs text-slate-400">1 hour ago</p>
                                    </article>

                                    <article>
                                        <p class="text-xl font-medium leading-tight">Compliance Alert</p>
                                        <p class="text-xs text-slate-500">CHED compliance report due in 7 days</p>
                                        <p class="text-xs text-slate-400">2 hours ago</p>
                                    </article>

                                    <article>
                                        <p class="text-xl font-medium leading-tight">Backup Complete</p>
                                        <p class="text-xs text-slate-500">System backup completed successfully</p>
                                        <p class="text-xs text-slate-400">3 hours ago</p>
                                    </article>
                                </div>
                            </div>
                        </div>

                        <details class="group relative">
                            <summary class="flex cursor-pointer list-none items-center gap-1 rounded-full px-1 py-1 hover:bg-white/10">
                                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <circle cx="12" cy="8" r="4"></circle>
                                    <path d="M4 20c1.5-4 5-6 8-6s6.5 2 8 6"></path>
                                </svg>
                                <svg class="h-4 w-4 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M5.25 7.5 10 12.25 14.75 7.5" />
                                </svg>
                            </summary>

                            <div class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white text-slate-900 shadow-xl">
                                <div class="border-b border-slate-200 px-4 py-3">
                                    <p class="text-sm font-semibold">{{ $name }}</p>
                                    <p class="text-xs text-slate-500">Admin user</p>
                                </div>

                                @auth
                                    <div class="p-2">
                                        <button type="button"
                                                class="logout-trigger w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-red-600 hover:bg-red-50"
                                                data-testid="admin-signout-button">
                                            Sign out
                                        </button>
                                    </div>
                                @else
                                    <div class="p-2">
                                        <button type="button" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-100">
                                            Sign out
                                        </button>
                                    </div>
                                @endauth
                            </div>
                        </details>
                    </div>
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

        (() => {
            const bellToggle = document.getElementById('admin-bell-toggle');
            const panel = document.getElementById('admin-notification-panel');
            if (!bellToggle || !panel) return;

            const closePanel = () => {
                panel.classList.add('hidden');
                bellToggle.setAttribute('aria-expanded', 'false');
            };

            const openPanel = () => {
                panel.classList.remove('hidden');
                bellToggle.setAttribute('aria-expanded', 'true');
            };

            bellToggle.addEventListener('click', (event) => {
                event.stopPropagation();
                if (panel.classList.contains('hidden')) {
                    openPanel();
                } else {
                    closePanel();
                }
            });

            panel.addEventListener('click', (event) => {
                event.stopPropagation();
            });

            document.addEventListener('click', () => {
                closePanel();
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closePanel();
                }
            });
        })();
    </script>

    @stack('scripts')
</body>
</html>
