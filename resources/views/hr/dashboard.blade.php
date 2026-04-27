<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>HR Dashboard | {{ config('app.name', 'NU HRIS') }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#eceef1] text-slate-900 antialiased overflow-x-hidden overflow-y-auto">
    <div class="flex min-h-screen flex-col lg:flex-row">
        @include('partials.hr-sidebar', ['activeNav' => 'dashboard'])

        <main class="min-h-screen flex-1">
            <header class="border-b border-slate-300 bg-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-[30px] font-bold leading-none text-[#1f2b5d]">National University HRIS</h1>
                    </div>

                    @include('partials.header-actions')
                </div>
            </header>

            <section class="space-y-5 px-5 py-5 sm:px-6 sm:py-6">
                <div>
                    <h2 class="text-3xl font-bold text-[#1f2b5d]">HR Dashboard</h2>
                    <p class="text-sm text-slate-500">Welcome back! Here is your HR overview.</p>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium text-slate-500">Total Employees</p>
                        <p class="mt-1 text-4xl font-extrabold">{{ $stats['total_employees'] }}</p>
                    </article>
                    <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium text-slate-500">Pending Credentials</p>
                        <p class="mt-1 text-4xl font-extrabold">{{ $stats['pending_credentials'] }}</p>
                    </article>
                    <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium text-slate-500">Present Today</p>
                        <p class="mt-1 text-4xl font-extrabold">{{ $stats['present_today'] }}</p>
                    </article>
                    <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium text-slate-500">Expiring Licenses</p>
                        <p class="mt-1 text-4xl font-extrabold">{{ $stats['expiring_licenses'] }}</p>
                    </article>
                </div>

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                    <div class="space-y-4 xl:col-span-2">
                        <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                            <h3 class="mb-3 text-2xl font-bold text-slate-800">Action Required</h3>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3">
                                    <div>
                                        <p class="font-semibold text-slate-800">4 Resume(s) Need Update</p>
                                        <p class="text-xs text-slate-500">Outdated records</p>
                                    </div>
                                    <span class="text-slate-400">></span>
                                </div>

                                <div class="flex items-center justify-between rounded-xl border border-blue-200 bg-blue-50 px-4 py-3">
                                    <div>
                                        <p class="font-semibold text-slate-800">1 Credential pending review</p>
                                        <p class="text-xs text-slate-500">Requires verification</p>
                                    </div>
                                    <span class="text-slate-400">></span>
                                </div>
                            </div>
                        </article>

                        <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                            <h3 class="mb-2 text-2xl font-bold text-slate-800">Records Overview</h3>
                            <p class="mb-3 text-sm text-slate-500">Latest HR updates and metrics.</p>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm font-semibold text-slate-700">Onboarding Queue</p>
                                    <p class="mt-2 text-3xl font-extrabold">3</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm font-semibold text-slate-700">Payroll Pending</p>
                                    <p class="mt-2 text-3xl font-extrabold">2</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm font-semibold text-slate-700">Leaves for Approval</p>
                                    <p class="mt-2 text-3xl font-extrabold">6</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm font-semibold text-slate-700">Policy Drafts</p>
                                    <p class="mt-2 text-3xl font-extrabold">1</p>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div class="space-y-4">
                        <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                            <h3 class="text-2xl font-bold text-slate-800">Calendar</h3>
                            <p class="mb-3 text-sm text-slate-500">February 2026</p>

                            <div class="grid grid-cols-7 gap-1 text-center text-xs text-slate-500">
                                <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                            </div>

                            <div class="mt-2 grid grid-cols-7 gap-1 text-center text-sm">
                                <span class="rounded py-1">1</span><span class="rounded py-1">2</span><span class="rounded py-1">3</span><span class="rounded py-1">4</span><span class="rounded py-1">5</span><span class="rounded py-1">6</span><span class="rounded py-1">7</span>
                                <span class="rounded py-1">8</span><span class="rounded py-1">9</span><span class="rounded py-1">10</span><span class="rounded py-1">11</span><span class="rounded py-1">12</span><span class="rounded bg-sky-600 py-1 font-semibold text-white">13</span><span class="rounded py-1">14</span>
                                <span class="rounded py-1">15</span><span class="rounded py-1">16</span><span class="rounded py-1">17</span><span class="rounded py-1">18</span><span class="rounded py-1">19</span><span class="rounded py-1">20</span><span class="rounded py-1">21</span>
                                <span class="rounded py-1">22</span><span class="rounded py-1">23</span><span class="rounded py-1">24</span><span class="rounded py-1">25</span><span class="rounded py-1">26</span><span class="rounded py-1">27</span><span class="rounded py-1">28</span>
                            </div>

                            <div class="mt-4 space-y-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Upcoming Events</p>
                                <div class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm">National Heroes Day</div>
                                <div class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm">PRC License Renewal Period</div>
                            </div>
                        </article>

                        <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                            <div class="mb-2 flex items-center justify-between">
                                <h3 class="text-2xl font-bold text-slate-800">Announcements</h3>
                                <a href="{{ route('announcements.index') }}" class="text-sm font-semibold text-blue-700 hover:underline">View all</a>
                            </div>

                            <div class="space-y-3">
                                @forelse($announcements as $announcement)
                                    <div class="rounded-lg border border-slate-200 px-3 py-2">
                                        <p class="text-sm font-semibold">{{ $announcement->title }}</p>
                                        <p class="text-xs text-slate-500">{{ $announcement->published_at->format('M d, Y') }}</p>
                                    </div>
                                @empty
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-4 text-center">
                                        <p class="text-sm text-slate-500">No announcements yet</p>
                                    </div>
                                @endforelse
                            </div>
                        </article>
                    </div>
                </div>
                <div class="h-8"></div>
            </section>
        </main>
    </div>

    @auth
        @include('partials.logout-modal')
    @endauth
</body>
</html>
