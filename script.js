// ============================================================================
// PART 1: LOGIN & SIGNUP PAGES (index.php) VALIDATIONS
// ============================================================================

// Tab Switching Logic
function switchTab(tabId) {
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => content.classList.remove('active'));

    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => btn.classList.remove('active'));

    document.getElementById(tabId).classList.add('active');
    
    const activeBtn = Array.from(buttons).find(btn => btn.getAttribute('onclick').includes(tabId));
    if (activeBtn) {
        activeBtn.classList.add('active');
    }
}

// Fade out session alerts after 5 seconds
window.addEventListener('DOMContentLoaded', () => {
    const alert = document.getElementById('session-alert');
    if (alert) {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    }
    
    const dashAlert = document.getElementById('dashboard-alert');
    if (dashAlert) {
        setTimeout(() => {
            dashAlert.style.transition = 'opacity 0.5s ease';
            dashAlert.style.opacity = '0';
            setTimeout(() => dashAlert.remove(), 500);
        }, 5000);
    }
});

// Signup Form Validation Logic
const signupForm = document.getElementById('signup-form');
const signupName = document.getElementById('signup_name');
const signupDesignation = document.getElementById('signup_designation');
const signupPhone = document.getElementById('signup_phone');
const signupEmail = document.getElementById('signup_email');
const signupSubmitBtn = document.getElementById('signup-submit-btn');

const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
const phoneRegex = /^\d{10}$/;

const signupErrors = {
    name: false,
    designation: false,
    phone: false,
    email: false
};

function validateSignupForm() {
    if (!signupForm) return;

    // Name validation
    const nameVal = signupName.value.trim();
    if (nameVal.length < 2) {
        showError(signupName, 'signup-name-error', 'Name must be at least 2 characters.');
        signupErrors.name = false;
    } else if (/\d/.test(nameVal)) {
        showError(signupName, 'signup-name-error', 'Name cannot contain numbers.');
        signupErrors.name = false;
    } else {
        showSuccess(signupName, 'signup-name-error');
        signupErrors.name = true;
    }

    // Designation validation
    if (signupDesignation.value === '') {
        showError(signupDesignation, '', '');
        signupErrors.designation = false;
    } else {
        showSuccess(signupDesignation, '');
        signupErrors.designation = true;
    }

    // Phone validation
    const phoneVal = signupPhone.value.trim();
    if (phoneVal === '') {
        showError(signupPhone, 'signup-phone-error', 'Phone number is required.');
        signupErrors.phone = false;
    } else if (!phoneRegex.test(phoneVal)) {
        showError(signupPhone, 'signup-phone-error', 'Phone number must be exactly 10 digits.');
        signupErrors.phone = false;
    } else {
        showSuccess(signupPhone, 'signup-phone-error');
        signupErrors.phone = true;
    }

    // Email validation
    const emailVal = signupEmail.value.trim();
    if (emailVal === '') {
        showError(signupEmail, 'signup-email-error', 'Email address is required.');
        signupErrors.email = false;
    } else if (!emailRegex.test(emailVal)) {
        showError(signupEmail, 'signup-email-error', 'Please enter a valid email address.');
        signupErrors.email = false;
    } else {
        showSuccess(signupEmail, 'signup-email-error');
        signupErrors.email = true;
    }

    const allValid = signupErrors.name && signupErrors.designation && signupErrors.phone && signupErrors.email;
    signupSubmitBtn.disabled = !allValid;
}

if (signupName) signupName.addEventListener('input', validateSignupForm);
if (signupDesignation) signupDesignation.addEventListener('change', validateSignupForm);
if (signupPhone) signupPhone.addEventListener('input', validateSignupForm);
if (signupEmail) signupEmail.addEventListener('input', validateSignupForm);


// Login Form Validation Logic
const loginForm = document.getElementById('login-form');
const loginIc = document.getElementById('login_ic');
const loginDesignation = document.getElementById('login_designation');
const loginSubmitBtn = document.getElementById('login-submit-btn');

const loginErrors = {
    ic: false,
    designation: false
};

function validateLoginForm() {
    if (!loginForm) return;

    // IC validation
    const icVal = loginIc.value.trim();
    if (icVal === '') {
        showError(loginIc, 'login-ic-error', 'IC number is required.');
        loginErrors.ic = false;
    } else if (!/^\d+$/.test(icVal)) {
        showError(loginIc, 'login-ic-error', 'IC number must be numbers only.');
        loginErrors.ic = false;
    } else if (parseInt(icVal) < 1001) {
        showError(loginIc, 'login-ic-error', 'IC number starts from 1001.');
        loginErrors.ic = false;
    } else {
        showSuccess(loginIc, 'login-ic-error');
        loginErrors.ic = true;
    }

    // Designation validation
    if (loginDesignation.value === '') {
        loginErrors.designation = false;
    } else {
        loginErrors.designation = true;
    }

    const allValid = loginErrors.ic && loginErrors.designation;
    loginSubmitBtn.disabled = !allValid;
}

if (loginIc) loginIc.addEventListener('input', validateLoginForm);
if (loginDesignation) loginDesignation.addEventListener('change', validateLoginForm);


// Helper functions for showing dynamic error/success states
function showError(inputElement, errorElementId, message) {
    if (!inputElement) return;
    inputElement.classList.add('invalid');
    inputElement.classList.remove('valid');
    if (errorElementId) {
        const errEl = document.getElementById(errorElementId);
        if (errEl) errEl.innerText = message;
    }
}

function showSuccess(inputElement, errorElementId) {
    if (!inputElement) return;
    inputElement.classList.remove('invalid');
    inputElement.classList.add('valid');
    if (errorElementId) {
        const errEl = document.getElementById(errorElementId);
        if (errEl) errEl.innerText = '';
    }
}

// ============================================================================
// PART 2: DASHBOARD MANAGEMENT LOGIC (dashboard.php)
// ============================================================================

// Sidebar sliding animation toggle
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    const toggleBtn = document.getElementById('toggle-sidebar-btn');
    
    if (!sidebar || !mainContent || !toggleBtn) return;

    sidebar.classList.toggle('collapsed');
    sidebar.classList.toggle('active'); // active is for mobile slide-drawer overlay
    mainContent.classList.toggle('expanded');
    
    if (sidebar.classList.contains('collapsed')) {
        toggleBtn.innerHTML = '☰';
    } else {
        toggleBtn.innerHTML = '✕';
    }
}

// Switching panel tabs
function showPanel(panelName) {
    const panels = document.querySelectorAll('.content-panel');
    panels.forEach(p => p.classList.remove('active'));
    
    const activePanel = document.getElementById('panel-' + panelName);
    if (activePanel) {
        activePanel.classList.add('active');
    }

    // Manage nav button active classes
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        if (item.tagName === 'BUTTON') {
            item.classList.remove('active');
            if (item.getAttribute('onclick') && item.getAttribute('onclick').includes(panelName)) {
                item.classList.add('active');
            }
        }
    });

    // Close sidebar on mobile after choosing a link
    if (window.innerWidth <= 768) {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        const toggleBtn = document.getElementById('toggle-sidebar-btn');
        
        if (sidebar && mainContent && toggleBtn) {
            sidebar.classList.add('collapsed');
            sidebar.classList.remove('active');
            mainContent.classList.add('expanded');
            toggleBtn.innerHTML = '☰';
        }
    }
}

// Checkbox select all management (Get Report page)
function toggleSelectAll(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.emp-checkbox');
    checkboxes.forEach(chk => {
        chk.checked = masterCheckbox.checked;
    });
}

// ============================================================================
// PART 3: MODAL CONTROLS & LOADERS
// ============================================================================

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modals when clicking background overlay
window.addEventListener('click', (event) => {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
});

// View Employee Details Modal Loader (Admin Eye Icon)
function openViewModal(employee, workshops) {
    document.getElementById('view-ic').value = employee.ic_no;
    document.getElementById('view-name').value = employee.name;
    document.getElementById('view-phone').value = employee.phone;
    document.getElementById('view-email').value = employee.email;
    
    // Formatting created date
    const date = new Date(employee.created_at);
    document.getElementById('view-date').value = date.toLocaleString();
    
    // Populate workshops list in the unique timeline representation
    const timeline = document.getElementById('view-workshops-timeline');
    if (timeline) {
        timeline.innerHTML = '';
        
        if (workshops && workshops.length > 0) {
            workshops.forEach(ws => {
                const item = document.createElement('div');
                item.className = 'timeline-item';
                
                // Convert YYYY-MM-DD to DD-MM-YYYY
                const dateParts = ws.attended_date.split('-');
                const formattedDate = dateParts.length === 3 ? `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}` : ws.attended_date;
                
                item.innerHTML = `
                    <div class="timeline-badge"></div>
                    <div class="timeline-panel">
                        <span class="timeline-date-label">${formattedDate}</span>
                        <p class="timeline-title-label">${ws.title}</p>
                    </div>
                `;
                timeline.appendChild(item);
            });
        } else {
            timeline.innerHTML = '<p style="color: #888; font-style: italic; font-size: 13px; text-align: center; margin: 15px 0;">No workshops registered yet.</p>';
        }
    }
    
    openModal('view-modal');
}

// Edit Employee details Modal Loader
const updateName = document.getElementById('update-name');
const updatePhone = document.getElementById('update-phone');
const updateEmail = document.getElementById('update-email');
const updateSubmitBtn = document.getElementById('update-submit-btn');

const updateErrors = {
    name: true,
    phone: true,
    email: true
};

function openUpdateModal(employee) {
    document.getElementById('update-ic').value = employee.ic_no;
    updateName.value = employee.name;
    updatePhone.value = employee.phone;
    updateEmail.value = employee.email;

    // Reset error styling
    showSuccess(updateName, 'update-name-error');
    showSuccess(updatePhone, 'update-phone-error');
    showSuccess(updateEmail, 'update-email-error');

    updateErrors.name = true;
    updateErrors.phone = true;
    updateErrors.email = true;
    updateSubmitBtn.disabled = false;

    openModal('update-modal');
}

// Live Validation for Update Employee form
function validateUpdateForm() {
    if (!updateName || !updatePhone || !updateEmail) return;

    // Name check
    const nameVal = updateName.value.trim();
    if (nameVal.length < 2) {
        showError(updateName, 'update-name-error', 'Name must be at least 2 characters.');
        updateErrors.name = false;
    } else if (/\d/.test(nameVal)) {
        showError(updateName, 'update-name-error', 'Name cannot contain numbers.');
        updateErrors.name = false;
    } else {
        showSuccess(updateName, 'update-name-error');
        updateErrors.name = true;
    }

    // Phone check
    const phoneVal = updatePhone.value.trim();
    if (!phoneRegex.test(phoneVal)) {
        showError(updatePhone, 'update-phone-error', 'Phone number must be exactly 10 digits.');
        updateErrors.phone = false;
    } else {
        showSuccess(updatePhone, 'update-phone-error');
        updateErrors.phone = true;
    }

    // Email check
    const emailVal = updateEmail.value.trim();
    if (!emailRegex.test(emailVal)) {
        showError(updateEmail, 'update-email-error', 'Please enter a valid email address.');
        updateErrors.email = false;
    } else {
        showSuccess(updateEmail, 'update-email-error');
        updateErrors.email = true;
    }

    const allValid = updateErrors.name && updateErrors.phone && updateErrors.email;
    updateSubmitBtn.disabled = !allValid;
}

if (updateName) updateName.addEventListener('input', validateUpdateForm);
if (updatePhone) updatePhone.addEventListener('input', validateUpdateForm);
if (updateEmail) updateEmail.addEventListener('input', validateUpdateForm);


// View Generated Report Modal Loader
function openReportModal(report) {
    document.getElementById('report-title-label').innerText = report.title;
    
    let reportObj = {};
    try {
        reportObj = JSON.parse(report.content);
    } catch (e) {
        console.error("Failed to parse report details JSON.", e);
        return;
    }

    document.getElementById('report-author').innerText = reportObj.generated_by || "System";
    
    // Set Dynamic Year Columns headings
    const yearPrev = reportObj.year_previous || "Previous Year";
    const yearCurr = reportObj.year_current || "Current Year";
    document.getElementById('report-th-prev').innerText = "Attended in " + yearPrev;
    document.getElementById('report-th-curr').innerText = "Attended in " + yearCurr;

    const tbody = document.getElementById('report-detail-tbody');
    tbody.innerHTML = ''; // Clear old rows

    if (reportObj.employees && reportObj.employees.length > 0) {
        reportObj.employees.forEach(emp => {
            const row = document.createElement('tr');
            
            const cellIc = document.createElement('td');
            cellIc.innerText = emp.ic_no;
            row.appendChild(cellIc);
            
            const cellName = document.createElement('td');
            cellName.innerText = emp.name;
            row.appendChild(cellName);
            
            const cellPrev = document.createElement('td');
            cellPrev.innerText = emp.previous_count;
            row.appendChild(cellPrev);
            
            const cellCurr = document.createElement('td');
            cellCurr.innerText = emp.current_count;
            row.appendChild(cellCurr);
            
            tbody.appendChild(row);
        });
    } else {
        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.setAttribute('colspan', '4');
        cell.innerText = "No employee stats recorded in this report.";
        row.appendChild(cell);
        tbody.appendChild(row);
    }

    openModal('report-modal');
}
