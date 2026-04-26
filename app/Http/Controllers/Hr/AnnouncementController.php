<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Models\AnnouncementNotification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $priority = $request->string('priority')->toString();

        $announcements = Announcement::query()
            ->latest()
            ->when($search, function ($query, $term) {
                $query->where(function ($nested) use ($term) {
                    $nested->where('title', 'like', "%{$term}%")
                        ->orWhere('content', 'like', "%{$term}%");
                });
            })
            ->when($priority, fn ($query, $value) => $query->where('priority', $value))
            ->paginate(10)
            ->withQueryString();

        return view('hr.announcements', [
            'announcements' => $announcements,
            'officeAudiences' => config('hris.admin_support_offices', []),
            'filters' => [
                'search' => $search,
                'priority' => $priority,
            ],
            'stats' => [
                'total' => Announcement::count(),
                'active' => Announcement::where('is_published', true)->count(),
                'urgent' => Announcement::where('priority', 'high')->count(),
                'current_month' => Announcement::whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count(),
            ],
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $announcement = Announcement::create([
                ...$request->validated(),
                'is_published' => $request->boolean('is_published'),
                'created_by' => $request->user()->id,
                'published_at' => $request->date('published_at') ?? now(),
            ]);

            $userQuery = User::query();

            if ($announcement->target_user_type) {
                $userQuery->where('user_type', $announcement->target_user_type);
            }

            if ($announcement->target_office) {
                $userQuery->whereHas('employeeProfile', function ($query) use ($announcement): void {
                    $query->where('position', $announcement->target_office);
                });
            }

            $userIds = $userQuery->pluck('id');

            $rows = $userIds->map(fn ($userId) => [
                'announcement_id' => $announcement->id,
                'user_id' => $userId,
                'is_read' => false,
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            if (! empty($rows)) {
                AnnouncementNotification::insert($rows);
            }
        });

        return redirect()->route('announcements.index')->with('success', 'Announcement posted and notifications sent.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()->route('announcements.index')->with('success', 'Announcement deleted successfully.');
    }
}
