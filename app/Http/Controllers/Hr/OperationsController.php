<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementNotification;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCredential;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\SupabaseStorageService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class OperationsController extends Controller
{
    public function profile(Request $request): View
    {
        $employeeId = $request->integer('employee');

        $employee = Employee::query()
            ->with('department')
            ->when($employeeId, fn ($query) => $query->whereKey($employeeId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->first();

        return view('hr.viewemployeeprofile', [
            'employee' => $employee,
        ]);
    }

    public function credentials(Request $request): View
    {
        $statusFilter = $request->string('status')->toString() ?: 'pending';

        $query = EmployeeCredential::query()
            ->with(['employee.department', 'reviewer'])
            ->latest('updated_at');

        if (in_array($statusFilter, ['pending', 'verified', 'rejected'], true)) {
            $query->where('status', $statusFilter);
        }

        $credentials = $query->get()->map(function (EmployeeCredential $credential) {
            return [
                'id' => $credential->id,
                'employee_name' => $credential->employee?->full_name ?? '—',
                'employee_email' => $credential->employee?->email ?? '',
                'department' => $credential->employee?->department?->name ?? 'Unassigned',
                'type_label' => $credential->typeLabel(),
                'title' => $credential->title,
                'description' => $credential->description,
                'expires_at' => $credential->expires_at?->format('M d, Y'),
                'submitted_at' => $credential->created_at?->format('M d, Y h:i A'),
                'status' => $credential->status,
                'has_file' => ! empty($credential->file_path),
                'original_filename' => $credential->original_filename,
                'review_notes' => $credential->review_notes,
                'reviewer_name' => $credential->reviewer?->name,
                'reviewed_at' => $credential->reviewed_at?->format('M d, Y h:i A'),
            ];
        });

        $counts = [
            'pending' => EmployeeCredential::query()->where('status', 'pending')->count(),
            'verified' => EmployeeCredential::query()->where('status', 'verified')->count(),
            'rejected' => EmployeeCredential::query()->where('status', 'rejected')->count(),
            'total' => EmployeeCredential::query()->count(),
        ];

        $expiringSoon = EmployeeCredential::query()
            ->where('status', 'verified')
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', now()->addDays(30))
            ->whereDate('expires_at', '>=', now())
            ->count();

        return view('hr.credentials', [
            'credentials' => $credentials,
            'departments' => Department::query()->schools()->orderBy('name')->get(),
            'statusFilter' => $statusFilter,
            'stats' => [
                'total' => $counts['total'],
                'pending' => $counts['pending'],
                'verified' => $counts['verified'],
                'rejected' => $counts['rejected'],
                'expiring_soon' => $expiringSoon,
            ],
        ]);
    }

    public function viewCredentialFile(EmployeeCredential $credential, SupabaseStorageService $storage)
    {
        if (! $credential->file_path) {
            return back()->with('error', 'No file was attached to this credential.');
        }

        $url = $storage->createSignedUrl($credential->file_path, 300);

        if (! $url) {
            return back()->with('error', 'Unable to generate a download link. Please try again.');
        }

        return redirect()->away($url);
    }

    public function approveCredential(Request $request, EmployeeCredential $credential): RedirectResponse
    {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $credential, $validated): void {
            $credential->update([
                'status' => 'verified',
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
                'review_notes' => $validated['review_notes'] ?? null,
            ]);

            $this->broadcastCredentialDecisionNotification(
                $credential,
                'approved',
                $request->user(),
                $validated['review_notes'] ?? null
            );
        });

        return back()->with('success', 'Credential approved successfully.');
    }

    public function rejectCredential(Request $request, EmployeeCredential $credential): RedirectResponse
    {
        $validated = $request->validate([
            'review_notes' => ['required', 'string', 'max:1000'],
        ], [
            'review_notes.required' => 'Please provide a reason when rejecting a credential so the employee knows what to fix.',
        ]);

        DB::transaction(function () use ($request, $credential, $validated): void {
            $credential->update([
                'status' => 'rejected',
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
                'review_notes' => $validated['review_notes'],
            ]);

            $this->broadcastCredentialDecisionNotification(
                $credential,
                'rejected',
                $request->user(),
                $validated['review_notes']
            );
        });

        return back()->with('success', 'Credential rejected. The employee will see your notes.');
    }

    private function broadcastCredentialDecisionNotification(EmployeeCredential $credential, string $decision, ?User $reviewer, ?string $notes = null): void
    {
        $credential->loadMissing('employee');

        $title = $decision === 'approved'
            ? 'Credential approved'
            : 'Credential rejected';

        $content = sprintf(
            '%s %s the %s credential "%s" for %s.',
            $reviewer?->name ?? 'HR',
            $decision,
            $credential->typeLabel(),
            $credential->title,
            $credential->employee?->full_name ?? 'an employee'
        );

        if ($notes) {
            $content .= ' Notes: '.$notes;
        }

        $announcement = Announcement::create([
            'title' => $title,
            'content' => $content,
            'priority' => $decision === 'rejected' ? 'high' : 'medium',
            'published_at' => now(),
            'is_published' => true,
            'created_by' => $reviewer?->id,
        ]);

        $userIds = User::query()->pluck('id');

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
    }

    public function timekeeping(Request $request): View
    {
        $month = $request->integer('month', (int) now()->month);
        $year = $request->integer('year', (int) now()->year);
        $search = $request->string('search')->trim()->toString();
        $selectedDate = Carbon::createFromDate($year, $month, 1);

        $query = Employee::query()
            ->with('department')
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ILIKE', "%{$search}%")
                  ->orWhere('last_name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%")
                  ->orWhere('employee_id', 'ILIKE', "%{$search}%")
                  ->orWhereHas('department', fn ($dq) => $dq->where('name', 'ILIKE', "%{$search}%"));
            });
        }

        $employeeCards = $query->get()->map(function (Employee $employee) use ($selectedDate) {
            $monthStart = $selectedDate->copy()->startOfMonth();
            $monthEnd = $selectedDate->copy()->endOfMonth();

            $attendanceRecords = AttendanceRecord::where('employee_id', $employee->id)
                ->whereBetween('record_date', [$monthStart, $monthEnd])
                ->get();

            $presentDays = $attendanceRecords->where('status', 'present')->count();
            $totalTardiness = (int) $attendanceRecords->sum('tardiness_minutes');

            return [
                'id' => $employee->id,
                'initials' => str($employee->full_name)->explode(' ')->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->join(''),
                'name' => $employee->full_name,
                'department' => $employee->department?->name ?? 'Unassigned',
                'present' => $presentDays,
                'tardiness' => $totalTardiness,
                'has_data' => $attendanceRecords->isNotEmpty(),
                'official_time' => sprintf('%s - %s', optional($employee->official_time_in)?->format('H:i') ?? '08:30', optional($employee->official_time_out)?->format('H:i') ?? '17:30'),
            ];
        });

        // Generate period options (last 12 months)
        $periods = collect();
        for ($i = 0; $i < 12; $i++) {
            $d = now()->subMonths($i);
            $periods->push([
                'label' => $d->format('F Y'),
                'month' => $d->month,
                'year' => $d->year,
                'selected' => $d->month === $month && $d->year === $year,
            ]);
        }

        return view('hr.timekeeping', [
            'employeeCards' => $employeeCards,
            'periods' => $periods,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'search' => $search,
        ]);
    }

    public function dailyTimeRecord(Request $request): View
    {
        $employeeId = $request->integer('employee');
        $month = $request->integer('month', (int) now()->month);
        $year = $request->integer('year', (int) now()->year);
        $selectedDate = Carbon::createFromDate($year, $month, 1);

        $employee = Employee::query()
            ->with('department')
            ->when($employeeId, fn ($query) => $query->whereKey($employeeId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->first();

        $records = $this->buildAttendanceRows($employee, $selectedDate);

        $summary = [
            'present_days' => $records->where('status', 'Present')->count(),
            'absent_days' => $records->where('status', 'Absent')->count(),
            'tardiness_total' => $records->sum('tardiness_minutes'),
            'undertime_total' => $records->sum('undertime_minutes'),
        ];

        $periods = collect();
        for ($i = 0; $i < 12; $i++) {
            $d = now()->subMonths($i);
            $periods->push([
                'label' => $d->format('F Y'),
                'month' => $d->month,
                'year' => $d->year,
                'selected' => $d->month === $month && $d->year === $year,
            ]);
        }

        return view('hr.dailytimerecord', [
            'employee' => $employee,
            'records' => $records,
            'summary' => $summary,
            'period_label' => $selectedDate->format('F Y'),
            'official_time' => sprintf('%s - %s', optional($employee?->official_time_in)?->format('H:i') ?? '08:30', optional($employee?->official_time_out)?->format('H:i') ?? '17:30'),
            'periods' => $periods,
            'selectedMonth' => $month,
            'selectedYear' => $year,
        ]);
    }

    public function exportDtrPdf(Request $request)
    {
        $employeeId = $request->integer('employee');
        $month = $request->integer('month', (int) now()->month);
        $year = $request->integer('year', (int) now()->year);
        $selectedDate = Carbon::createFromDate($year, $month, 1);

        $employee = Employee::query()->with('department')
            ->when($employeeId, fn ($q) => $q->whereKey($employeeId))
            ->orderBy('last_name')->first();

        $records = $this->buildAttendanceRows($employee, $selectedDate);
        $summary = [
            'present_days' => $records->where('status', 'Present')->count(),
            'absent_days' => $records->where('status', 'Absent')->count(),
            'tardiness_total' => $records->sum('tardiness_minutes'),
            'undertime_total' => $records->sum('undertime_minutes'),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('hr.dtr-export-pdf', [
            'employee' => $employee,
            'records' => $records,
            'summary' => $summary,
            'period_label' => $selectedDate->format('F Y'),
            'official_time' => sprintf('%s - %s', optional($employee?->official_time_in)?->format('H:i') ?? '08:30', optional($employee?->official_time_out)?->format('H:i') ?? '17:30'),
        ]);

        $filename = 'DTR_' . str_replace(' ', '_', $employee?->full_name ?? 'Employee') . '_' . $selectedDate->format('F_Y') . '.pdf';
        return $pdf->download($filename);
    }

    public function exportDtrExcel(Request $request)
    {
        $employeeId = $request->integer('employee');
        $month = $request->integer('month', (int) now()->month);
        $year = $request->integer('year', (int) now()->year);
        $selectedDate = Carbon::createFromDate($year, $month, 1);

        $employee = Employee::query()->with('department')
            ->when($employeeId, fn ($q) => $q->whereKey($employeeId))
            ->orderBy('last_name')->first();

        $records = $this->buildAttendanceRows($employee, $selectedDate);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('DTR');

        // Header
        $sheet->setCellValue('A1', 'Daily Time Record - ' . ($employee?->full_name ?? 'Employee'));
        $sheet->setCellValue('A2', 'Period: ' . $selectedDate->format('F Y'));
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Column headers
        $headers = ['Date', 'Day', 'Time In', 'Time Out', 'Tardiness (min)', 'Undertime (min)', 'Status'];
        foreach ($headers as $col => $h) {
            $sheet->setCellValueByColumnAndRow($col + 1, 4, $h);
        }
        $sheet->getStyle('A4:G4')->getFont()->setBold(true);

        // Data
        $row = 5;
        foreach ($records as $r) {
            $sheet->setCellValueByColumnAndRow(1, $row, $r['date']);
            $sheet->setCellValueByColumnAndRow(2, $row, $r['day']);
            $sheet->setCellValueByColumnAndRow(3, $row, $r['time_in']);
            $sheet->setCellValueByColumnAndRow(4, $row, $r['time_out']);
            $sheet->setCellValueByColumnAndRow(5, $row, $r['tardiness_minutes'] ?: '-');
            $sheet->setCellValueByColumnAndRow(6, $row, $r['undertime_minutes'] ?: '-');
            $sheet->setCellValueByColumnAndRow(7, $row, $r['status']);
            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'DTR_' . str_replace(' ', '_', $employee?->full_name ?? 'Employee') . '_' . $selectedDate->format('F_Y') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function leaveManagement(Request $request): View
    {
        $search = $request->string('search')->toString();
        $departmentId = $request->string('department_id')->toString();
        $selectedMonth = $request->string('month')->toString();

        if (! preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = now()->format('Y-m');
        }

        [$selectedYear, $selectedMonthNumber] = array_map('intval', explode('-', $selectedMonth));

        $employees = Employee::query()
            ->with([
                'department',
                'leaveBalances',
                'leaveRequests' => fn ($query) => $query
                    ->whereYear('start_date', $selectedYear)
                    ->whereMonth('start_date', $selectedMonthNumber),
            ])
            ->when($search, function ($query, $searchTerm) {
                $query->where(function ($nested) use ($searchTerm) {
                    $nested
                        ->where('employee_id', 'like', "%{$searchTerm}%")
                        ->orWhere('first_name', 'like', "%{$searchTerm}%")
                        ->orWhere('last_name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhereHas('department', fn ($department) => $department->where('name', 'like', "%{$searchTerm}%"));
                });
            })
            ->when($departmentId === 'asp', function ($query) {
                $query->where(function ($nested) {
                    $nested
                        ->where('employment_type', 'Admin Support Personnel')
                        ->orWhereHas('department', fn ($department) => $department->where('name', 'ASP'));
                });
            })
            ->when(filled($departmentId) && $departmentId !== 'asp', fn ($query) => $query->where('department_id', $departmentId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $leaveCards = $employees->map(function (Employee $employee) {
            $balances = $employee->leaveBalances;
            $requests = $employee->leaveRequests;

            $activeRequests = $requests->whereIn('status', ['approved', 'pending']);
            $used = (float) $activeRequests->sum('days_deducted');

            // Calculate used days per leave type from actual requests
            $usedByType = $activeRequests->groupBy(fn ($r) => strtolower((string) $r->leave_type));

            $vacationUsed = (float) collect(['vacation leave', 'vacation', 'vl'])
                ->flatMap(fn ($key) => $usedByType->get($key, collect()))
                ->sum('days_deducted');

            $sickUsed = (float) collect(['sick leave', 'sick', 'sl'])
                ->flatMap(fn ($key) => $usedByType->get($key, collect()))
                ->sum('days_deducted');

            $emergencyUsed = (float) collect(['emergency leave', 'emergency', 'el'])
                ->flatMap(fn ($key) => $usedByType->get($key, collect()))
                ->sum('days_deducted');

            // If leave balances table has data, use that for remaining
            // Otherwise show the used counts from requests (more useful than 0)
            $remaining = (float) $balances->sum('remaining_days');

            $balanceByType = $balances->keyBy(fn ($b) => strtolower((string) $b->leave_type));
            $lookup = function (array $keys) use ($balanceByType) {
                foreach ($keys as $key) {
                    if ($balanceByType->has($key)) {
                        return (float) $balanceByType->get($key)->remaining_days;
                    }
                }
                return null;
            };

            return [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'department' => $employee->department?->name ?? 'Unassigned',
                'initials' => str($employee->full_name)->explode(' ')->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->join(''),
                'vacation_remaining' => $lookup(['vacation leave', 'vacation', 'vl']),
                'vacation_used' => $vacationUsed,
                'sick_remaining' => $lookup(['sick leave', 'sick', 'sl']),
                'sick_used' => $sickUsed,
                'emergency_remaining' => $lookup(['emergency leave', 'emergency', 'el']),
                'emergency_used' => $emergencyUsed,
                'remaining' => $remaining,
                'used' => $used,
                'carry_over' => 0,
                'has_data' => $requests->isNotEmpty() || $balances->isNotEmpty(),
                'leave_types' => $usedByType->keys()->toArray(),
            ];
        });

        $totalUsed = (float) $leaveCards->sum('used');
        $totalVacationUsed = (float) $leaveCards->sum('vacation_used');
        $totalSickUsed = (float) $leaveCards->sum('sick_used');

        $monthOptions = collect(range(0, 11))
            ->map(function ($offset) {
                $date = now()->startOfMonth()->subMonths($offset);

                return [
                    'value' => $date->format('Y-m'),
                    'label' => $date->format('F Y'),
                ];
            })
            ->values();

        return view('hr.leavemanagement', [
            'leaveCards' => $leaveCards,
            'departments' => Department::query()->facultySchools()->orderBy('name')->get(),
            'filters' => [
                'search' => $search,
                'department_id' => $departmentId,
                'month' => $selectedMonth,
            ],
            'monthOptions' => $monthOptions,
            'stats' => [
                'total_employees' => $leaveCards->count(),
                'vacation_used' => (int) round($totalVacationUsed),
                'sick_used' => (int) round($totalSickUsed),
                'current_year' => now()->year,
            ],
        ]);
    }

    /**
     * Normalize an employee ID by stripping dashes/hyphens for comparison.
     * e.g. "23-8061" => "238061", "238061" => "238061"
     */
    private function normalizeEmployeeId(string $id): string
    {
        return str_replace('-', '', trim($id));
    }

    public function uploadBiometrics(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'biometrics_file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'biometrics_file.required' => 'Please choose a PDF file first.',
            'biometrics_file.mimes' => 'Only PDF files are accepted (got a different file type).',
            'biometrics_file.max' => 'The PDF is too large. Maximum allowed is 10 MB.',
        ]);

        $file = $validated['biometrics_file'];

        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($file->getRealPath());
            $text = $pdf->getText();

            $records = $this->parseBiometricText($text);

            if (empty($records)) {
                return back()->with('error', 'No attendance rows were detected in the PDF. Make sure you uploaded a biometric "Timesheet Report" and not a different document.');
            }

            // ------------------------------------------------------------------
            // PERFORMANCE FIX: Pre-load ALL employees into memory once instead
            // of querying the database per-record (which caused the 30-second
            // timeout on large PDFs).
            // ------------------------------------------------------------------
            $allEmployees = Employee::all();

            // Build lookup maps for fast matching
            $byExactId = $allEmployees->keyBy('employee_id');
            $byNormalizedId = [];
            $byNameIndex = [];

            foreach ($allEmployees as $emp) {
                $normalizedId = $this->normalizeEmployeeId($emp->employee_id);
                $byNormalizedId[$normalizedId] = $emp;

                // Index by "lastname" => [employees] for name-based fallback
                $lastKey = mb_strtolower(trim($emp->last_name));
                $byNameIndex[$lastKey][] = $emp;
            }

            $imported = 0;
            $skipped = 0;
            $unmatchedEmployees = [];
            $appliedEmployees = [];

            foreach ($records as $record) {
                $employee = $this->findEmployeeInMemory(
                    $record,
                    $byExactId,
                    $byNormalizedId,
                    $byNameIndex
                );

                if (! $employee) {
                    $skipped++;
                    $label = trim(($record['name'] ?? '').' ('.($record['employee_id'] ?? '—').')');
                    $unmatchedEmployees[$label] = true;
                    continue;
                }

                $recordDate = Carbon::parse($record['date']);
                $scheduledIn = $employee->official_time_in ? Carbon::parse($employee->official_time_in) : Carbon::createFromTime(8, 30);
                $scheduledOut = $employee->official_time_out ? Carbon::parse($employee->official_time_out) : Carbon::createFromTime(17, 30);

                $timeIn = !empty($record['time_in']) ? Carbon::parse($record['time_in']) : null;
                $timeOut = !empty($record['time_out']) ? Carbon::parse($record['time_out']) : null;

                $tardiness = 0;
                $undertime = 0;
                $status = 'absent';

                if ($timeIn) {
                    $status = 'present';
                    $tardiness = max(0, (int) $timeIn->diffInMinutes($scheduledIn, false) * -1);
                }

                if ($timeOut && $timeIn) {
                    $undertime = max(0, (int) $scheduledOut->diffInMinutes($timeOut, false) * -1);
                }

                AttendanceRecord::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'record_date' => $recordDate->toDateString(),
                    ],
                    [
                        'time_in' => $timeIn?->format('H:i:s'),
                        'time_out' => $timeOut?->format('H:i:s'),
                        'scheduled_time_in' => $scheduledIn->format('H:i:s'),
                        'scheduled_time_out' => $scheduledOut->format('H:i:s'),
                        'tardiness_minutes' => $tardiness,
                        'undertime_minutes' => $undertime,
                        'status' => $status,
                    ]
                );

                $imported++;
                $appliedEmployees[$employee->full_name . ' (' . $employee->employee_id . ')'] = true;
            }

            $message = "Biometric PDF processed — {$imported} attendance row(s) imported";
            if ($skipped > 0) {
                $message .= ", {$skipped} skipped (unmatched employees)";
            }
            $message .= '.';

            return back()
                ->with('success', $message)
                ->with('unmatched_employees', array_keys($unmatchedEmployees))
                ->with('applied_employees', array_keys($appliedEmployees))
                ->with('import_stats', [
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'total_records' => count($records),
                ]);

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to parse PDF: '.$e->getMessage());
        }
    }

    /**
     * Find an employee from in-memory collections (no DB queries).
     * Match priority:
     *   1. Exact employee_id match
     *   2. Normalized employee_id match (strip dashes: "23-8061" == "238061")
     *   3. Name-based match (Lastname, Firstname format)
     */
    private function findEmployeeInMemory(
        array $record,
        Collection $byExactId,
        array $byNormalizedId,
        array $byNameIndex
    ): ?Employee {
        // 1. Exact ID match
        if (! empty($record['employee_id'])) {
            $exactId = trim($record['employee_id']);
            if ($byExactId->has($exactId)) {
                return $byExactId->get($exactId);
            }

            // 2. Normalized ID match (strip dashes)
            $normalizedId = $this->normalizeEmployeeId($exactId);
            if (isset($byNormalizedId[$normalizedId])) {
                return $byNormalizedId[$normalizedId];
            }
        }

        // 3. Name-based fallback
        if (empty($record['name'])) {
            return null;
        }

        $name = trim($record['name']);

        if (str_contains($name, ',')) {
            [$last, $first] = array_map('trim', explode(',', $name, 2));
            $firstWord = trim(explode(' ', $first)[0] ?? $first);
            $lastKey = mb_strtolower($last);

            if (! isset($byNameIndex[$lastKey])) {
                return null;
            }

            foreach ($byNameIndex[$lastKey] as $emp) {
                if (str_starts_with(mb_strtolower($emp->first_name), mb_strtolower($firstWord))) {
                    return $emp;
                }
            }
        } else {
            $parts = preg_split('/\s+/', $name);
            if (count($parts) >= 2) {
                $firstName = mb_strtolower($parts[0]);
                $lastName = mb_strtolower(end($parts));

                if (isset($byNameIndex[$lastName])) {
                    foreach ($byNameIndex[$lastName] as $emp) {
                        if (mb_strtolower($emp->first_name) === $firstName) {
                            return $emp;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Wipe all stored attendance records.
     */
    public function clearBiometrics(Request $request): RedirectResponse
    {
        $deleted = AttendanceRecord::query()->delete();

        return back()->with('success', "Cleared {$deleted} attendance record(s). You can now re-upload a biometric PDF.");
    }

    /**
     * Clear attendance records for a single employee.
     */
    public function clearEmployeeAttendance(Request $request, Employee $employee): RedirectResponse
    {
        $month = $request->integer('month', (int) now()->month);
        $year = $request->integer('year', (int) now()->year);
        $selectedDate = Carbon::createFromDate($year, $month, 1);

        $deleted = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('record_date', [
                $selectedDate->copy()->startOfMonth(),
                $selectedDate->copy()->endOfMonth(),
            ])
            ->delete();

        return back()->with('success', "Cleared {$deleted} attendance record(s) for {$employee->full_name}.");
    }

    /**
     * Import leave applications from an .xlsx export.
     */
    public function uploadLeaves(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'leaves_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'leaves_file.required' => 'Please choose an Excel file first.',
            'leaves_file.mimes' => 'Only .xlsx or .xls files are accepted.',
            'leaves_file.max' => 'The file is too large. Maximum allowed is 10 MB.',
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $validated['leaves_file'];

        try {
            $spreadsheet = SpreadsheetIOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
        } catch (\Throwable $exception) {
            return back()->with('error', 'Unable to read the Excel file: '.$exception->getMessage());
        }

        if (empty($rows) || count($rows) < 2) {
            return back()->with('error', 'The Excel file does not contain any data rows.');
        }

        $header = array_map(
            fn ($cell) => strtolower(trim((string) $cell)),
            $rows[0]
        );
        $dataRows = array_slice($rows, 1);

        $columnIndex = $this->resolveLeaveColumns($header);
        $missingColumns = array_keys(array_filter($columnIndex, fn ($idx) => $idx === null));

        if (! empty($missingColumns)) {
            return back()->with('error', 'Missing expected column(s) in the Excel header: '.implode(', ', $missingColumns).'. Expected headers: Leave ID, Employee ID, Employee Name, Application, Type, Date Filed, Date From, Date To, Total Hours, Status.');
        }

        // ------------------------------------------------------------------
        // PERFORMANCE FIX: Pre-load ALL employees into memory once.
        // Also build a normalized ID map for flexible matching.
        // ------------------------------------------------------------------
        $allEmployees = Employee::all();
        $byExactId = $allEmployees->keyBy('employee_id');
        $byNormalizedId = [];
        foreach ($allEmployees as $emp) {
            $byNormalizedId[$this->normalizeEmployeeId($emp->employee_id)] = $emp;
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $unmatched = [];
        $applied = [];

        foreach ($dataRows as $rowNumber => $row) {
            if (empty(array_filter($row, fn ($v) => $v !== null && $v !== ''))) {
                continue;
            }

            $employeeCode = trim((string) ($row[$columnIndex['employee_id']] ?? ''));
            $employeeName = trim((string) ($row[$columnIndex['employee_name']] ?? ''));

            if ($employeeCode === '' && $employeeName === '') {
                $skipped++;
                continue;
            }

            // Try exact match first, then normalized match
            $employee = null;
            if ($employeeCode !== '') {
                $employee = $byExactId->get($employeeCode);
                if (! $employee) {
                    $normalizedCode = $this->normalizeEmployeeId($employeeCode);
                    $employee = $byNormalizedId[$normalizedCode] ?? null;
                }
            }

            if (! $employee) {
                $skipped++;
                $unmatched[trim($employeeName.' ('.$employeeCode.')', ' ()')] = true;
                continue;
            }

            $startDate = $this->parseExcelDate($row[$columnIndex['date_from']] ?? null);
            $endDate = $this->parseExcelDate($row[$columnIndex['date_to']] ?? null) ?? $startDate;
            $dateFiled = $this->parseExcelDate($row[$columnIndex['date_filed']] ?? null);

            if (! $startDate) {
                $skipped++;
                continue;
            }

            $totalHours = (float) ($row[$columnIndex['total_hours']] ?? 0);
            $daysDeducted = $totalHours > 0 ? round($totalHours / 8, 2) : 0;

            $leaveType = trim((string) ($row[$columnIndex['application']] ?? 'Leave'));
            $category = trim((string) ($row[$columnIndex['type']] ?? ''));

            $statusRaw = strtoupper(trim((string) ($row[$columnIndex['status']] ?? '')));
            $status = match (true) {
                str_contains($statusRaw, 'APPROVED') && ! str_contains($statusRaw, 'DIS') => 'approved',
                str_contains($statusRaw, 'DIS') || str_contains($statusRaw, 'CANCEL') || str_contains($statusRaw, 'REJECT') => 'rejected',
                default => 'pending',
            };

            $reasonParts = array_filter([$category, $statusRaw ? 'Source status: '.$statusRaw : null]);

            $attributes = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'leave_type' => $leaveType,
                'days_deducted' => $daysDeducted,
                'status' => $status,
                'cutoff_date' => $dateFiled,
                'reason' => $reasonParts ? implode(' · ', $reasonParts) : null,
            ];

            $existing = LeaveRequest::query()
                ->where('employee_id', $employee->id)
                ->whereDate('start_date', $startDate)
                ->where('leave_type', $leaveType)
                ->first();

            if ($existing) {
                $existing->update($attributes);
                $updated++;
            } else {
                LeaveRequest::create(array_merge(['employee_id' => $employee->id], $attributes));
                $imported++;
            }

            $applied[$employee->full_name . ' (' . $employee->employee_id . ')'] = true;
        }

        $message = "Leave file processed — {$imported} new, {$updated} updated";
        if ($skipped > 0) {
            $message .= ", {$skipped} skipped";
        }
        $message .= '.';

        return back()
            ->with('success', $message)
            ->with('unmatched_employees', array_keys($unmatched))
            ->with('applied_employees', array_keys($applied))
            ->with('import_stats', [
                'imported' => $imported + $updated,
                'skipped' => $skipped,
                'total_records' => count($dataRows),
            ]);
    }

    /**
     * Wipe all stored leave requests.
     */
    public function clearLeaves(Request $request): RedirectResponse
    {
        $deleted = LeaveRequest::query()->delete();

        return back()->with('success', "Cleared {$deleted} leave request(s).");
    }

    /**
     * Clear leave records for a single employee.
     */
    public function clearEmployeeLeaves(Request $request, Employee $employee): RedirectResponse
    {
        $deleted = LeaveRequest::where('employee_id', $employee->id)->delete();

        return back()->with('success', "Cleared {$deleted} leave record(s) for {$employee->full_name}.");
    }

    /**
     * Locate each required column by header name.
     */
    private function resolveLeaveColumns(array $header): array
    {
        $find = function (array $candidates) use ($header): ?int {
            foreach ($header as $idx => $label) {
                if (in_array($label, $candidates, true)) {
                    return $idx;
                }
            }
            return null;
        };

        return [
            'employee_id' => $find(['employee id', 'employee_id', 'emp id']),
            'employee_name' => $find(['employee name', 'employee_name', 'name']),
            'application' => $find(['application', 'application type', 'leave type']),
            'type' => $find(['type', 'category']),
            'date_filed' => $find(['date filed', 'filed', 'filed_on']),
            'date_from' => $find(['date from', 'from', 'start date', 'start_date']),
            'date_to' => $find(['date to', 'to', 'end date', 'end_date']),
            'total_hours' => $find(['total hours', 'hours']),
            'status' => $find(['status']),
        ];
    }

    /**
     * Accept both Excel serial numbers and string dates.
     */
    private function parseExcelDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                $datetime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                return $datetime->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Parse biometric text extracted from PDF into structured records.
     */
    private function parseBiometricText(string $text): array
    {
        $records = [];
        $lines = preg_split('/\r?\n/', $text);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines, fn($l) => $l !== '');
        $lines = array_values($lines);

        $records = $this->parseNuLipaFormat($lines);

        if (empty($records)) {
            $records = $this->parseTabularFormat($lines);
        }

        if (empty($records)) {
            $records = $this->parseBlockFormat($lines);
        }

        if (empty($records)) {
            $records = $this->parseGenericFormat($text);
        }

        return $records;
    }

    /**
     * Parser for the NU LIPA / Mustard Seed "TIMESHEET REPORT" biometric PDF.
     */
    private function parseNuLipaFormat(array $lines): array
    {
        $records = [];
        $currentEmployee = null;

        foreach ($lines as $line) {
            if (preg_match('/^Employee:\s*(.+?)\s*\(([^)]+)\)\s*$/i', $line, $m)) {
                $currentEmployee = [
                    'id' => trim($m[2]),
                    'name' => trim($m[1]),
                ];
                continue;
            }

            if (! $currentEmployee) {
                continue;
            }

            if (preg_match('/^(Total:|Days\s+Present|Days\s+Absent)/i', $line)) {
                continue;
            }

            if (! preg_match('#^(\d{1,2}/\d{1,2}/\d{4})\s+(.+)$#', $line, $m)) {
                continue;
            }

            $date = $m[1];
            $rest = $m[2];

            if (! preg_match_all('/\d{1,2}:\d{2}\s*(?:am|pm)/i', $rest, $timeMatches)) {
                continue;
            }

            $times = $timeMatches[0];
            $timeIn = $times[0] ?? null;
            $timeOut = count($times) > 1 ? end($times) : null;

            $records[] = [
                'employee_id' => $currentEmployee['id'],
                'name' => $currentEmployee['name'],
                'date' => $this->normalizeDate($date),
                'time_in' => $timeIn ? $this->normalizeTime($timeIn) : null,
                'time_out' => $timeOut ? $this->normalizeTime($timeOut) : null,
            ];
        }

        return $records;
    }

    /**
     * Parse tabular biometric format.
     */
    private function parseTabularFormat(array $lines): array
    {
        $records = [];

        foreach ($lines as $line) {
            if (preg_match('/(\d{4}-\d{3})\s*[|\t]\s*(.+?)\s*[|\t]\s*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}|\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2})\s*[|\t]\s*(\d{1,2}:\d{2}\s*(?:AM|PM)?)\s*[|\t]\s*(\d{1,2}:\d{2}\s*(?:AM|PM)?)/i', $line, $matches)) {
                $records[] = [
                    'employee_id' => $matches[1],
                    'name' => trim($matches[2]),
                    'date' => $this->normalizeDate($matches[3]),
                    'time_in' => $this->normalizeTime($matches[4]),
                    'time_out' => $this->normalizeTime($matches[5]),
                ];
                continue;
            }

            if (preg_match('/(\d{4}-\d{3})\s+([A-Za-z][A-Za-z\s,\.]+?)\s+(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}|\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2})\s+(\d{1,2}:\d{2}\s*(?:AM|PM)?)\s+(\d{1,2}:\d{2}\s*(?:AM|PM)?)/i', $line, $matches)) {
                $records[] = [
                    'employee_id' => $matches[1],
                    'name' => trim($matches[2]),
                    'date' => $this->normalizeDate($matches[3]),
                    'time_in' => $this->normalizeTime($matches[4]),
                    'time_out' => $this->normalizeTime($matches[5]),
                ];
                continue;
            }

            if (preg_match('/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}|\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2})\s+(\d{1,2}:\d{2}\s*(?:AM|PM)?)\s+(\d{1,2}:\d{2}\s*(?:AM|PM)?)/i', $line, $matches)) {
                if (!empty($this->currentParseEmployee)) {
                    $records[] = [
                        'employee_id' => $this->currentParseEmployee['id'],
                        'name' => $this->currentParseEmployee['name'],
                        'date' => $this->normalizeDate($matches[1]),
                        'time_in' => $this->normalizeTime($matches[2]),
                        'time_out' => $this->normalizeTime($matches[3]),
                    ];
                }
            }

            if (preg_match('/^(?:Employee|Emp|ID)?[:\s]*(\d{4}-\d{3})\s*[:\-\s]+\s*([A-Za-z][A-Za-z\s,\.]+)/i', $line, $matches)) {
                $this->currentParseEmployee = [
                    'id' => $matches[1],
                    'name' => trim($matches[2]),
                ];
            }
        }

        return $records;
    }

    private ?array $currentParseEmployee = null;

    /**
     * Parse block format where each employee has a section with daily records.
     */
    private function parseBlockFormat(array $lines): array
    {
        $records = [];
        $currentEmployee = null;

        foreach ($lines as $line) {
            if (preg_match('/(?:name|employee)\s*[:\-]\s*(?:(\d{4}-\d{3})\s*[:\-\s]*)?\s*([A-Za-z][A-Za-z\s,\.]+)/i', $line, $matches)) {
                $currentEmployee = [
                    'id' => $matches[1] ?: '',
                    'name' => trim($matches[2]),
                ];
                continue;
            }

            if ($currentEmployee && empty($currentEmployee['id']) && preg_match('/(?:id|emp[_\s]?id)\s*[:\-]\s*(\d{4}-\d{3})/i', $line, $matches)) {
                $currentEmployee['id'] = $matches[1];
                continue;
            }

            if ($currentEmployee && preg_match('/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}|\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2})\s+(\d{1,2}:\d{2}\s*(?:AM|PM)?)\s+(\d{1,2}:\d{2}\s*(?:AM|PM)?)/i', $line, $matches)) {
                $records[] = [
                    'employee_id' => $currentEmployee['id'],
                    'name' => $currentEmployee['name'],
                    'date' => $this->normalizeDate($matches[1]),
                    'time_in' => $this->normalizeTime($matches[2]),
                    'time_out' => $this->normalizeTime($matches[3]),
                ];
            }
        }

        return $records;
    }

    /**
     * Generic fallback parser.
     */
    private function parseGenericFormat(string $text): array
    {
        $records = [];

        preg_match_all(
            '/(\d{4}-\d{3}).*?(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}|\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2})\s+(\d{1,2}:\d{2}\s*(?:AM|PM)?)\s+(\d{1,2}:\d{2}\s*(?:AM|PM)?)/is',
            $text,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $records[] = [
                'employee_id' => $match[1],
                'name' => '',
                'date' => $this->normalizeDate($match[2]),
                'time_in' => $this->normalizeTime($match[3]),
                'time_out' => $this->normalizeTime($match[4]),
            ];
        }

        return $records;
    }

    /**
     * Normalize various date formats to Y-m-d.
     */
    private function normalizeDate(string $date): string
    {
        $date = trim($date);

        $formats = ['Y-m-d', 'm/d/Y', 'd/m/Y', 'm-d-Y', 'd-m-Y', 'm/d/y', 'd/m/y'];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $date);
                if ($parsed) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return Carbon::parse($date)->format('Y-m-d');
    }

    /**
     * Normalize time to 24-hour format.
     */
    private function normalizeTime(string $time): string
    {
        $time = trim($time);

        if (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)/i', $time, $m)) {
            $h = (int) $m[1];
            $min = $m[2];
            $period = strtoupper($m[3]);

            if ($period === 'PM' && $h !== 12) {
                $h += 12;
            } elseif ($period === 'AM' && $h === 12) {
                $h = 0;
            }

            return sprintf('%02d:%s', $h, $min);
        }

        return $time;
    }

    private function buildAttendanceRows(?Employee $employee, ?Carbon $selectedDate = null): Collection
    {
        $baseDate = $selectedDate ?? now();
        $period = CarbonPeriod::create($baseDate->copy()->startOfMonth(), $baseDate->copy()->endOfMonth());

        $dbRecords = collect();
        if ($employee) {
            $dbRecords = AttendanceRecord::where('employee_id', $employee->id)
                ->whereBetween('record_date', [$baseDate->copy()->startOfMonth(), $baseDate->copy()->endOfMonth()])
                ->get()
                ->keyBy(fn ($r) => $r->record_date->format('Y-m-d'));
        }

        return collect($period)
            ->map(function (Carbon $date, int $index) use ($employee, $dbRecords) {
                $dateKey = $date->format('Y-m-d');

                if ($date->isWeekend()) {
                    return [
                        'date' => $date->format('M j'),
                        'day' => $date->format('D'),
                        'time_in' => '-',
                        'time_out' => '-',
                        'tardiness_minutes' => 0,
                        'undertime_minutes' => 0,
                        'status' => 'Weekend',
                    ];
                }

                if ($dbRecords->has($dateKey)) {
                    $record = $dbRecords->get($dateKey);
                    return [
                        'date' => $date->format('M j'),
                        'day' => $date->format('D'),
                        'time_in' => $record->time_in ? Carbon::parse($record->time_in)->format('H:i') : '-',
                        'time_out' => $record->time_out ? Carbon::parse($record->time_out)->format('H:i') : '-',
                        'tardiness_minutes' => $record->tardiness_minutes,
                        'undertime_minutes' => $record->undertime_minutes,
                        'status' => ucfirst($record->status),
                    ];
                }

                if ($date->isAfter(now())) {
                    return [
                        'date' => $date->format('M j'),
                        'day' => $date->format('D'),
                        'time_in' => '-',
                        'time_out' => '-',
                        'tardiness_minutes' => 0,
                        'undertime_minutes' => 0,
                        'status' => '-',
                    ];
                }

                return [
                    'date' => $date->format('M j'),
                    'day' => $date->format('D'),
                    'time_in' => '-',
                    'time_out' => '-',
                    'tardiness_minutes' => 0,
                    'undertime_minutes' => 0,
                    'status' => 'No record',
                ];
            })
            ->values();
    }
}
