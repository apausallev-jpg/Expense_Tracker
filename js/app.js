// ============================================
//  Expense Tracker — Main JS (app.js)
// ============================================

const API = {
  expenses:   'api/expenses.php',
  categories: 'api/categories.php',
  summary:    'api/summary.php',
};

// ── State ────────────────────────────────────────────────────────────────────
let allExpenses  = [];
let categories   = [];
let editingId    = null;
let filterState  = { search: '', category: '', month: '' };

// ── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
  await loadCategories();
  await Promise.all([loadExpenses(), loadSummary()]);
  bindEvents();
});

// ── API helpers ───────────────────────────────────────────────────────────────
async function apiFetch(url, options = {}) {
  const res  = await fetch(url, {
    headers: { 'Content-Type': 'application/json' },
    ...options,
  });
  const data = await res.json();
  if (!res.ok) throw new Error(data.error || 'Request failed');
  return data;
}

// ── Load data ─────────────────────────────────────────────────────────────────
async function loadCategories() {
  categories = await apiFetch(API.categories);
  populateCategorySelects();
}

async function loadExpenses() {
  const params = new URLSearchParams();
  if (filterState.search)   params.set('search',      filterState.search);
  if (filterState.category) params.set('category_id', filterState.category);
  if (filterState.month)    params.set('month',        filterState.month);

  allExpenses = await apiFetch(`${API.expenses}?${params}`);
  renderTable();
}

async function loadSummary() {
  const data = await apiFetch(API.summary);
  renderStats(data);
  renderCategoryBreakdown(data.by_category);
}

// ── Render: stats ─────────────────────────────────────────────────────────────
function renderStats(data) {
  qs('#stat-total').textContent     = formatCurrency(data.total);
  qs('#stat-month').textContent     = formatCurrency(data.this_month);
  qs('#stat-count').textContent     = data.count;
}

// ── Render: table ─────────────────────────────────────────────────────────────
function renderTable() {
  const tbody = qs('#expense-tbody');
  if (!allExpenses.length) {
    tbody.innerHTML = `<tr><td colspan="6">
      <div class="empty-state">
        <div class="icon">💸</div>
        No expenses found. Add your first one!
      </div></td></tr>`;
    return;
  }

  tbody.innerHTML = allExpenses.map(e => `
    <tr>
      <td>${escHtml(e.title)}</td>
      <td>
        <span class="badge" style="background:${hexToRgba(e.category_color,.12)};color:${e.category_color}">
          <span class="badge-dot" style="background:${e.category_color}"></span>
          ${escHtml(e.category_name)}
        </span>
      </td>
      <td class="amount-cell">${formatCurrency(e.amount)}</td>
      <td class="date-cell">${formatDate(e.expense_date)}</td>
      <td class="desc-cell" title="${escHtml(e.description || '')}">${escHtml(e.description || '—')}</td>
      <td>
        <button class="btn-icon" onclick="openEditModal(${e.id})" title="Edit">✏️</button>
        <button class="btn-icon del" onclick="deleteExpense(${e.id})" title="Delete">🗑️</button>
      </td>
    </tr>`).join('');
}

// ── Render: category breakdown ────────────────────────────────────────────────
function renderCategoryBreakdown(cats) {
  const max = Math.max(...cats.map(c => parseFloat(c.total)), 1);
  qs('#cat-list').innerHTML = cats
    .filter(c => parseFloat(c.total) > 0)
    .map(c => {
      const pct = (parseFloat(c.total) / max * 100).toFixed(1);
      return `
        <li class="cat-item">
          <span class="cat-name">${escHtml(c.name)}</span>
          <div class="cat-bar-wrap">
            <div class="cat-bar" style="width:${pct}%;background:${c.color}"></div>
          </div>
          <span class="cat-amount">${formatCurrency(c.total)}</span>
        </li>`;
    }).join('') || '<li class="cat-item" style="color:var(--muted)">No data yet</li>';
}

// ── Populate category <select> ────────────────────────────────────────────────
function populateCategorySelects() {
  const opts = categories.map(c =>
    `<option value="${c.id}">${escHtml(c.name)}</option>`
  ).join('');

  qs('#form-category').innerHTML     = '<option value="">Select category…</option>' + opts;
  qs('#filter-category').innerHTML   = '<option value="">All categories</option>'   + opts;
}

// ── Modal ─────────────────────────────────────────────────────────────────────
function openAddModal() {
  editingId = null;
  qs('#modal-title').textContent = 'Add Expense';
  qs('#expense-form').reset();
  qs('#form-date').value = todayISO();
  showModal();
}

async function openEditModal(id) {
  editingId = id;
  qs('#modal-title').textContent = 'Edit Expense';
  try {
    const e = await apiFetch(`${API.expenses}?id=${id}`);
    qs('#form-title').value       = e.title;
    qs('#form-category').value    = e.category_id;
    qs('#form-amount').value      = e.amount;
    qs('#form-date').value        = e.expense_date.substring(0, 10);
    qs('#form-description').value = e.description || '';
    showModal();
  } catch (err) { showToast(err.message, 'error'); }
}

function showModal()  { qs('#modal').classList.remove('hidden'); }
function closeModal() { qs('#modal').classList.add('hidden');    }

// ── Save expense ──────────────────────────────────────────────────────────────
async function saveExpense() {
  const body = {
    title:        qs('#form-title').value.trim(),
    category_id:  qs('#form-category').value,
    amount:       qs('#form-amount').value,
    expense_date: qs('#form-date').value,
    description:  qs('#form-description').value.trim(),
  };

  const btn = qs('#save-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span>';

  try {
    if (editingId) {
      await apiFetch(`${API.expenses}?id=${editingId}`, { method: 'PUT', body: JSON.stringify(body) });
      showToast('Expense updated ✓', 'success');
    } else {
      await apiFetch(API.expenses, { method: 'POST', body: JSON.stringify(body) });
      showToast('Expense added ✓', 'success');
    }
    closeModal();
    await Promise.all([loadExpenses(), loadSummary()]);
  } catch (err) {
    showToast(err.message, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = 'Save';
  }
}

// ── Delete expense ────────────────────────────────────────────────────────────
async function deleteExpense(id) {
  if (!confirm('Delete this expense?')) return;
  try {
    await apiFetch(`${API.expenses}?id=${id}`, { method: 'DELETE' });
    showToast('Deleted ✓', 'success');
    await Promise.all([loadExpenses(), loadSummary()]);
  } catch (err) { showToast(err.message, 'error'); }
}

// ── Filters ───────────────────────────────────────────────────────────────────
function applyFilters() {
  filterState.search   = qs('#filter-search').value.trim();
  filterState.category = qs('#filter-category').value;
  filterState.month    = qs('#filter-month').value;
  loadExpenses();
}

function clearFilters() {
  qs('#filter-search').value   = '';
  qs('#filter-category').value = '';
  qs('#filter-month').value    = '';
  filterState = { search: '', category: '', month: '' };
  loadExpenses();
}

// ── Bind events ───────────────────────────────────────────────────────────────
function bindEvents() {
  qs('#add-btn').addEventListener('click', openAddModal);
  qs('#save-btn').addEventListener('click', saveExpense);
  qs('#modal-close').addEventListener('click', closeModal);
  qs('#modal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });

  qs('#filter-search').addEventListener('input',  debounce(applyFilters, 300));
  qs('#filter-category').addEventListener('change', applyFilters);
  qs('#filter-month').addEventListener('change',    applyFilters);
  qs('#clear-filters').addEventListener('click',    clearFilters);
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const qs = sel => document.querySelector(sel);

function formatCurrency(n) {
  return '₱' + parseFloat(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(d) {
  return new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

function todayISO() {
  return new Date().toISOString().substring(0, 10);
}

function escHtml(str) {
  return String(str).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
}

function hexToRgba(hex, alpha) {
  const r = parseInt(hex.slice(1,3),16), g = parseInt(hex.slice(3,5),16), b = parseInt(hex.slice(5,7),16);
  return `rgba(${r},${g},${b},${alpha})`;
}

function debounce(fn, ms) {
  let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
}

let toastTimer;
function showToast(msg, type = '') {
  const el = qs('#toast');
  el.textContent  = msg;
  el.className    = 'show ' + type;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => el.className = '', 3000);
}
