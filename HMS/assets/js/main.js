/**
 * Global Javascript Helpers
 * Hospital Management System
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Sidebar Toggle Trigger
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarCollapse && sidebar) {
        sidebarCollapse.addEventListener('click', function () {
            sidebar.classList.toggle('active');
        });
    }

    // 2. Initialize Bootstrap Toasts
    const toastElList = [].slice.call(document.querySelectorAll('.toast'));
    const toastList = toastElList.map(function (toastEl) {
        return new bootstrap.Toast(toastEl, { autohide: true, delay: 5000 });
    });
    
    // Show them
    toastList.forEach(toast => toast.show());

    // 3. Confirm Deactivations / Deletions dialogs
    const confirmActions = document.querySelectorAll('.confirm-action');
    confirmActions.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm-message') || "Are you sure you want to perform this action?";
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
});

/**
 * AJAX helper to load doctors by department
 * @param {number} departmentId
 * @param {string} targetSelectId
 * @param {string} basePath Relative path prefix (e.g. "../")
 */
function loadDoctorsByDepartment(departmentId, targetSelectId, basePath = '') {
    const doctorSelect = document.getElementById(targetSelectId);
    if (!doctorSelect) return;

    // Reset doctor selection
    doctorSelect.innerHTML = '<option value="">Loading doctors...</option>';
    doctorSelect.disabled = true;

    if (!departmentId) {
        doctorSelect.innerHTML = '<option value="">Select Department First</option>';
        doctorSelect.disabled = true;
        return;
    }

    // Fetch doctors via AJAX
    fetch(`${basePath}includes/ajax-get-doctors.php?department_id=${departmentId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            doctorSelect.innerHTML = '<option value="">-- Select Doctor --</option>';
            if (data.length === 0) {
                doctorSelect.innerHTML = '<option value="">No doctors available in this department</option>';
            } else {
                data.forEach(doctor => {
                    doctorSelect.innerHTML += `<option value="${doctor.id}">${doctor.name} (${doctor.specialization}) - Fee: Rs. ${doctor.consultation_fee.toLocaleString()}</option>`;
                });
                doctorSelect.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error fetching doctors:', error);
            doctorSelect.innerHTML = '<option value="">Error loading doctors</option>';
        });
}

/**
 * Print Element utility
 */
function printReport() {
    window.print();
}
