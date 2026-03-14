<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Expense Tracker</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="css/style.css" />
  <style>
    /* ── User menu ── */
    .user-menu { position: relative; }
    .user-trigger {
      display: flex; align-items: center; gap: .5rem;
      background: rgba(255,255,255,.15); border: 1.5px solid rgba(255,255,255,.3);
      border-radius: 30px; padding: .3rem .8rem .3rem .3rem;
      cursor: pointer; color: #fff; font-size: .82rem; font-weight: 600;
      transition: background .2s;
    }
    .user-trigger:hover { background: rgba(255,255,255,.25); }
    .user-avatar {
      width: 32px; height: 32px; border-radius: 50%;
      object-fit: cover; border: 2px solid rgba(255,255,255,.5);
      background: #7c3aed; display: flex; align-items: center; justify-content: center;
      font-size: .8rem; font-weight: 700; color: #fff; flex-shrink: 0;
      overflow: hidden;
    }
    .user-avatar img { width: 100%; height: 100%; object-fit: cover; }

    .dropdown {
      position: absolute; top: calc(100% + 8px); right: 0;
      background: #fff; border-radius: 12px;
      box-shadow: 0 8px 30px rgba(0,0,0,.15);
      min-width: 200px; overflow: hidden;
      opacity: 0; pointer-events: none; transform: translateY(-8px);
      transition: all .2s;
    }
    .dropdown.open { opacity: 1; pointer-events: all; transform: translateY(0); }
    .dropdown-header { padding: .9rem 1rem; border-bottom: 1px solid #f1f5f9; }
    .dropdown-header .name  { font-weight: 700; font-size: .875rem; color: #1e293b; }
    .dropdown-header .email { font-size: .75rem; color: #64748b; margin-top: 2px; }
    .dropdown-item {
      display: flex; align-items: center; gap: .6rem;
      padding: .65rem 1rem; font-size: .85rem; color: #1e293b;
      cursor: pointer; transition: background .15s; border: none; background: none; width: 100%; text-align: left;
    }
    .dropdown-item:hover { background: #f8fafc; }
    .dropdown-item.danger { color: #ef4444; }
    .dropdown-item.danger:hover { background: #fee2e2; }

    /* ── Profile Modal ── */
    .avatar-upload-wrap { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.25rem; }
    .avatar-preview {
      width: 72px; height: 72px; border-radius: 50%;
      object-fit: cover; border: 3px solid #e2e8f0;
      background: #7c3aed; display: flex; align-items: center; justify-content: center;
      font-size: 1.4rem; color: #fff; font-weight: 700; overflow: hidden; flex-shrink: 0;
    }
    .avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
    .avatar-upload-btn {
      display: inline-flex; align-items: center; gap: .4rem;
      padding: .45rem .9rem; border-radius: 8px;
      border: 1.5px solid #e2e8f0; background: #fff;
      font-size: .82rem; font-weight: 600; cursor: pointer; color: #1e293b;
    }
    .avatar-upload-btn:hover { background: #f8fafc; }
    #avatar-input { display: none; }
  </style>
</head>
<body>

<!-- ── Navigation ── -->
<nav>
  <span class="brand">💰 Expense <span>Tracker</span></span>

  <div class="user-menu">
    <div class="user-trigger" onclick="toggleDropdown()">
      <div class="user-avatar" id="nav-avatar">
        <span id="nav-initials">?</span>
      </div>
      <span id="nav-name">Loading…</span>
      ▾
    </div>
    <div class="dropdown" id="dropdown">
      <div class="dropdown-header">
        <div class="name"  id="dd-name">—</div>
        <div class="email" id="dd-email">—</div>
      </div>
      <button class="dropdown-item" onclick="openProfileModal()">✏️ Edit Profile</button>
      <button class="dropdown-item danger" onclick="doLogout()">🚪 Logout</button>
    </div>
  </div>
</nav>

<!-- ── Main content ── -->
<div class="container">
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon" style="background:#eef2ff">💳</div>
      <div><div class="value" id="stat-total">—</div><div class="label">Total Expenses</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#f0fdf4">📅</div>
      <div><div class="value" id="stat-month">—</div><div class="label">This Month</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#fff7ed">📊</div>
      <div><div class="value" id="stat-count">—</div><div class="label">Total Records</div></div>
    </div>
  </div>

  <div class="main-grid">
    <div class="card">
      <div class="card-header">
        <h2>All Expenses</h2>
        <button class="btn btn-primary btn-sm" id="add-btn">+ Add Expense</button>
      </div>
      <div class="filter-bar">
        <input type="search" id="filter-search"   placeholder="🔍 Search…" />
        <select             id="filter-category"><option value="">All categories</option></select>
        <input type="month" id="filter-month" />
        <button class="btn btn-outline btn-sm" id="clear-filters">Clear</button>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Title</th><th>Category</th><th>Amount</th><th>Date</th><th>Notes</th><th></th>
            </tr>
          </thead>
          <tbody id="expense-tbody">
            <tr><td colspan="6"><div class="empty-state"><div class="icon">⏳</div>Loading…</div></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h2>By Category</h2></div>
      <div class="card-body">
        <ul class="cat-list" id="cat-list"><li class="cat-item" style="color:var(--muted)">Loading…</li></ul>
      </div>
    </div>
  </div>
</div>

<!-- ── Add/Edit Expense Modal ── -->
<div class="modal-backdrop hidden" id="modal">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modal-title">Add Expense</h3>
      <button class="modal-close" id="modal-close">✕</button>
    </div>
    <div class="modal-body">
      <form id="expense-form" onsubmit="return false">
        <div class="form-group">
          <label>Title *</label>
          <input type="text" id="form-title" placeholder="e.g. Lunch at Jollibee" required maxlength="255" />
        </div>
        <div class="form-group">
          <label>Category *</label>
          <select id="form-category" required><option value="">Select category…</option></select>
        </div>
        <div class="form-group">
          <label>Amount (₱) *</label>
          <input type="number" id="form-amount" placeholder="0.00" min="0.01" step="0.01" required />
        </div>
        <div class="form-group">
          <label>Date *</label>
          <input type="date" id="form-date" required />
        </div>
        <div class="form-group">
          <label>Notes (optional)</label>
          <textarea id="form-description" placeholder="Add any extra details…"></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
      <button class="btn btn-primary" id="save-btn">Save</button>
    </div>
  </div>
</div>

<!-- ── Profile Modal ── -->
<div class="modal-backdrop hidden" id="profile-modal">
  <div class="modal">
    <div class="modal-header">
      <h3>Edit Profile</h3>
      <button class="modal-close" onclick="closeProfileModal()">✕</button>
    </div>
    <div class="modal-body">
      <div class="alert" id="profile-alert" style="padding:.65rem .9rem;border-radius:8px;font-size:.82rem;margin-bottom:1rem;display:none"></div>

      <div class="avatar-upload-wrap">
        <div class="avatar-preview" id="profile-avatar-preview">
          <span id="profile-initials-preview">?</span>
        </div>
        <div>
          <label class="avatar-upload-btn" for="avatar-input">📷 Choose Photo</label>
          <input type="file" id="avatar-input" accept="image/*" onchange="previewAvatar(this)" />
          <div style="font-size:.75rem;color:#94a3b8;margin-top:.3rem">JPG, PNG, GIF · Max 2MB</div>
        </div>
      </div>

      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" id="profile-name" placeholder="Your full name" />
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" id="profile-email" disabled style="background:#f8fafc;color:#94a3b8" />
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeProfileModal()">Cancel</button>
      <button class="btn btn-primary" id="profile-save-btn" onclick="saveProfile()">Save Changes</button>
    </div>
  </div>
</div>

<!-- ── Toast ── -->
<div id="toast"></div>

<script src="js/app.js"></script>
<script>
// ── Auth / user display ──────────────────────────────────────────────────────
let currentUser = null;

async function loadUser() {
  try {
    const res = await fetch('api/auth.php?action=me');
    if (res.status === 401) { window.location.href = 'login.html'; return; }
    currentUser = await res.json();
    renderUserUI(currentUser);
  } catch(e) {}
}

function renderUserUI(user) {
  const initials = user.full_name.split(' ').map(w=>w[0]).join('').substring(0,2).toUpperCase();
  document.getElementById('nav-name').textContent    = user.full_name.split(' ')[0];
  document.getElementById('dd-name').textContent     = user.full_name;
  document.getElementById('dd-email').textContent    = user.email;
  document.getElementById('nav-initials').textContent = initials;

  const navAvatar = document.getElementById('nav-avatar');
  if (user.avatar) {
    navAvatar.innerHTML = `<img src="${user.avatar}?t=${Date.now()}" alt="avatar" />`;
  } else {
    navAvatar.innerHTML = `<span>${initials}</span>`;
  }
}

function toggleDropdown() {
  document.getElementById('dropdown').classList.toggle('open');
}
document.addEventListener('click', e => {
  if (!e.target.closest('.user-menu')) document.getElementById('dropdown').classList.remove('open');
});

async function doLogout() {
  await fetch('api/auth.php?action=logout', { method: 'POST' });
  window.location.href = 'login.html';
}

// ── Profile modal ────────────────────────────────────────────────────────────
function openProfileModal() {
  document.getElementById('dropdown').classList.remove('open');
  if (!currentUser) return;

  document.getElementById('profile-name').value  = currentUser.full_name;
  document.getElementById('profile-email').value = currentUser.email;
  document.getElementById('avatar-input').value  = '';

  const initials = currentUser.full_name.split(' ').map(w=>w[0]).join('').substring(0,2).toUpperCase();
  const preview  = document.getElementById('profile-avatar-preview');
  if (currentUser.avatar) {
    preview.innerHTML = `<img src="${currentUser.avatar}?t=${Date.now()}" style="width:100%;height:100%;object-fit:cover;border-radius:50%" />`;
  } else {
    document.getElementById('profile-initials-preview').textContent = initials;
    preview.innerHTML = `<span id="profile-initials-preview">${initials}</span>`;
  }

  document.getElementById('profile-alert').style.display = 'none';
  document.getElementById('profile-modal').classList.remove('hidden');
}
function closeProfileModal() {
  document.getElementById('profile-modal').classList.add('hidden');
}

function previewAvatar(input) {
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('profile-avatar-preview').innerHTML =
      `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:50%" />`;
  };
  reader.readAsDataURL(input.files[0]);
}

async function saveProfile() {
  const name  = document.getElementById('profile-name').value.trim();
  const file  = document.getElementById('avatar-input').files[0];
  const alert = document.getElementById('profile-alert');
  const btn   = document.getElementById('profile-save-btn');

  alert.style.display = 'none';
  if (!name) { showProfileAlert('Full name is required.', 'error'); return; }

  const form = new FormData();
  form.append('full_name', name);
  if (file) form.append('avatar', file);

  btn.disabled = true; btn.textContent = 'Saving…';

  try {
    const res  = await fetch('api/auth.php?action=update_profile', { method: 'POST', body: form });
    const data = await res.json();
    if (!res.ok) { showProfileAlert(data.error, 'error'); return; }
    currentUser = data;
    renderUserUI(data);
    closeProfileModal();
    showToast('Profile updated ✓', 'success');
  } catch(e) {
    showProfileAlert('Error saving profile.', 'error');
  } finally {
    btn.disabled = false; btn.textContent = 'Save Changes';
  }
}

function showProfileAlert(msg, type) {
  const el = document.getElementById('profile-alert');
  el.textContent = msg;
  el.style.display = 'block';
  el.style.background = type === 'error' ? '#fee2e2' : '#d1fae5';
  el.style.color      = type === 'error' ? '#b91c1c' : '#065f46';
}

// Init
loadUser();
</script>
</body>
</html>
