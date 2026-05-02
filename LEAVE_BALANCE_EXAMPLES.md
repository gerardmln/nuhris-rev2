# Leave Balance Calculation Examples

## Scenario 1: Faculty Employee - Year 1 (Just Hired)

**Employee Details**:
- Employment Type: Faculty
- Hire Date: May 1, 2026 (4 months ago, not yet 1 year)
- Status: Not yet regular

**Leave Credits Calculation**:
```
Condition: Must have 1+ year service to be regular
Since hired 4 months ago: NOT REGULAR

Base Credits Applied:
- Vacation Leave: 11 days
- Sick Leave: 11 days
- Emergency Leave: 3 days

Accrual: NONE (not yet regular)

Total Credits:
- Vacation Leave: 11
- Sick Leave: 11
- Emergency Leave: 3
```

**Usage Example**:
- Approved Vacation Leave: 5 days
- Approved Sick Leave: 2 days

**Remaining Balance**:
- Vacation Leave: 11 - 5 = 6 days ✓
- Sick Leave: 11 - 2 = 9 days ✓
- Emergency Leave: 3 - 0 = 3 days ✓

---

## Scenario 2: Faculty Employee - Year 1.5 (Just Became Regular)

**Employee Details**:
- Employment Type: Faculty
- Hire Date: May 1, 2025 (13 months ago)
- Status: NOW REGULAR (1+ year service)

**Leave Credits Calculation**:
```
Condition: 1+ year service = REGULAR ✓

Base Credits: 11 each
Accrual Years: 1 complete year (May 2025 to May 2026)

Per Year Increment:
- Vacation Leave: +2
- Sick Leave: +2
- Emergency Leave: +0 (fixed)

Total Credits:
- Vacation Leave: 11 + 2 = 13 days
- Sick Leave: 11 + 2 = 13 days
- Emergency Leave: 3 (no increment)
```

**Usage Example**:
- Approved Vacation Leave: 5 days
- Approved Sick Leave: 3 days
- Approved Emergency Leave: 1 day

**Remaining Balance**:
- Vacation Leave: 13 - 5 = 8 days ✓
- Sick Leave: 13 - 3 = 10 days ✓
- Emergency Leave: 3 - 1 = 2 days ✓

---

## Scenario 3: Faculty Employee - Year 3 (Long Service)

**Employee Details**:
- Employment Type: Faculty
- Hire Date: May 1, 2023 (3 years ago)
- Status: REGULAR (1+ year service)

**Leave Credits Calculation**:
```
Condition: 1+ year service = REGULAR ✓

Base Credits: 11 each
Accrual Years: 3 complete years

Per Year Increment:
- Year 1: +2 each (11+2=13)
- Year 2: +2 each (13+2=15) ← REACHES MAX
- Year 3: +0 each (already at max 15)

Total Credits (Cannot exceed maximum):
- Vacation Leave: 15 (max reached in year 2)
- Sick Leave: 15 (max reached in year 2)
- Emergency Leave: 3 (never increments)
```

**Usage Example**:
- Approved Vacation Leave: 8 days
- Approved Sick Leave: 6 days
- Approved Emergency Leave: 0 days

**Remaining Balance**:
- Vacation Leave: 15 - 8 = 7 days ✓
- Sick Leave: 15 - 6 = 9 days ✓
- Emergency Leave: 3 - 0 = 3 days ✓

---

## Scenario 4: ASP Employee - Year 1 (Just Hired)

**Employee Details**:
- Employment Type: Admin Support Personnel
- Hire Date: May 1, 2026 (4 months ago)
- Status: Not yet regular

**Leave Credits Calculation**:
```
Condition: Must have 1+ year service to be regular
ASP requires 6 months minimum (not yet met for year 1)
Since hired 4 months ago: NOT REGULAR

Base Credits Applied (ASP Lower Than Faculty):
- Vacation Leave: 9 days
- Sick Leave: 9 days
- Emergency Leave: 3 days

Total Credits:
- Vacation Leave: 9
- Sick Leave: 9
- Emergency Leave: 3
```

---

## Scenario 5: ASP Employee - Year 1 (Became Regular at 6 Months)

**Employee Details**:
- Employment Type: Admin Support Personnel
- Hire Date: November 1, 2025 (7 months ago)
- Status: NOW REGULAR (6+ months service)

**Leave Credits Calculation**:
```
Condition: 6+ months service = REGULAR ✓

Base Credits (ASP): 9 each
Accrual: YES (regular with service time)

Years of service: 0.58 years (7 months)
Complete years: 0 (need full year for increment)

Total Credits (no complete year yet):
- Vacation Leave: 9 (no accrual yet)
- Sick Leave: 9 (no accrual yet)
- Emergency Leave: 3
```

---

## Scenario 6: ASP Employee - Year 2 (1.5 Years Service)

**Employee Details**:
- Employment Type: Admin Support Personnel
- Hire Date: November 1, 2024 (18 months ago)
- Status: REGULAR (6+ months service)

**Leave Credits Calculation**:
```
Condition: 6+ months service = REGULAR ✓

Base Credits (ASP): 9 each
Complete years of service: 1

Accrual Calculation:
- Year 1: +2 each (9+2=11)

Total Credits:
- Vacation Leave: 11
- Sick Leave: 11
- Emergency Leave: 3
```

---

## Scenario 7: Part-Time Faculty (Never Regular)

**Employee Details**:
- Employment Type: Part-Time Faculty
- Hire Date: May 1, 2022 (4 years ago)
- Status: NEVER REGULAR (part-time = always irregular)

**Leave Credits Calculation**:
```
Condition: Part-time employees are NEVER regular
(Regardless of service time)

Base Credits: NONE
Accrual: NONE (not regular)

Total Credits:
- Vacation Leave: 0 (not eligible)
- Sick Leave: 0 (not eligible)
- Emergency Leave: 0 (not eligible)

Result: No leave balance tracking for part-time
```

---

## Leave Usage Breakdown Display

### Example Employee Leave History

**Deductible Leaves** (Affect Balance):
- ✓ Vacation Leave: 12 days used (3 requests)
- ✓ Sick Leave: 5 days used (2 requests)
- ✓ Emergency Leave: 1 day used (1 request)

**Tracked-Only Leaves** (No Balance Deduction):
- Bereavement Leave: 3 days (1 request)
- Training Leave: 8 days (1 request)
- Official Business: 2 days (2 requests)
- Work From Home: 15 days (monthly)
- Birthday Leave: 1 day (1 request)
- Research Leave: 5 days (1 request)
- Solo Parent Leave: 0 days
- Paternity Leave: 0 days
- Study Leave With Pay: 0 days
- Study Leave Without Pay: 0 days
- Leave Without Pay: 0 days
- Maternity Leave: 0 days
- Special Leave for Women: 0 days

**Summary**:
- Total Deductible Days Used: 18 days
- Remaining Balance: 15 - 18 = -3 (OVERDRAFT!)
- Total Tracked (Non-deductible) Days: 34 days
  - These are recorded but do NOT affect balance

---

## Implementation Notes

1. **Regular Status Check**:
   - Runs automatically when calculating balances
   - Checked against hire_date and employment_type
   - Part-time employees are always irregular

2. **Accrual Timing**:
   - Currently calculated based on elapsed time
   - Can be scheduled for specific dates (e.g., Jan 1 yearly)
   - Respects maximum credits (15 for VL/SL, 3 for EL)

3. **Balance Updates**:
   - Updated when leave requests are approved/rejected
   - Recalculated when running `leave-balance:initialize`
   - No automatic rollover (configurable in future)

4. **Leave Request Processing**:
   - Only deductible leaves deduct from balance
   - All leaves are tracked in database
   - Approved status required for calculation
