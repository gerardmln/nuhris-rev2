# Leave Balance System - Implementation Guide

## What Has Been Implemented

A complete leave management system with:
1. ✅ Leave balance tracking (VL, SL, EL only)
2. ✅ Base leave credits by employment type
3. ✅ Yearly accrual logic for regular employees
4. ✅ Detailed leave usage breakdown
5. ✅ Deductible vs tracked-only leave classification

---

## Quick Start

### 1. Initialize Employee Leave Balances

**First time setup** - Initialize all employees:
```bash
php artisan leave-balance:initialize --force
```

**Or initialize specific employee**:
```bash
php artisan leave-balance:initialize --employee-id=5
```

This command:
- Calculates base credits based on employment type
- Applies yearly accrual for regular employees with 1+ year service
- Creates `LeaveBalance` records for VL, SL, EL
- Calculates remaining balance = total credits - used days

### 2. View Employee Leave Balances

Employee navigates to **Attendance & DTR** → **Leave Monitoring**:
- Sees remaining balance for VL, SL, EL (only deductible leaves)
- Sees detailed usage breakdown:
  - **Deductible Leaves**: VL, SL, EL (affect balance)
  - **Tracked-Only Leaves**: All other types (no balance deduction)
- Views full leave history table

---

## System Architecture

### Three Main Components

#### 1. LeaveBalanceService
**Location**: `app/Services/LeaveBalanceService.php`

**Key Methods**:
```php
// Initialize or update balance for an employee
initializeOrUpdateBalance(Employee $employee): void

// Get remaining balances for deductible leaves only
getDeductibleLeaveBalances(Employee $employee): Collection

// Get detailed usage breakdown (deductible + tracked)
getLeaveUsageBreakdown(Employee $employee): array

// Get remaining balance for specific leave type
getRemainingBalance(Employee $employee, string $leaveType): float

// Check if leave type affects balance
isDeductibleLeaveType(string $leaveType): bool
```

#### 2. Console Command
**Location**: `app/Console/Commands/InitializeLeaveBalances.php`

**Usage**:
```bash
php artisan leave-balance:initialize [--employee-id=ID] [--force]
```

#### 3. Enhanced Employee Portal
**Controller**: `app/Http/Controllers/Employee/PortalController.php`
**View**: `resources/views/employee/leave.blade.php`

---

## Leave Credit Structure

### Base Credits (Year 1)

| Leave Type | Faculty | ASP |
|---|---|---|
| Vacation Leave | 11 | 9 |
| Sick Leave | 11 | 9 |
| Emergency Leave | 3 | 3 |

### Yearly Increment (Regular Employees Only)

**Eligibility**:
- Faculty: Regular if hired ≥1 year ago
- ASP: Regular if hired ≥6 months ago (but increment after 1 year service)
- Part-Time: NEVER regular (no accrual ever)

**Accrual Per Year**:
- Vacation Leave: +2 (max 15)
- Sick Leave: +2 (max 15)
- Emergency Leave: +0 (fixed at 3)

### Complete Example Timeline

**Faculty Employee Hired May 1, 2024**:
```
May 2024: Year 0
- VL: 11, SL: 11, EL: 3 (base only, not regular yet)

May 2025: Year 1 (NOW REGULAR)
- VL: 11+2=13, SL: 11+2=13, EL: 3 (first accrual)

May 2026: Year 2
- VL: 13+2=15 (reaches max), SL: 13+2=15 (reaches max), EL: 3

May 2027: Year 3+
- VL: 15 (max), SL: 15 (max), EL: 3 (no change, at max)
```

---

## Deductible vs Tracked-Only Leaves

### DEDUCTIBLE LEAVES (Affect Balance)
Only these 3 leave types reduce the remaining balance:
- ✓ **Vacation Leave** (VL)
- ✓ **Sick Leave** (SL)
- ✓ **Emergency Leave** (EL)

### TRACKED-ONLY LEAVES (No Balance Deduction)
These are recorded but do NOT reduce balance:
- Bereavement Leave
- Training Leave
- Official Business
- Work From Home
- Birthday Leave
- Maternity Leave
- Solo Parent Leave
- Paternity Leave
- Special Leave for Women
- Study Leave With Pay
- Study Leave Without Pay
- Leave Without Pay
- Research Leave

---

## Database Schema

### LeaveBalance Table
```sql
CREATE TABLE leave_balances (
  id BIGINT PRIMARY KEY,
  employee_id BIGINT REFERENCES employees,
  leave_type VARCHAR(50),
  remaining_days DECIMAL(5, 2),
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  UNIQUE(employee_id, leave_type)
);
```

**Example Records**:
```
| ID | Employee | Leave Type | Remaining Days |
|----|-----------|-----------|-----------------|
| 1  | John (5) | Vacation Leave | 8.5 |
| 2  | John (5) | Sick Leave | 10.0 |
| 3  | John (5) | Emergency Leave | 3.0 |
| 4  | Jane (6) | Vacation Leave | 6.0 |
| 5  | Jane (6) | Sick Leave | 11.0 |
| 6  | Jane (6) | Emergency Leave | 3.0 |
```

### LeaveRequest Table
```sql
-- Already exists, no changes needed
-- Tracks ALL leave types (deductible + tracked-only)
-- Only approved requests are counted for balance deduction
```

---

## Employee Portal Changes

### Leave Monitoring Page Now Shows

1. **Quick Usage Summary**
   - Vacation Used, Sick Used, Emergency Used (in colored cards)

2. **Remaining Leave Balance**
   - Only shows deductible leaves (VL, SL, EL)
   - Displays remaining days for each type
   - Example: "Vacation Leave: 8.5 remaining days"

3. **Detailed Usage Breakdown** (NEW)
   - **Deductible Leaves Section**:
     - Vacation Leave: 12 days used (3 requests)
     - Sick Leave: 5 days used (2 requests)
     - Emergency Leave: 1 day used (1 request)
   - **Tracked-Only Leaves Section**:
     - All other leave types with usage counts
     - Clearly marked: "Balance Not Affected"
     - Example: "Bereavement Leave: 3 days (1 request)"

4. **Leave History Table**
   - Complete record of all leave requests
   - Shows type, dates, days, status, cutoff, reason

---

## How to Use in Code

### In Controllers
```php
use App\Services\LeaveBalanceService;

public function someAction(LeaveBalanceService $leaveBalanceService)
{
    $employee = auth()->user()->employee();
    
    // Get deductible balances only
    $balances = $leaveBalanceService->getDeductibleLeaveBalances($employee);
    
    // Get detailed breakdown
    $breakdown = $leaveBalanceService->getLeaveUsageBreakdown($employee);
    
    // Check remaining for a specific leave
    $remaining = $leaveBalanceService->getRemainingBalance($employee, 'Vacation Leave');
}
```

### When Approving Leave Requests
```php
// After approving a leave request:
$leaveBalanceService->initializeOrUpdateBalance($employee);
// This recalculates remaining balance
```

### During Employee Onboarding
```php
// When creating a new employee:
$employee = Employee::create([...]);
$leaveBalanceService->initializeOrUpdateBalance($employee);
// This sets up initial leave balances
```

---

## Important Notes

### 1. Regular Employee Status
- Automatically determined by:
  - Employment type (not part-time)
  - Hire date (tenure requirement)
- No manual configuration needed
- Part-Time employees are ALWAYS irregular (no accrual)

### 2. Yearly Accrual
- Calculated based on **complete years of service**
- Currently calculated on-demand (when balance initialized)
- Can be scheduled (recommended: annual job on Jan 1)

### 3. Balance Calculation Formula
```
Remaining Balance = Total Credits - Used Days

Where:
Total Credits = Base Credits + Yearly Accrual (capped at max)
Used Days = Sum of approved leave request days_deducted
```

### 4. No Rollover
- Current implementation does not carry over unused leaves
- Can be configured in future if needed

### 5. Negative Balance
- System allows negative balance (overdraft)
- Shows as negative in remaining_days
- No blocking mechanism (can be added if needed)

---

## Troubleshooting

### Employee Has No Leave Balance
**Cause**: Balance not initialized

**Solution**:
```bash
php artisan leave-balance:initialize --employee-id=<id>
```

### Balance Doesn't Update After Leave Approval
**Cause**: Balance cache or not recalculated

**Solution**:
```bash
php artisan leave-balance:initialize --force
```

### Part-Time Employee Still Shows Balance
**Cause**: Employee type classification

**Solution**: Verify employment_type field:
- Must contain "part-time", "part time", or "parttime" (case-insensitive)
- After correction, run: `php artisan leave-balance:initialize`

### ASP Shows Wrong Credits
**Cause**: Employment type not recognized

**Solution**: Verify employment_type contains:
- "asp" OR
- "admin support" OR
- "admin support personnel"

---

## Future Enhancements

1. **Scheduled Yearly Accrual**
   - Artisan command scheduled job (e.g., Jan 1 yearly)
   - Automatic balance update without manual intervention

2. **Admin Balance Adjustment UI**
   - HR interface to manually adjust balances
   - Audit trail for adjustments

3. **Leave Carryover Rules**
   - Set maximum carryover (e.g., max 5 days from previous year)
   - Automatic carryover logic

4. **Leave Surrender**
   - Track and report unused leaves at end of year
   - Compensation calculations

5. **Balance Warnings**
   - Alert when balance is running low
   - Block leave requests if insufficient balance (optional)

6. **Historical Tracking**
   - Archive old balances for audit
   - Year-over-year comparison reports

---

## Testing Checklist

- [ ] Initialize employee balances with command
- [ ] Verify Faculty Year 1 credits (11, 11, 3)
- [ ] Verify ASP Year 1 credits (9, 9, 3)
- [ ] Verify Part-Time gets no balance
- [ ] Approve leave request and recalculate
- [ ] Check remaining balance decreased correctly
- [ ] Verify deductible vs tracked-only separation in view
- [ ] Confirm non-deductible leaves don't reduce balance
- [ ] Test with multiple employees
- [ ] Check view cache is cleared
