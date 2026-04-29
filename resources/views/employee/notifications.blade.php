@extends('employee.layout')

@section('title', 'Notification')
@section('page_title', 'Notification')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <p class="text-sm text-slate-600">Stay updated with credential reminders, HR announcements, and compliance alerts.</p>
        <div class="flex flex-wrap gap-2">
            @if ($notifications->where('is_read', false)->isNotEmpty())
                <form method="POST" action="{{ route('employee.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-blue-300 bg-white px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">Read All</button>
                </form>
            @endif
            <form method="POST" action="{{ route('employee.notifications.clear-all') }}" onsubmit="return confirm('Clear all notifications? This will remove them from your inbox.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear All</button>
            </form>
        </div>
    </div>

    <div id="notif-list" class="space-y-4">
        @forelse ($notifications as $notification)
            @php
                $announcement = $notification->announcement;
                $priorityLabel = $announcement?->priority_label ?? 'Medium';
                $priorityBadgeClass = $announcement?->priority_badge_class ?? 'bg-blue-100 text-blue-700';
            @endphp
            <a href="{{ route('employee.notifications.open', $notification) }}" class="block rounded-2xl border px-6 py-5 shadow-sm transition hover:border-slate-400 hover:bg-slate-50 {{ $notification->is_read ? 'border-slate-300 bg-slate-100' : 'border-slate-300 bg-white' }}">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <h3 class="text-2xl font-bold {{ $notification->is_read ? 'text-slate-500' : 'text-slate-900' }}">{{ $notification->title_text }}</h3>
                        @unless ($notification->is_read)
                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-blue-700" aria-label="Unread notification">
                                <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                                Unread
                            </span>
                        @endunless
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $priorityBadgeClass }}">{{ $priorityLabel }}</span>
                        @if ($announcement?->is_expired)
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-700">Expired</span>
                        @endif
                    </div>
                </div>
                <p class="text-sm {{ $notification->is_read ? 'text-slate-500' : 'text-slate-700' }}">{{ $notification->content_text }}</p>
                <p class="mt-5 text-xs text-slate-400">{{ $notification->created_at->format('M d, Y \a\t h:i A') }}</p>
            </a>
        @empty
            <p class="py-24 text-center text-2xl text-slate-400">No notifications found</p>
        @endforelse
    </div>
@endsection