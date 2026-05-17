<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RoleManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $roleFilter = $request->string('role')->toString() ?: 'all';
        $statusFilter = $request->string('status')->toString() ?: 'all';
        $departmentId = $request->string('department_id')->toString();

        $users = User::query()
            ->with('employeeProfile')
            ->orderBy('name')
            ->when($search, function ($query, $searchTerm) {
                $query->where(function ($nested) use ($searchTerm) {
                    $nested->where('name', 'like', '%'.$searchTerm.'%')
                        ->orWhere('email', 'like', '%'.$searchTerm.'%');
                });
            })
            ->when($roleFilter !== 'all', fn ($query) => $query->where('user_type', $roleFilter))
            ->when($statusFilter === 'active', fn ($query) => $query->whereNotNull('email_verified_at'))
            ->when($statusFilter === 'inactive', fn ($query) => $query->whereNull('email_verified_at'))
            ->when(filled($departmentId), function ($query) use ($departmentId) {
                if ($departmentId === 'asp') {
                    $query->whereHas('employeeProfile', fn ($employeeQuery) => $employeeQuery->where('employment_type', 'Admin Support Personnel'));

                    return;
                }

                $query->whereHas('employeeProfile', fn ($employeeQuery) => $employeeQuery->where('department_id', $departmentId));
            })
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $this->roleLabel($user->user_type),
                    'user_type' => $user->user_type,
                    'department' => $user->employeeProfile?->department?->name ?? 'N/A',
                    'status' => $user->email_verified_at ? 'Active' : 'Inactive',
                    'created_at' => $user->created_at?->format('M d, Y'),
                ];
            });

        $roleDistribution = [
            'Admin' => User::query()->where('user_type', User::TYPE_ADMIN)->count(),
            'HR' => User::query()->where('user_type', User::TYPE_HR)->count(),
            'Employee' => User::query()->where('user_type', User::TYPE_EMPLOYEE)->count(),
        ];

        return view('admin.role-management.index', [
            'users' => $users,
            'roleDistribution' => $roleDistribution,
            'roles' => [
                ['value' => User::TYPE_ADMIN, 'label' => 'Admin'],
                ['value' => User::TYPE_HR, 'label' => 'HR'],
                ['value' => User::TYPE_EMPLOYEE, 'label' => 'Employee'],
            ],
            'departments' => Department::query()->orderBy('name')->get(),
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
                'status' => $statusFilter,
                'department_id' => $departmentId,
            ],
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'user_type' => 'required|integer|in:' . User::TYPE_ADMIN . ',' . User::TYPE_HR . ',' . User::TYPE_EMPLOYEE,
        ]);

        // Prevent removing all admins
        if ($validated['user_type'] !== User::TYPE_ADMIN && $user->user_type === User::TYPE_ADMIN) {
            $adminCount = User::query()->where('user_type', User::TYPE_ADMIN)->count();
            if ($adminCount <= 1) {
                return redirect()->back()
                    ->with('error', 'Cannot remove the last Admin user. At least one Admin must exist.');
            }
        }

        $oldRole = $this->roleLabel($user->user_type);
        $newRole = $this->roleLabel($validated['user_type']);

        $user->update($validated);

        return redirect()->route('admin.roles.index')
            ->with('success', "{$user->name}'s role has been changed from {$oldRole} to {$newRole}.");
    }

    private function roleLabel(int $userType): string
    {
        return match ($userType) {
            User::TYPE_ADMIN => 'Admin',
            User::TYPE_HR => 'HR',
            User::TYPE_EMPLOYEE => 'Employee',
            default => 'Unknown',
        };
    }
}
