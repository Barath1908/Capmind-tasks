// ============================================================
//  app.js — AJAX Appointment Manager
//  Uses fetch() + async/await, proper HTTP methods
// ============================================================

'use strict';

const API = 'api.php';
let editingId = null;

// ─── Read CSRF token from the server-rendered hidden field ───
function getCsrfToken() {
    return document.getElementById('csrf_token').value;
}

// ════════════════════════════════════════════════════════════
//  READ — GET all appointments
// ════════════════════════════════════════════════════════════
async function loadAppointments() {
    showTableLoading(true);
    try {
        const res  = await fetch(API);
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        renderTable(json.data);
    } catch (e) {
        showToast(e.message || 'Failed to load appointments.', 'error');
        renderTable([]);
    } finally {
        showTableLoading(false);
    }
}

// ─── Render table ────────────────────────────────────────────
function renderTable(appointments) {
    const tbody = document.getElementById('appointmentBody');
    const empty = document.getElementById('emptyState');
    const table = document.getElementById('appointmentTable');

    tbody.innerHTML = '';

    if (!appointments.length) {
        table.style.display = 'none';
        empty.style.display = 'flex';
        updateStats([]);
        return;
    }

    table.style.display = 'table';
    empty.style.display = 'none';

    appointments.forEach((a, idx) => {
        const tr = document.createElement('tr');
        tr.dataset.id = a.id;
        tr.style.animationDelay = `${idx * 40}ms`;
        tr.classList.add('row-enter');

        const statusColors = {
            'Pending'   : 'badge-pending',
            'Confirmed' : 'badge-confirmed',
            'Cancelled' : 'badge-cancelled'
        };

        tr.innerHTML = `
            <td><span class="id-badge">#${String(a.id).padStart(3,'0')}</span></td>
            <td>
                <div class="patient-cell">
                    <div class="avatar">${getInitials(a.patient_name)}</div>
                    <div>
                        <div class="patient-name">${esc(a.patient_name)}</div>
                        <div class="patient-email">${esc(a.email)}</div>
                    </div>
                </div>
            </td>
            <td><span class="mobile-num">📞 ${esc(a.mobile)}</span></td>
            <td>
                <div class="doctor-cell">
                    <div class="doctor-name">${esc(a.doctor_name || '—')}</div>
                    <div class="doctor-spec">${esc(a.specialty || '')}</div>
                </div>
            </td>
            <td>
                <div class="datetime-cell">
                    <div>📅 ${formatDate(a.appointment_date)}</div>
                    <div>🕐 ${formatTime(a.appointment_time)}</div>
                </div>
            </td>
            <td>
                <select class="status-select ${statusColors[a.status] || ''}"
                        onchange="updateStatus(${a.id}, this)"
                        data-original="${esc(a.status)}">
                    <option ${a.status==='Pending'   ?'selected':''}>Pending</option>
                    <option ${a.status==='Confirmed' ?'selected':''}>Confirmed</option>
                    <option ${a.status==='Cancelled' ?'selected':''}>Cancelled</option>
                </select>
            </td>
            <td>
                <div class="action-btns">
                    <button class="btn-edit" onclick="editAppointment(${a.id})" title="Edit">
                        ✏️ Edit
                    </button>
                    <button class="btn-delete" onclick="deleteAppointment(${a.id}, '${esc(a.patient_name)}')" title="Delete">
                        🗑️ Delete
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });

    updateStats(appointments);
}

// ─── Stats counter ───────────────────────────────────────────
function updateStats(appointments) {
    document.getElementById('statTotal').textContent     = appointments.length;
    document.getElementById('statPending').textContent   = appointments.filter(a => a.status === 'Pending').length;
    document.getElementById('statConfirmed').textContent = appointments.filter(a => a.status === 'Confirmed').length;
    document.getElementById('statCancelled').textContent = appointments.filter(a => a.status === 'Cancelled').length;
}

// ════════════════════════════════════════════════════════════
//  CREATE / UPDATE — form submit
// ════════════════════════════════════════════════════════════
document.getElementById('appointmentForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validateForm()) return;

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = editingId ? '⏳ Updating…' : '⏳ Booking…';

    const csrfToken = getCsrfToken();

    const payload = {
        patient_name      : document.getElementById('patient_name').value.trim(),
        email             : document.getElementById('email').value.trim(),
        mobile            : document.getElementById('mobile').value.trim(),
        doctor_id         : document.getElementById('doctor_id').value,
        appointment_date  : document.getElementById('appointment_date').value,
        appointment_time  : document.getElementById('appointment_time').value,
        csrf_token        : csrfToken
    };

    if (editingId) payload.id = editingId;

    try {
        const res  = await fetch(API, {
            method  : editingId ? 'PUT' : 'POST',
            headers : { 'Content-Type': 'application/json' },
            body    : JSON.stringify(payload)
        });

        const json = await res.json();

        if (!json.success) throw new Error(json.message);

        showToast(json.message, 'success');
        const savedId = editingId;
        resetForm();
        await loadAppointments();
        if (savedId) highlightRow(savedId);

    } catch (e) {
        showToast(e.message || 'Operation failed.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = editingId ? '💾 Update Appointment' : '📅 Book Appointment';
    }
});

// ════════════════════════════════════════════════════════════
//  EDIT — populate form
// ════════════════════════════════════════════════════════════
async function editAppointment(id) {
    try {
        const res  = await fetch(API);
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        const appt = json.data.find(a => a.id == id);
        if (!appt) throw new Error('Appointment not found.');

        editingId = id;

        document.getElementById('patient_name')      .value = appt.patient_name;
        document.getElementById('email')             .value = appt.email;
        document.getElementById('mobile')            .value = appt.mobile;
        document.getElementById('doctor_id')         .value = appt.doctor_id;
        document.getElementById('appointment_date')  .value = appt.appointment_date;
        document.getElementById('appointment_time')  .value = appt.appointment_time;

        document.getElementById('submitBtn').textContent = '💾 Update Appointment';
        document.getElementById('cancelEditBtn').style.display = 'inline-flex';
        document.getElementById('formTitle').textContent = '✏️ Edit Appointment';

        document.getElementById('appointmentForm').scrollIntoView({ behavior: 'smooth', block: 'start' });

        document.querySelectorAll('tr.editing').forEach(r => r.classList.remove('editing'));
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (row) row.classList.add('editing');

    } catch (e) {
        showToast(e.message || 'Failed to load appointment.', 'error');
    }
}

// ════════════════════════════════════════════════════════════
//  DELETE
// ════════════════════════════════════════════════════════════
async function deleteAppointment(id, name) {
    if (!confirm(`Delete appointment for "${name}"?\nThis action cannot be undone.`)) return;

    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (row) row.classList.add('row-exit');

    const csrfToken = getCsrfToken();

    try {
        const res  = await fetch(API, {
            method  : 'DELETE',
            headers : { 'Content-Type': 'application/json' },
            body    : JSON.stringify({ id, csrf_token: csrfToken })
        });

        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        showToast(json.message, 'success');
        setTimeout(() => loadAppointments(), 350);

    } catch (e) {
        if (row) row.classList.remove('row-exit');
        showToast(e.message || 'Delete failed.', 'error');
    }
}

// ════════════════════════════════════════════════════════════
//  STATUS UPDATE — PATCH
// ════════════════════════════════════════════════════════════
async function updateStatus(id, selectEl) {
    const status   = selectEl.value;
    const original = selectEl.dataset.original;
    const csrfToken = getCsrfToken();

    try {
        const res  = await fetch(API, {
            method  : 'PATCH',
            headers : { 'Content-Type': 'application/json' },
            body    : JSON.stringify({ id, status, csrf_token: csrfToken })
        });

        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        const map = { Pending: 'badge-pending', Confirmed: 'badge-confirmed', Cancelled: 'badge-cancelled' };
        selectEl.className = 'status-select ' + (map[status] || '');
        selectEl.dataset.original = status;

        showToast(json.message, 'success');
        await loadAppointments();

    } catch (e) {
        selectEl.value = original;
        showToast(e.message || 'Status update failed.', 'error');
    }
}

// ─── Form validation ─────────────────────────────────────────
function validateForm() {
    clearErrors();
    let valid = true;

    const name  = document.getElementById('patient_name').value.trim();
    const email = document.getElementById('email').value.trim();
    const mob   = document.getElementById('mobile').value.trim();
    const doc   = document.getElementById('doctor_id').value;
    const date  = document.getElementById('appointment_date').value;
    const time  = document.getElementById('appointment_time').value;

    if (!name)  { setError('patient_name', 'Patient name is required.'); valid = false; }
    if (!email) { setError('email', 'Email is required.'); valid = false; }
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { setError('email', 'Invalid email format.'); valid = false; }
    if (!mob)   { setError('mobile', 'Mobile number is required.'); valid = false; }
    else if (!/^[0-9]{7,15}$/.test(mob)) { setError('mobile', 'Mobile must be 7–15 digits.'); valid = false; }
    if (!doc)   { setError('doctor_id', 'Please select a doctor.'); valid = false; }
    if (!date)  { setError('appointment_date', 'Appointment date is required.'); valid = false; }
    else if (!editingId && date < new Date().toISOString().split('T')[0]) {
        setError('appointment_date', 'Appointment date cannot be in the past.'); valid = false;
    }
    if (!time)  { setError('appointment_time', 'Appointment time is required.'); valid = false; }

    return valid;
}

function setError(fieldId, msg) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    field.classList.add('input-error');
    const errEl = document.getElementById(`err_${fieldId}`);
    if (errEl) errEl.textContent = msg;
}

function clearErrors() {
    document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    document.querySelectorAll('.field-error').forEach(el => el.textContent = '');
}

// ─── Reset form ───────────────────────────────────────────────
function resetForm() {
    document.getElementById('appointmentForm').reset();
    clearErrors();
    editingId = null;
    document.getElementById('submitBtn').textContent = '📅 Book Appointment';
    document.getElementById('cancelEditBtn').style.display = 'none';
    document.getElementById('formTitle').textContent = '📋 New Appointment';
    document.querySelectorAll('tr.editing').forEach(r => r.classList.remove('editing'));
}

document.getElementById('cancelEditBtn').addEventListener('click', resetForm);

// ─── Search / filter ─────────────────────────────────────────
document.getElementById('searchInput').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#appointmentBody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

// ─── Helpers ─────────────────────────────────────────────────
function showToast(msg, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast     = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <span class="toast-icon">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
        <span>${msg}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">✕</button>
    `;
    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('show'));
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 350);
    }, 4000);
}

function highlightRow(id) {
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (row) {
        row.classList.add('highlight-updated');
        setTimeout(() => row.classList.remove('highlight-updated'), 2500);
    }
}

function showTableLoading(show) {
    document.getElementById('tableLoader').style.display = show ? 'flex' : 'none';
}

function getInitials(name) {
    return name.split(' ').slice(0, 2).map(w => w[0]?.toUpperCase() || '').join('');
}

function esc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function formatDate(d) {
    if (!d) return '—';
    const dt = new Date(d + 'T00:00:00');
    return dt.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatTime(t) {
    if (!t) return '—';
    const [h, m] = t.split(':');
    const hr   = parseInt(h);
    const ampm = hr >= 12 ? 'PM' : 'AM';
    const hr12 = hr % 12 || 12;
    return `${hr12}:${m} ${ampm}`;
}

// ─── Set min date = today ────────────────────────────────────
document.getElementById('appointment_date').min = new Date().toISOString().split('T')[0];

// ─── Init ─────────────────────────────────────────────────────
loadAppointments();
