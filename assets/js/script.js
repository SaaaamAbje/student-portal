/**
 * Student Portal - Client-side Scripts
 * Phase 6: JavaScript enhancements
 * Phase 9: Auto-remarks live suggestion + lock-awareness
 *
 * Features:
 *  - Live "Final Grade" preview on the teacher's Input Grades page
 *  - Live auto-remarks suggestion hint (mirrors PHP-side auto_remarks() thresholds)
 *  - Client-side validation for grade inputs (0-100 range)
 *  - Auto-dismissing success alerts
 *  - Unsaved-changes warning when leaving a grade entry page
 *  - Simple client-side table search/filter (for tables with .js-table-search)
 *  - Confirm dialogs are handled inline via onclick="return confirm(...)" in PHP
 */

// Keep these in sync with includes/grade_helpers.php GRADE_PASSING_THRESHOLD / GRADE_INCOMPLETE_THRESHOLD
const GRADE_PASSING_THRESHOLD = 75;
const GRADE_INCOMPLETE_THRESHOLD = 50;

document.addEventListener('DOMContentLoaded', function () {
    initGradePreview();
    initGradeValidation();
    initAlertAutoDismiss();
    initUnsavedChangesWarning();
    initTableSearch();
});

/* ============================================================
   1. Live Final Grade Preview + Auto-Remarks Suggestion
   Recalculates and displays the Final Grade (50/50 average of
   Midterm + Finals) as the teacher types, before saving, and
   shows a suggested remark next to the (unlocked) remarks
   dropdown so the teacher knows what will be auto-applied if
   they leave it on "-- Auto --".
   ============================================================ */
function initGradePreview() {
    const midtermInputs = document.querySelectorAll('.grade-midterm');

    midtermInputs.forEach(function (midtermInput) {
        const formId = midtermInput.dataset.form;
        const finalsInput = document.querySelector('.grade-finals[data-form="' + formId + '"]');
        const previewSpan = document.querySelector('.final-grade-preview[data-form="' + formId + '"]');
        const remarksSelect = document.querySelector('.auto-remarks-select[data-form="' + formId + '"]');

        if (!finalsInput || !previewSpan) return;

        // Create (or reuse) a small hint element under the remarks dropdown
        let hint = null;
        if (remarksSelect) {
            hint = remarksSelect.parentElement.querySelector('.auto-remarks-hint');
            if (!hint) {
                hint = document.createElement('div');
                hint.className = 'auto-remarks-hint';
                remarksSelect.insertAdjacentElement('afterend', hint);
            }
        }

        function suggestRemark(finalGrade) {
            if (finalGrade === null) return null;
            if (finalGrade >= GRADE_PASSING_THRESHOLD) return 'Passed';
            if (finalGrade >= GRADE_INCOMPLETE_THRESHOLD) return 'Incomplete';
            return 'Failed';
        }

        function updatePreview() {
            const midterm = parseFloat(midtermInput.value);
            const finals = parseFloat(finalsInput.value);

            let finalGrade = null;
            if (!isNaN(midterm) && !isNaN(finals)) {
                finalGrade = Math.round(((midterm * 0.5) + (finals * 0.5)) * 100) / 100;
                previewSpan.textContent = finalGrade.toFixed(2);
                previewSpan.classList.add('preview-updated');
            } else {
                previewSpan.textContent = '-';
                previewSpan.classList.remove('preview-updated');
            }

            if (hint && remarksSelect && !remarksSelect.disabled) {
                const suggestion = suggestRemark(finalGrade);
                if (suggestion && remarksSelect.value === '') {
                    hint.textContent = 'Will auto-set: ' + suggestion;
                    hint.className = 'auto-remarks-hint auto-remarks-hint-' + suggestion.toLowerCase();
                } else {
                    hint.textContent = '';
                    hint.className = 'auto-remarks-hint';
                }
            }
        }

        midtermInput.addEventListener('input', updatePreview);
        finalsInput.addEventListener('input', updatePreview);
        if (remarksSelect) {
            remarksSelect.addEventListener('change', updatePreview);
        }

        // Run once on load in case fields are pre-filled
        updatePreview();
    });
}

/* ============================================================
   2. Grade Input Validation
   Ensures grade values stay within 0-100 and warns the user
   visually (red border) for invalid values before submission.
   ============================================================ */
function initGradeValidation() {
    const gradeInputs = document.querySelectorAll('.grade-input[type="number"]');

    gradeInputs.forEach(function (input) {
        input.addEventListener('input', function () {
            validateGradeInput(input);
        });

        input.addEventListener('blur', function () {
            validateGradeInput(input);
        });
    });

    // Validate inputs belonging to a form before submission
    const allForms = document.querySelectorAll('form');

    allForms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            let relatedInputs = [];

            if (form.id) {
                // Inputs linked via the "form" attribute (outside the <form> tag)
                relatedInputs = Array.from(document.querySelectorAll('[form="' + form.id + '"].grade-input[type="number"]'));
            }

            // Also include any grade inputs nested directly inside this form
            relatedInputs = relatedInputs.concat(Array.from(form.querySelectorAll('.grade-input[type="number"]')));

            if (relatedInputs.length === 0) return;

            let hasError = false;
            relatedInputs.forEach(function (input) {
                if (!validateGradeInput(input)) {
                    hasError = true;
                }
            });

            if (hasError) {
                e.preventDefault();
                alert('Please enter grade values between 0 and 100.');
            }
        });
    });
}

function validateGradeInput(input) {
    const value = input.value.trim();

    // Empty is allowed (means "not graded yet")
    if (value === '') {
        input.classList.remove('input-invalid');
        return true;
    }

    const num = parseFloat(value);
    if (isNaN(num) || num < 0 || num > 100) {
        input.classList.add('input-invalid');
        return false;
    }

    input.classList.remove('input-invalid');
    return true;
}

/* ============================================================
   3. Auto-dismiss Alerts
   Success alerts fade out automatically after a few seconds
   so they don't clutter the page on repeated form submits.
   ============================================================ */
function initAlertAutoDismiss() {
    const alerts = document.querySelectorAll('.alert-success');

    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';

            setTimeout(function () {
                alert.style.display = 'none';
            }, 500);
        }, 4000);
    });
}

/* ============================================================
   4. Unsaved Changes Warning
   Warns the user if they try to navigate away from a page
   after editing a grade field without saving it.
   ============================================================ */
function initUnsavedChangesWarning() {
    const trackedInputs = document.querySelectorAll('.grade-input[form], .grade-input');

    if (trackedInputs.length === 0) return;

    let hasUnsavedChanges = false;
    let formsBeingSubmitted = new Set();

    trackedInputs.forEach(function (input) {
        input.addEventListener('input', function () {
            hasUnsavedChanges = true;
        });
    });

    // When any grade-related form is submitted, allow navigation
    document.querySelectorAll('form').forEach(function (form) {
        const hasGradeInput = form.querySelector('.grade-input') ||
            (form.id && document.querySelector('[form="' + form.id + '"].grade-input'));

        if (hasGradeInput) {
            form.addEventListener('submit', function () {
                hasUnsavedChanges = false;
            });
        }
    });

    window.addEventListener('beforeunload', function (e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
}

/* ============================================================
   5. Simple Table Search/Filter
   For any input with class "js-table-search" that has a
   data-table attribute pointing to a table's id, filters
   rows by matching text content.
   ============================================================ */
function initTableSearch() {
    const searchInputs = document.querySelectorAll('.js-table-search');

    searchInputs.forEach(function (input) {
        const tableId = input.dataset.table;
        const table = document.getElementById(tableId);
        if (!table) return;

        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        const rows = tbody.querySelectorAll('tr');

        input.addEventListener('input', function () {
            const query = input.value.trim().toLowerCase();

            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    });
}

/* ============================================================
   Phase 13 — Section A: Dark Mode
   Priority: localStorage → system preference → light
   ============================================================ */

(function initDarkMode() {
    const STORAGE_KEY = 'sp-theme';
    const html = document.documentElement;

    function getPreferred() {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored === 'dark' || stored === 'light') return stored;
        // Fall back to OS preference
        return window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark' : 'light';
    }

    function applyTheme(theme) {
        html.setAttribute('data-theme', theme);
        localStorage.setItem(STORAGE_KEY, theme);

        // Update aria-label on any toggle buttons already in the DOM
        document.querySelectorAll('.nav-dark-toggle').forEach(function (btn) {
            btn.setAttribute('aria-label',
                theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'
            );
            btn.setAttribute('title',
                theme === 'dark' ? 'Light mode' : 'Dark mode'
            );
        });
    }

    // Apply immediately (before paint) to prevent flash
    applyTheme(getPreferred());

    // Expose global toggle function called by the button onclick
    window.toggleDarkMode = function () {
        const current = html.getAttribute('data-theme');
        applyTheme(current === 'dark' ? 'light' : 'dark');
    };

    // React to OS preference changes (e.g. user switches system theme)
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
        // Only follow OS if user hasn't made an explicit choice this session
        if (!localStorage.getItem(STORAGE_KEY)) {
            applyTheme(e.matches ? 'dark' : 'light');
        }
    });
}());

/* ============================================================
   Phase 13 — Section B: Hamburger / Mobile Nav Drawer
   ============================================================ */

(function initHamburger() {

    // Runs once DOM is ready; safe to call multiple times (idempotent)
    function setup() {
        const hamburgers = document.querySelectorAll('.nav-hamburger');

        hamburgers.forEach(function (btn) {
            // Find this nav's links list and overlay
            const navbar   = btn.closest('.navbar');
            if (!navbar) return;

            const navId    = btn.getAttribute('aria-controls');
            const links    = document.getElementById(navId);
            const overlayId = 'nav-overlay-' + navId;
            const overlay  = document.getElementById(overlayId);

            if (!links) return;

            function open() {
                links.classList.add('nav-open');
                btn.setAttribute('aria-expanded', 'true');
                document.body.style.overflow = 'hidden'; // prevent background scroll
                if (overlay) overlay.classList.add('visible');
            }

            function close() {
                links.classList.remove('nav-open');
                btn.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
                if (overlay) overlay.classList.remove('visible');
            }

            // Expose so inline onclick="closeNav()" still works
            window.closeNav = close;

            // Update global toggleNav to target this instance
            window.toggleNav = function (clickedBtn) {
                const expanded = clickedBtn.getAttribute('aria-expanded') === 'true';
                expanded ? close() : open();
            };

            // Close when a nav link is clicked (SPA-style single-page feel)
            links.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    // Small delay so the link href navigates before drawer closes
                    setTimeout(close, 80);
                });
            });
        });

        // Close drawer on ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.navbar-links.nav-open').forEach(function (el) {
                    el.classList.remove('nav-open');
                    document.body.style.overflow = '';
                });
                document.querySelectorAll('.nav-hamburger').forEach(function (btn) {
                    btn.setAttribute('aria-expanded', 'false');
                });
                document.querySelectorAll('.nav-overlay').forEach(function (ov) {
                    ov.classList.remove('visible');
                });
            }
        });

        // Close drawer on resize back to desktop (avoids stuck-open state)
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                document.querySelectorAll('.navbar-links').forEach(function (el) {
                    el.classList.remove('nav-open');
                });
                document.querySelectorAll('.nav-hamburger').forEach(function (btn) {
                    btn.setAttribute('aria-expanded', 'false');
                });
                document.querySelectorAll('.nav-overlay').forEach(function (ov) {
                    ov.classList.remove('visible');
                });
                document.body.style.overflow = '';
            }
        });
    }

    // Run immediately if DOM is ready, otherwise wait
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setup);
    } else {
        setup();
    }
}());