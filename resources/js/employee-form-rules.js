const normalize = (value) => String(value ?? '').trim().toLowerCase();

const teachingKeywords = ['professor', 'dean', 'program chair', 'instructor'];

function isTeachingPosition(position) {
    const normalized = normalize(position);

    return teachingKeywords.some((keyword) => normalized.includes(keyword));
}

function isProfessorPosition(position) {
    return normalize(position).includes('professor');
}

function rankingPrefixForPosition(position) {
    const normalized = normalize(position);

    if (normalized.includes('assistant professor')) {
        return 'assistant professor';
    }

    if (normalized.includes('associate professor')) {
        return 'associate professor';
    }

    if (normalized.includes('full professor')) {
        return 'full professor';
    }

    if (normalized.includes('instructor')) {
        return 'instructor';
    }

    return '';
}

function requiresGroupedRanking(position) {
    return rankingPrefixForPosition(position) !== '';
}

function filterRankingOptions(rankingControl, position) {
    if (!rankingControl) {
        return;
    }

    const prefix = rankingPrefixForPosition(position);
    const options = Array.from(rankingControl.options);
    let selectedStillVisible = false;

    options.forEach((option) => {
        if (option.value === '') {
            option.hidden = false;
            option.disabled = false;
            if (option.value === rankingControl.value) {
                selectedStillVisible = true;
            }
            return;
        }

        const optionValue = normalize(option.value);
        const matches = !prefix || optionValue.startsWith(prefix);

        option.hidden = !matches;
        option.disabled = !matches;

        if (matches && option.value === rankingControl.value) {
            selectedStillVisible = true;
        }
    });

    if (!selectedStillVisible && prefix) {
        rankingControl.value = '';
    }
}

function getControl(form, name) {
    return form.querySelector(`[data-employee-control="${name}"]`);
}

function getField(form, name) {
    return form.querySelector(`[data-employee-field="${name}"]`);
}

/**
 * Map the selected employment type onto the value each <option data-employment-category>
 * uses, so we can only show the positions that belong to that type.
 *
 * Faculty                  -> "faculty"
 * Admin Support Personnel  -> "asp"
 */
function employmentCategory(type) {
    const normalized = normalize(type);

    if (!normalized) {
        return '';
    }

    if (normalized.includes('faculty')) {
        return 'faculty';
    }

    if (normalized.includes('admin') || normalized === 'asp') {
        return 'asp';
    }

    return '';
}

/**
 * Show only the <option> elements whose data-employment-category matches the
 * currently selected employment type. The empty placeholder option is always
 * kept visible. If the current value no longer belongs to the allowed list,
 * it is reset so the user picks a valid position.
 */
function filterPositionOptions(positionControl, employmentType) {
    if (!positionControl) {
        return;
    }

    const category = employmentCategory(employmentType);
    const options = Array.from(positionControl.options);
    let selectedStillVisible = false;

    options.forEach((option) => {
        const optionCategory = option.dataset.employmentCategory || '';

        // Placeholder (value === "") should always remain selectable.
        if (option.value === '') {
            option.hidden = false;
            option.disabled = false;
            return;
        }

        const matches = category === '' || optionCategory === category;
        option.hidden = !matches;
        option.disabled = !matches;

        if (matches && option.value === positionControl.value) {
            selectedStillVisible = true;
        }
    });

    if (!selectedStillVisible && category !== '') {
        positionControl.value = '';
    }
}

function updateEmployeeFormState(form) {
    const employmentType = getControl(form, 'employment_type')?.value ?? '';
    const positionControl = getControl(form, 'position');

    filterPositionOptions(positionControl, employmentType);

    const position = positionControl?.value ?? '';

    const departmentField = getField(form, 'department');
    const departmentControl = getControl(form, 'department');
    const rankingField = getField(form, 'ranking');
    const rankingControl = getControl(form, 'ranking');

    const needsDepartment = isTeachingPosition(position);
    const needsRanking = requiresGroupedRanking(position);

    if (departmentField && departmentControl) {
        departmentField.classList.toggle('hidden', !needsDepartment);
        departmentControl.required = needsDepartment;

        if (!needsDepartment) {
            departmentControl.value = '';
        }
    }

    if (rankingField && rankingControl) {
        filterRankingOptions(rankingControl, position);

        rankingField.classList.toggle('hidden', !needsRanking);
        rankingControl.required = needsRanking;

        if (!needsRanking) {
            rankingControl.value = '';
        }
    }
}

function initializeEmployeeForm(form) {
    const typeControl = getControl(form, 'employment_type');
    const positionControl = getControl(form, 'position');

    if (!positionControl) {
        return;
    }

    const handleChange = () => updateEmployeeFormState(form);

    positionControl.addEventListener('change', handleChange);

    if (typeControl) {
        typeControl.addEventListener('change', handleChange);
    }

    updateEmployeeFormState(form);
}

function initializeEmployeeForms() {
    document.querySelectorAll('[data-employee-form]').forEach((form) => {
        initializeEmployeeForm(form);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeEmployeeForms);
} else {
    initializeEmployeeForms();
}
