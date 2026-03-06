/* ============================================================
   GameVault – Main JavaScript
   Praktikum 4: fetch ke api/ endpoints (OOP + REST)
   ============================================================ */

// ── SESSION STATE ─────────────────────────────────────────────
let isLoggedIn = false;
let currentUser = null;

// ── PLATFORM OPTIONS ──────────────────────────────────────────
const PLATFORMS = [
    { group: 'PC', options: ['PC'] },
    { group: 'PlayStation', options: ['PS1', 'PS2', 'PS3', 'PS4', 'PS5'] },
    { group: 'Xbox', options: ['Original Xbox', 'Xbox 360', 'Xbox One', 'Xbox Series S', 'Xbox Series X'] },
    { group: 'Nintendo', options: ['NES', 'SNES', 'N64', 'GameCube', 'Wii', 'Wii U', 'Nintendo DS', 'Nintendo 3DS', 'Nintendo Switch', 'Nintendo Switch 2'] },
    { group: 'Mobile', options: ['iOS', 'Android'] },
    { group: 'Lainnya', options: ['Lainnya (Ketik Manual)'] },
];

// ── INIT ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    checkSession();
    buildPlatformDropdowns();
});

// ── SESSION CHECK ─────────────────────────────────────────────
function checkSession() {
    fetch('includes/session_check.php')
        .then(r => r.json())
        .then(d => {
            if (d.loggedIn) {
                isLoggedIn = true;
                currentUser = d.user;
                showLibrary();
            } else {
                showLanding();
            }
        });
}

// ── PAGE VIEWS ────────────────────────────────────────────────
function showLanding() {
    document.getElementById('landing-page').style.display = '';
    document.getElementById('library-page').style.display = 'none';
    document.getElementById('account-page').style.display = 'none';
    document.getElementById('nav-logged-in').style.display = 'none';
    document.getElementById('nav-logged-out').style.display = '';
    updateHeroStats();
}

function showLibrary() {
    document.getElementById('landing-page').style.display = 'none';
    document.getElementById('library-page').style.display = '';
    document.getElementById('account-page').style.display = 'none';
    document.getElementById('nav-logged-in').style.display = '';
    document.getElementById('nav-logged-out').style.display = 'none';
    document.getElementById('nav-nickname').textContent = currentUser?.nickname || currentUser?.username || 'User';
    loadGames();
    loadCategories();
}

function showAccount() {
    document.getElementById('landing-page').style.display = 'none';
    document.getElementById('library-page').style.display = 'none';
    document.getElementById('account-page').style.display = '';
    loadProfile();
    switchAccountPanel('profile');
}

// ── HERO STATS ────────────────────────────────────────────────
function updateHeroStats() {
    // Static display on landing; real stats shown after login
}

// ── AUTH OVERLAY ──────────────────────────────────────────────
function openAuthModal(tab = 'login') {
    document.getElementById('auth-overlay').classList.add('active');
    switchAuthTab(tab);
}
function closeAuthModal() {
    document.getElementById('auth-overlay').classList.remove('active');
    clearAuthForms();
}

function switchAuthTab(tab) {
    document.querySelectorAll('.modal-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
    document.getElementById('login-form-wrap').style.display    = tab === 'login'    ? '' : 'none';
    document.getElementById('register-form-wrap').style.display = tab === 'register' ? '' : 'none';
}

function clearAuthForms() {
    document.querySelectorAll('#auth-overlay .form-control').forEach(el => el.value = '');
    document.querySelectorAll('#auth-overlay .alert').forEach(el => el.classList.remove('show'));
}

// ── LOGIN ─────────────────────────────────────────────────────
document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('action', 'login');
    const res = await postData('includes/auth.php', fd);
    if (res.success) {
        currentUser = { nickname: res.nickname };
        isLoggedIn  = true;
        closeAuthModal();
        toast(res.message, 'success');
        showLibrary();
    } else {
        showAlert('login-alert', res.message, 'error');
    }
});

// ── REGISTER ──────────────────────────────────────────────────
document.getElementById('register-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd    = new FormData(e.target);
    fd.append('action', 'register');
    const pass  = fd.get('password');
    const pass2 = fd.get('password2');
    if (pass !== pass2) { showAlert('register-alert', 'Konfirmasi password tidak cocok.', 'error'); return; }
    const res = await postData('includes/auth.php', fd);
    showAlert('register-alert', res.message, res.success ? 'success' : 'error');
    if (res.success) setTimeout(() => switchAuthTab('login'), 1500);
});

// ── LOGOUT ────────────────────────────────────────────────────
async function logout() {
    const fd = new FormData();
    fd.append('action', 'logout');
    await postData('includes/auth.php', fd);
    isLoggedIn = false; currentUser = null;
    toast('Berhasil logout.', 'success');
    showLanding();
}

// ── LOAD GAMES ────────────────────────────────────────────────
// Prak 4: fetch ke api/read.php (GET)
let activeGenre = '', activePlatform = '', searchQuery = '';

async function loadGames() {
    const params = new URLSearchParams({ action: 'get_games' });
    if (activeGenre)    params.append('genre',    activeGenre);
    if (activePlatform) params.append('platform', activePlatform);
    if (searchQuery)    params.append('search',   searchQuery);

    const res  = await fetch('api/read.php?' + params);
    const data = await res.json();
    renderGames(data.games || []);

    const el = document.getElementById('game-count');
    if (el) el.textContent = (data.games || []).length + ' game';
}

function renderGames(games) {
    const grid = document.getElementById('game-grid');
    if (!games.length) {
        grid.innerHTML = `
          <div class="empty-state">
            <div class="empty-icon">🎮</div>
            <div class="empty-title">Library kosong</div>
            <p class="empty-desc">Belum ada game yang ditambahkan. Mulai tambahkan koleksimu!</p>
            <button class="btn btn-primary" onclick="openAddGameModal()">+ Tambah Game</button>
          </div>`;
        return;
    }

    grid.innerHTML = games.map(g => {
        const cover = g.cover_image
            ? `<img class="game-cover" src="${g.cover_image}" alt="${esc(g.title)}" loading="lazy">`
            : `<div class="game-cover-placeholder">🎮<small>${esc(g.title).substring(0,10)}</small></div>`;
        return `
          <div class="game-card" data-id="${g.id}">
            ${cover}
            <div class="game-info">
              <div class="game-title" title="${esc(g.title)}">${esc(g.title)}</div>
              <div class="game-meta">
                <span class="tag tag-genre">${esc(g.genre)}</span>
                <span class="tag tag-platform">${esc(g.platform)}</span>
              </div>
            </div>
            <div class="game-card-actions">
              <div class="action-btn action-btn-edit" onclick="openEditGame(${g.id},'${esc(g.title)}','${esc(g.genre)}','${esc(g.platform)}','${esc(g.notes||'')}','${g.cover_image||''}')" title="Edit">✏️</div>
              <div class="action-btn action-btn-del"  onclick="confirmDeleteGame(${g.id},'${esc(g.title)}')" title="Hapus">🗑️</div>
            </div>
          </div>`;
    }).join('');
}

// ── LOAD CATEGORIES ───────────────────────────────────────────
// Prak 4: fetch ke api/read.php?action=get_categories (GET)
async function loadCategories() {
    const res  = await fetch('api/read.php?action=get_categories');
    const data = await res.json();
    renderChips(data.genres || [], data.platforms || []);
    populateFilters(data.genres || [], data.platforms || []);
}

function renderChips(genres, platforms) {
    const wrap = document.getElementById('category-chips');
    const all  = [
        { label: 'Semua', type: '' },
        ...genres.map(g  => ({ label: '🎯 ' + g, type: 'genre',    value: g })),
        ...platforms.map(p => ({ label: '🖥 ' + p, type: 'platform', value: p })),
    ];
    wrap.innerHTML = all.map(c => {
        const isActive = (!c.type && !activeGenre && !activePlatform)
            || (c.type === 'genre'    && activeGenre    === c.value)
            || (c.type === 'platform' && activePlatform === c.value);
        return `<button class="chip ${isActive?'active':''}" onclick="filterBy('${c.type}','${c.value||''}')">${c.label}</button>`;
    }).join('');
}

function populateFilters(genres, platforms) {
    const gs = document.getElementById('filter-genre');
    const ps = document.getElementById('filter-platform');
    gs.innerHTML = '<option value="">Semua Genre</option>'    + genres.map(g => `<option value="${esc(g)}">${esc(g)}</option>`).join('');
    ps.innerHTML = '<option value="">Semua Platform</option>' + platforms.map(p => `<option value="${esc(p)}">${esc(p)}</option>`).join('');
    gs.value = activeGenre;
    ps.value = activePlatform;
}

function filterBy(type, value) {
    if      (type === 'genre')    { activeGenre = value; activePlatform = ''; }
    else if (type === 'platform') { activePlatform = value; activeGenre = ''; }
    else                          { activeGenre = ''; activePlatform = ''; }
    loadGames();
    loadCategories();
}

// ── SEARCH ────────────────────────────────────────────────────
let searchTimer;
document.getElementById('search-input').addEventListener('input', (e) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        searchQuery = e.target.value.trim();
        loadGames();
    }, 350);
});

document.getElementById('filter-genre').addEventListener('change', (e) => {
    activeGenre = e.target.value;
    loadGames(); loadCategories();
});
document.getElementById('filter-platform').addEventListener('change', (e) => {
    activePlatform = e.target.value;
    loadGames(); loadCategories();
});

// ── ADD GAME MODAL ────────────────────────────────────────────
function openAddGameModal() {
    document.getElementById('game-modal-title').textContent = 'Tambah Game';
    document.getElementById('game-form').reset();
    document.getElementById('game-id').value = '';
    clearCoverPreview();
    togglePlatformCustom();
    document.getElementById('remove-cover-flag').value = '0';
    document.getElementById('game-overlay').classList.add('active');
}

function closeGameModal() {
    document.getElementById('game-overlay').classList.remove('active');
}

// ── EDIT GAME MODAL ───────────────────────────────────────────
function openEditGame(id, title, genre, platform, notes, cover) {
    document.getElementById('game-modal-title').textContent = 'Edit Game';
    document.getElementById('game-id').value    = id;
    document.getElementById('g-title').value    = title;
    document.getElementById('g-genre').value    = genre;
    document.getElementById('remove-cover-flag').value = '0';

    const select  = document.getElementById('g-platform');
    const allOpts = Array.from(select.querySelectorAll('option')).map(o => o.value);
    if (allOpts.includes(platform)) {
        select.value = platform;
    } else {
        select.value = 'Lainnya (Ketik Manual)';
    }
    togglePlatformCustom();
    if (document.getElementById('g-platform-custom').style.display !== 'none') {
        document.getElementById('g-platform-custom').value = platform;
    }

    document.getElementById('g-notes').value = notes;
    cover ? showCoverPreview(cover) : clearCoverPreview();
    document.getElementById('game-overlay').classList.add('active');
}

// ── SUBMIT GAME FORM ──────────────────────────────────────────
// Prak 4: add  → POST  api/create.php
//         edit → POST  api/update.php  (multipart tidak bisa PUT di browser)
document.getElementById('game-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const id = document.getElementById('game-id').value;

    // Platform custom
    const platform = document.getElementById('g-platform').value;
    if (platform === 'Lainnya (Ketik Manual)') {
        const custom = document.getElementById('g-platform-custom').value.trim();
        if (!custom) { toast('Isi nama platform terlebih dahulu.', 'error'); return; }
        fd.set('platform', custom);
    }

    const endpoint = id ? 'api/update.php' : 'api/create.php';
    if (id) fd.append('id', id);

    const btn = e.target.querySelector('[type=submit]');
    btn.disabled = true; btn.textContent = 'Menyimpan...';

    const res = await postData(endpoint, fd);
    btn.disabled = false; btn.textContent = 'Simpan';

    if (res.success) {
        toast(res.message, 'success');
        closeGameModal();
        loadGames();
        loadCategories();
    } else {
        toast(res.message, 'error');
    }
});

// ── DELETE GAME ───────────────────────────────────────────────
// Prak 4: POST api/delete.php
let pendingDeleteId = null;
function confirmDeleteGame(id, title) {
    pendingDeleteId = id;
    document.getElementById('delete-game-title').textContent = title;
    document.getElementById('delete-overlay').classList.add('active');
}
function closeDeleteModal() { document.getElementById('delete-overlay').classList.remove('active'); }

document.getElementById('confirm-delete-btn').addEventListener('click', async () => {
    if (!pendingDeleteId) return;
    const fd = new FormData();
    fd.append('id', pendingDeleteId);
    const res = await postData('api/delete.php', fd);
    if (res.success) {
        toast(res.message, 'success');
        closeDeleteModal();
        loadGames();
        loadCategories();
    } else {
        toast(res.message, 'error');
    }
    pendingDeleteId = null;
});

// ── COVER PREVIEW ─────────────────────────────────────────────
document.getElementById('cover-input').addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        showCoverPreview(URL.createObjectURL(file));
        document.getElementById('remove-cover-flag').value = '0';
    }
});

function showCoverPreview(url) {
    document.getElementById('cover-upload-area').style.display = 'none';
    document.getElementById('cover-preview-wrap').style.display = '';
    document.getElementById('cover-preview-img').src = url;
}

function clearCoverPreview() {
    document.getElementById('cover-upload-area').style.display = '';
    document.getElementById('cover-preview-wrap').style.display = 'none';
    document.getElementById('cover-input').value = '';
}

function removeCover() {
    clearCoverPreview();
    document.getElementById('remove-cover-flag').value = '1';
}

// ── PLATFORM CUSTOM INPUT ─────────────────────────────────────
function togglePlatformCustom() {
    const sel    = document.getElementById('g-platform');
    const custom = document.getElementById('g-platform-custom');
    custom.style.display = (sel.value === 'Lainnya (Ketik Manual)') ? '' : 'none';
    custom.required      = (sel.value === 'Lainnya (Ketik Manual)');
}

// ── BUILD PLATFORM DROPDOWNS ──────────────────────────────────
function buildPlatformDropdowns() {
    document.querySelectorAll('.platform-select').forEach(sel => {
        sel.innerHTML = '<option value="">Pilih Platform</option>';
        PLATFORMS.forEach(group => {
            const og = document.createElement('optgroup');
            og.label = group.group;
            group.options.forEach(opt => {
                const o = document.createElement('option');
                o.value = opt; o.textContent = opt;
                og.appendChild(o);
            });
            sel.appendChild(og);
        });
    });
}

// ── ACCOUNT PAGE ──────────────────────────────────────────────
function switchAccountPanel(panel) {
    document.querySelectorAll('.account-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.account-nav-item').forEach(n => n.classList.remove('active'));
    document.getElementById('panel-' + panel).classList.add('active');
    document.querySelectorAll(`.account-nav-item[data-panel="${panel}"]`).forEach(n => n.classList.add('active'));
}

async function loadProfile() {
    const fd = new FormData();
    fd.append('action', 'get_profile');
    const res  = await postData('includes/account.php', fd);
    if (!res.success) return;
    const u = res.user;
    document.getElementById('profile-avatar').textContent         = (u.nickname || u.username || '?')[0].toUpperCase();
    document.getElementById('profile-username').textContent       = u.username;
    document.getElementById('profile-email-display').textContent  = u.email;
    document.getElementById('profile-joined').textContent         = 'Bergabung: ' + new Date(u.created_at).toLocaleDateString('id-ID', { year:'numeric', month:'long', day:'numeric' });
    document.getElementById('edit-nickname').value = u.nickname || '';
    document.getElementById('edit-email').value    = u.email;
}

document.getElementById('edit-profile-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('action', 'edit_profile');
    const res = await postData('includes/account.php', fd);
    showAlert('edit-profile-alert', res.message, res.success ? 'success' : 'error');
    if (res.success) {
        currentUser.nickname = res.nickname;
        document.getElementById('nav-nickname').textContent = res.nickname;
        loadProfile();
    }
});

document.getElementById('change-pass-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('action', 'change_password');
    const res = await postData('includes/account.php', fd);
    showAlert('change-pass-alert', res.message, res.success ? 'success' : 'error');
    if (res.success) e.target.reset();
});

document.getElementById('delete-account-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('action', 'delete_account');
    const res = await postData('includes/account.php', fd);
    showAlert('delete-account-alert', res.message, res.success ? 'success' : 'error');
    if (res.success) {
        setTimeout(() => {
            isLoggedIn = false; currentUser = null;
            showLanding();
            toast('Akun berhasil dihapus.', 'success');
        }, 1500);
    }
});

// ── HELPERS ───────────────────────────────────────────────────
async function postData(url, formData) {
    const res = await fetch(url, { method: 'POST', body: formData });
    return res.json();
}

function showAlert(id, msg, type) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg;
    el.className   = `alert alert-${type} show`;
    setTimeout(() => el.classList.remove('show'), 4000);
}

function toast(msg, type = 'success') {
    const container = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<span>${type === 'success' ? '✓' : '✕'}</span> ${msg}`;
    container.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

function esc(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// ── CLOSE MODALS ON BACKDROP CLICK ───────────────────────────
document.querySelectorAll('.overlay').forEach(ov => {
    ov.addEventListener('click', (e) => { if (e.target === ov) ov.classList.remove('active'); });
});
