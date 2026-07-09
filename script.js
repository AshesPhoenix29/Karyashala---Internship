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
const signupPassword = document.getElementById('signup_password');
const signupConfirmPassword = document.getElementById('signup_confirm_password');
const signupSubmitBtn = document.getElementById('signup-submit-btn');

const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
const phoneRegex = /^\d{10}$/;

const signupErrors = {
    name: false,
    designation: false,
    phone: false,
    email: false,
    password: false,
    confirmPassword: false
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

    // Password validation
    const passwordVal = signupPassword.value;
    if (passwordVal === '') {
        showError(signupPassword, 'signup-password-error', 'Password is required.');
        signupErrors.password = false;
    } else if (passwordVal.length < 6) {
        showError(signupPassword, 'signup-password-error', 'Password must be at least 6 characters.');
        signupErrors.password = false;
    } else {
        showSuccess(signupPassword, 'signup-password-error');
        signupErrors.password = true;
    }

    // Confirm Password validation
    const confirmVal = signupConfirmPassword.value;
    if (confirmVal === '') {
        showError(signupConfirmPassword, 'signup-confirm-password-error', 'Please confirm your password.');
        signupErrors.confirmPassword = false;
    } else if (confirmVal !== passwordVal) {
        showError(signupConfirmPassword, 'signup-confirm-password-error', 'Passwords do not match.');
        signupErrors.confirmPassword = false;
    } else {
        showSuccess(signupConfirmPassword, 'signup-confirm-password-error');
        signupErrors.confirmPassword = true;
    }

    const allValid = signupErrors.name && signupErrors.designation && signupErrors.phone && signupErrors.email && signupErrors.password && signupErrors.confirmPassword;
    signupSubmitBtn.disabled = !allValid;
}

if (signupName) signupName.addEventListener('input', validateSignupForm);
if (signupDesignation) signupDesignation.addEventListener('change', validateSignupForm);
if (signupPhone) signupPhone.addEventListener('input', validateSignupForm);
if (signupEmail) signupEmail.addEventListener('input', validateSignupForm);
if (signupPassword) signupPassword.addEventListener('input', validateSignupForm);
if (signupConfirmPassword) signupConfirmPassword.addEventListener('input', validateSignupForm);


// Login Form Validation Logic
const loginForm = document.getElementById('login-form');
const loginIc = document.getElementById('login_ic');
const loginPassword = document.getElementById('login_password');
const loginSubmitBtn = document.getElementById('login-submit-btn');

const loginErrors = {
    ic: false,
    password: false
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

    // Password validation
    const passwordVal = loginPassword.value;
    if (passwordVal === '') {
        showError(loginPassword, 'login-password-error', 'Password is required.');
        loginErrors.password = false;
    } else {
        showSuccess(loginPassword, 'login-password-error');
        loginErrors.password = true;
    }

    const allValid = loginErrors.ic && loginErrors.password;
    loginSubmitBtn.disabled = !allValid;
}

if (loginIc) loginIc.addEventListener('input', validateLoginForm);
if (loginPassword) loginPassword.addEventListener('input', validateLoginForm);


// Add Employee Form Validation Logic (Dashboard)
const addForm = document.getElementById('add-employee-form');
const addName = document.getElementById('add_name');
const addDesignation = document.getElementById('add_designation');
const addPhone = document.getElementById('add_phone');
const addEmail = document.getElementById('add_email');
const addWorkshopTitle = document.getElementById('add_workshop_title');
const addWorkshopDate = document.getElementById('add_workshop_date');
const addSubmitBtn = document.getElementById('add-submit-btn');

const addErrors = {
    name: false,
    designation: false,
    phone: false,
    email: false,
    workshopTitle: false,
    workshopDate: false
};

function validateAddForm() {
    if (!addForm) return;

    // Name check
    const nameVal = addName.value.trim();
    if (nameVal.length < 2) {
        showError(addName, 'add-name-error', 'Name must be at least 2 characters.');
        addErrors.name = false;
    } else if (/\d/.test(nameVal)) {
        showError(addName, 'add-name-error', 'Name cannot contain numbers.');
        addErrors.name = false;
    } else {
        showSuccess(addName, 'add-name-error');
        addErrors.name = true;
    }

    // Designation check
    const designationVal = addDesignation ? addDesignation.value.trim() : '';
    if (designationVal === '') {
        showError(addDesignation, 'add-designation-error', 'Designation is required.');
        addErrors.designation = false;
    } else if (designationVal.length > 20) {
        showError(addDesignation, 'add-designation-error', 'Designation must be max 20 characters.');
        addErrors.designation = false;
    } else {
        showSuccess(addDesignation, 'add-designation-error');
        addErrors.designation = true;
    }

    // Phone check
    const phoneVal = addPhone.value.trim();
    if (!phoneRegex.test(phoneVal)) {
        showError(addPhone, 'add-phone-error', 'Phone number must be exactly 10 digits.');
        addErrors.phone = false;
    } else {
        showSuccess(addPhone, 'add-phone-error');
        addErrors.phone = true;
    }

    // Email check
    const emailVal = addEmail.value.trim();
    if (!emailRegex.test(emailVal)) {
        showError(addEmail, 'add-email-error', 'Please enter a valid email address.');
        addErrors.email = false;
    } else {
        showSuccess(addEmail, 'add-email-error');
        addErrors.email = true;
    }

    // Workshop Title check
    const wsTitleVal = addWorkshopTitle ? addWorkshopTitle.value.trim() : '';
    if (wsTitleVal === '') {
        showError(addWorkshopTitle, 'add-workshop-title-error', 'Workshop Title is required.');
        addErrors.workshopTitle = false;
    } else {
        showSuccess(addWorkshopTitle, 'add-workshop-title-error');
        addErrors.workshopTitle = true;
    }

    // Workshop Date check
    const wsDateVal = addWorkshopDate ? addWorkshopDate.value.trim() : '';
    if (wsDateVal === '') {
        showError(addWorkshopDate, 'add-workshop-date-error', 'Workshop Date is required.');
        addErrors.workshopDate = false;
    } else {
        showSuccess(addWorkshopDate, 'add-workshop-date-error');
        addErrors.workshopDate = true;
    }

    const allValid = addErrors.name && addErrors.designation && addErrors.phone && addErrors.email && addErrors.workshopTitle && addErrors.workshopDate;
    addSubmitBtn.disabled = !allValid;
}

if (addName) addName.addEventListener('input', validateAddForm);
if (addDesignation) addDesignation.addEventListener('input', validateAddForm);
if (addPhone) addPhone.addEventListener('input', validateAddForm);
if (addEmail) addEmail.addEventListener('input', validateAddForm);
if (addWorkshopTitle) addWorkshopTitle.addEventListener('input', validateAddForm);
if (addWorkshopDate) addWorkshopDate.addEventListener('input', validateAddForm);


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

// View Karyashala Admin Details Modal Loader (Admin Eye Icon)
function openViewModal(karyashala_admin, workshops) {
    document.getElementById('view-ic').value = karyashala_admin.ic_no;
    document.getElementById('view-name').value = karyashala_admin.name;
    document.getElementById('view-designation').value = karyashala_admin.designation;
    document.getElementById('view-phone').value = karyashala_admin.phone;
    document.getElementById('view-email').value = karyashala_admin.email;
    
    // Formatting created date
    const date = new Date(karyashala_admin.created_at);
    document.getElementById('view-date').value = date.toLocaleString();
    
    document.getElementById('view-remark').value = karyashala_admin.remark || '';
    
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

// Edit Karyashala Admin details Modal Loader
const updateName = document.getElementById('update-name');
const updateDesignation = document.getElementById('update-designation');
const updatePhone = document.getElementById('update-phone');
const updateEmail = document.getElementById('update-email');
const updateSubmitBtn = document.getElementById('update-submit-btn');

const updateErrors = {
    name: true,
    designation: true,
    phone: true,
    email: true
};

function openUpdateModal(karyashala_admin, workshops) {
    switchUpdateTab('personal');
    newWorkshopCounter = 0;
    
    document.getElementById('update-ic').value = karyashala_admin.ic_no;
    updateName.value = karyashala_admin.name;
    if (updateDesignation) {
        updateDesignation.value = karyashala_admin.designation || '';
        showSuccess(updateDesignation, 'update-designation-error');
    }
    updatePhone.value = karyashala_admin.phone;
    updateEmail.value = karyashala_admin.email;
    const remarkField = document.getElementById('update-remark');
    if (remarkField) {
        remarkField.value = karyashala_admin.remark || '';
    }

    // Reset error styling
    showSuccess(updateName, 'update-name-error');
    showSuccess(updatePhone, 'update-phone-error');
    showSuccess(updateEmail, 'update-email-error');

    updateErrors.name = true;
    updateErrors.designation = true;
    updateErrors.phone = true;
    updateErrors.email = true;
    updateSubmitBtn.disabled = false;

    // Populate workshops container
    const container = document.getElementById('update-workshops-container');
    if (container) {
        container.innerHTML = '';
        if (workshops && workshops.length > 0) {
            workshops.forEach((ws, idx) => {
                const item = document.createElement('div');
                item.className = 'update-workshop-item';
                item.style.borderTop = '1px dashed #ccc';
                item.style.paddingTop = '10px';
                item.style.marginTop = '10px';
                
                item.innerHTML = `
                    <h4 style="margin: 0 0 10px 0; color: #2c3e50;">Workshop #${idx + 1}</h4>
                    <input type="hidden" name="workshops[${ws.id}][id]" value="${ws.id}">
                    <div class="form-group">
                        <label for="update_ws_title_${ws.id}">Workshop Title:</label>
                        <input type="text" name="workshops[${ws.id}][title]" id="update_ws_title_${ws.id}" value="${escapeHtml(ws.title)}" required class="update-ws-title">
                        <span class="error-text" id="update-ws-title-error-${ws.id}"></span>
                    </div>
                    <div class="form-group">
                        <label for="update_ws_date_${ws.id}">Attended Date:</label>
                        <input type="date" name="workshops[${ws.id}][attended_date]" id="update_ws_date_${ws.id}" value="${ws.attended_date}" required max="${new Date().toISOString().split('T')[0]}" class="update-ws-date">
                        <span class="error-text" id="update-ws-date-error-${ws.id}"></span>
                    </div>
                `;
                container.appendChild(item);

                // Add input listeners for live validation on dynamically created inputs
                const titleInput = item.querySelector(`#update_ws_title_${ws.id}`);
                const dateInput = item.querySelector(`#update_ws_date_${ws.id}`);
                
                titleInput.addEventListener('input', () => {
                    if (titleInput.value.trim() === '') {
                        showError(titleInput, `update-ws-title-error-${ws.id}`, 'Workshop Title is required.');
                    } else {
                        showSuccess(titleInput, `update-ws-title-error-${ws.id}`);
                    }
                    validateUpdateForm();
                });

                dateInput.addEventListener('input', () => {
                    if (dateInput.value.trim() === '') {
                        showError(dateInput, `update-ws-date-error-${ws.id}`, 'Workshop Date is required.');
                    } else {
                        showSuccess(dateInput, `update-ws-date-error-${ws.id}`);
                    }
                    validateUpdateForm();
                });
            });
        } else {
            container.innerHTML = '<p style="color: #7f8c8d; font-style: italic; font-size: 13px; text-align: center; margin: 15px 0;">No workshops registered for this employee.</p>';
        }
    }

    openModal('update-modal');
}

// Live Validation for Update Karyashala Admin form
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

    // Designation check
    if (updateDesignation) {
        const designationVal = updateDesignation.value.trim();
        if (designationVal === '') {
            showError(updateDesignation, 'update-designation-error', 'Designation is required.');
            updateErrors.designation = false;
        } else if (designationVal.length > 20) {
            showError(updateDesignation, 'update-designation-error', 'Designation must be max 20 characters.');
            updateErrors.designation = false;
        } else {
            showSuccess(updateDesignation, 'update-designation-error');
            updateErrors.designation = true;
        }
    } else {
        updateErrors.designation = true;
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

    // Check dynamic workshop fields
    let workshopsValid = true;
    const updateWsTitles = document.querySelectorAll('.update-ws-title');
    const updateWsDates = document.querySelectorAll('.update-ws-date');
    
    updateWsTitles.forEach(input => {
        if (input.value.trim() === '') {
            workshopsValid = false;
        }
    });

    updateWsDates.forEach(input => {
        if (input.value.trim() === '') {
            workshopsValid = false;
        }
    });

    const allValid = updateErrors.name && updateErrors.designation && updateErrors.phone && updateErrors.email && workshopsValid;
    updateSubmitBtn.disabled = !allValid;
}

// Escape HTML helper
function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

if (updateName) updateName.addEventListener('input', validateUpdateForm);
if (updateDesignation) updateDesignation.addEventListener('input', validateUpdateForm);
if (updatePhone) updatePhone.addEventListener('input', validateUpdateForm);
if (updateEmail) updateEmail.addEventListener('input', validateUpdateForm);


// Verification Details Modal Loader (Admin Only)
function openVerificationModal(emp, year, isAlreadyVerified) {
    document.getElementById('ver-year-label').innerText = year;
    document.getElementById('ver-year-subtitle').innerText = year;
    document.getElementById('ver-ic').value = emp.ic_no;
    document.getElementById('ver-name').value = emp.name;
    document.getElementById('ver-designation').value = emp.designation;
    document.getElementById('ver-phone').value = emp.phone;
    document.getElementById('ver-email').value = emp.email;
    document.getElementById('ver-remark').value = emp.remark || '';
    
    // Set hidden verify inputs
    const verifyIcInput = document.getElementById('verify-ic-input');
    const verifyYearInput = document.getElementById('verify-year-input');
    if (verifyIcInput) verifyIcInput.value = emp.ic_no;
    if (verifyYearInput) verifyYearInput.value = year;

    // Toggle verify button form visibility depending on verification status
    const verifyForm = document.getElementById('verify-action-form');
    if (verifyForm) {
        if (isAlreadyVerified) {
            verifyForm.style.display = 'none';
        } else {
            verifyForm.style.display = 'flex';
        }
    }

    const listContainer = document.getElementById('ver-workshops-list');
    if (listContainer) {
        listContainer.innerHTML = '';
        if (emp.workshops && emp.workshops.length > 0) {
            emp.workshops.forEach((ws, idx) => {
                const item = document.createElement('div');
                item.style.padding = '10px';
                item.style.borderBottom = idx < emp.workshops.length - 1 ? '1px dashed #eee' : 'none';
                item.innerHTML = `
                    <div style="font-weight: bold; color: #2c3e50;">${escapeHtml(ws.title)}</div>
                    <div style="font-size: 12px; color: #7f8c8d; margin-top: 4px;">Attended: ${ws.attended_date}</div>
                `;
                listContainer.appendChild(item);
            });
        } else {
            listContainer.innerHTML = '<p style="color: #888; font-style: italic; font-size: 13px; text-align: center; margin: 15px 0;">No workshops recorded for this year.</p>';
        }
    }
    
    openModal('verification-modal');
}

// Switch between Personal Details and Workshops tab in Update Modal
function switchUpdateTab(tabName) {
    const personalTab = document.getElementById('update-tab-personal');
    const workshopsTab = document.getElementById('update-tab-workshops');
    const personalPane = document.getElementById('update-pane-personal');
    const workshopsPane = document.getElementById('update-pane-workshops');
    
    if (tabName === 'personal') {
        if (personalTab) personalTab.classList.add('active');
        if (workshopsTab) workshopsTab.classList.remove('active');
        if (personalPane) personalPane.style.display = 'block';
        if (workshopsPane) workshopsPane.style.display = 'none';
    } else {
        if (personalTab) personalTab.classList.remove('active');
        if (workshopsTab) workshopsTab.classList.add('active');
        if (personalPane) personalPane.style.display = 'none';
        if (workshopsPane) workshopsPane.style.display = 'block';
    }
}

// Add dynamic new workshop inputs inside Update Modal
let newWorkshopCounter = 0;
function addNewWorkshopInput() {
    const container = document.getElementById('update-workshops-container');
    if (!container) return;
    
    // Remove placeholder empty paragraph if exists
    const placeholder = container.querySelector('p');
    if (placeholder && placeholder.style.fontStyle === 'italic') {
        container.innerHTML = '';
    }
    
    const idx = newWorkshopCounter++;
    const item = document.createElement('div');
    item.className = 'update-workshop-item new-workshop-item';
    item.style.borderTop = '1px dashed #2ecc71';
    item.style.paddingTop = '10px';
    item.style.marginTop = '10px';
    
    item.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h4 style="margin: 0; color: #2ecc71;">New Workshop</h4>
            <button type="button" class="icon-btn" onclick="removeNewWorkshopInput(this)" title="Remove" style="background:none; border:none; padding:2px; cursor:pointer;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#e74c3c" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="form-group">
            <label for="new_ws_title_${idx}">Workshop Title:</label>
            <input type="text" name="new_workshops[${idx}][title]" id="new_ws_title_${idx}" required class="update-ws-title new-ws-title" placeholder="e.g. Machine Learning Seminar">
            <span class="error-text" id="new-ws-title-error-${idx}"></span>
        </div>
        <div class="form-group">
            <label for="new_ws_date_${idx}">Attended Date:</label>
            <input type="date" name="new_workshops[${idx}][attended_date]" id="new_ws_date_${idx}" required max="${new Date().toISOString().split('T')[0]}" class="update-ws-date new-ws-date">
            <span class="error-text" id="new-ws-date-error-${idx}"></span>
        </div>
    `;
    container.appendChild(item);
    
    const titleInput = item.querySelector(`#new_ws_title_${idx}`);
    const dateInput = item.querySelector(`#new_ws_date_${idx}`);
    titleInput.focus();
    
    titleInput.addEventListener('input', () => {
        if (titleInput.value.trim() === '') {
            showError(titleInput, `new-ws-title-error-${idx}`, 'Workshop Title is required.');
        } else {
            showSuccess(titleInput, `new-ws-title-error-${idx}`);
        }
        validateUpdateForm();
    });

    dateInput.addEventListener('input', () => {
        if (dateInput.value.trim() === '') {
            showError(dateInput, `new-ws-date-error-${idx}`, 'Workshop Date is required.');
        } else {
            showSuccess(dateInput, `new-ws-date-error-${idx}`);
        }
        validateUpdateForm();
    });
    
    validateUpdateForm();
}

function removeNewWorkshopInput(btn) {
    const item = btn.closest('.update-workshop-item');
    if (item) {
        item.remove();
    }
    const container = document.getElementById('update-workshops-container');
    if (container && container.querySelectorAll('.update-workshop-item').length === 0) {
        container.innerHTML = '<p style="color: #7f8c8d; font-style: italic; font-size: 13px; text-align: center; margin: 15px 0;">No workshops registered for this employee.</p>';
    }
    validateUpdateForm();
}
