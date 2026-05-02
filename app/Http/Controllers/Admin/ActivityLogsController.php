<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ActivityLogsController extends Controller
{
    /**
     * Display activity logs (placeholder for future implementation).
     */
    public function index(): View
    {
        return view('admin.activity-logs.index', [
            'message' => 'Activity Logs module is reserved for future development.',
        ]);
    }
}
