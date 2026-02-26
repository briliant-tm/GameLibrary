<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameVault — Library Game Pribadimu</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="description" content="Simpan dan kelola koleksi game pribadimu dengan GameVault.">
</head>
<body>

<!-- ════════════════ NAVBAR ════════════════════════════════ -->
<nav class="navbar">
    <a href="#" class="nav-logo" onclick="isLoggedIn ? showLibrary() : showLanding()">
        <div class="logo-icon">🎮</div>
        Game<span>Vault</span>
    </a>

    <!-- Logged out -->
    <div id="nav-logged-out" class="nav-actions">
        <button class="btn btn-ghost" onclick="openAuthModal('login')">Masuk</button>
        <button class="btn btn-primary" onclick="openAuthModal('register')">Daftar Gratis</button>
    </div>

    <!-- Logged in -->
    <div id="nav-logged-in" class="nav-actions" style="display:none">
        <button class="btn btn-ghost" onclick="showLibrary()">📚 Library</button>
        <button class="btn btn-ghost" onclick="showAccount()">👤 Akun</button>
        <div class="nav-user">Halo, <strong id="nav-nickname">User</strong></div>
        <button class="btn btn-outline btn-sm" onclick="logout()">Keluar</button>
    </div>
</nav>

<!-- ════════════════ LANDING PAGE ══════════════════════════ -->
<div id="landing-page">
    <section class="hero main-container">
        <div class="hero-badge">✦ Gratis &amp; Tanpa Iklan</div>
        <h1 class="hero-title">
            Koleksi Gamemu,<br>
            Satu <em>Vault</em>
        </h1>
        <p class="hero-desc">
            Simpan, lacak, dan kategorikan semua game yang pernah kamu mainkan.
            Dari retro hingga next-gen, semua ada di sini.
        </p>
        <div class="hero-cta">
            <button class="btn btn-primary" style="padding:12px 28px;font-size:1rem;" onclick="openAuthModal('register')">
                🎮 Mulai Sekarang
            </button>
            <button class="btn btn-outline" style="padding:12px 24px;font-size:1rem;" onclick="openAuthModal('login')">
                Sudah punya akun?
            </button>
        </div>

        <div class="hero-stats">
            <div class="stat-item">
                <div class="stat-num">∞</div>
                <div class="stat-label">Game yang bisa disimpan</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">20+</div>
                <div class="stat-label">Platform tersedia</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">100%</div>
                <div class="stat-label">Privasi milikmu</div>
            </div>
        </div>
    </section>
</div>

<!-- ════════════════ LIBRARY PAGE ══════════════════════════ -->
<div id="library-page" class="main-container" style="display:none">
    <div class="library-header">
        <div>
            <h2 class="library-title">🎮 Library Saya</h2>
            <p class="library-subtitle" id="game-count">Memuat...</p>
        </div>
        <button class="btn btn-primary" onclick="openAddGameModal()">+ Tambah Game</button>
    </div>

    <!-- Category chips -->
    <div id="category-chips" class="category-chips"></div>

    <!-- Toolbar -->
    <div class="toolbar">
        <div class="search-wrap">
            <span class="search-icon">🔍</span>
            <input type="text" id="search-input" class="form-control search-input" placeholder="Cari judul game...">
        </div>
        <select id="filter-genre" class="form-control filter-select">
            <option value="">Semua Genre</option>
        </select>
        <select id="filter-platform" class="form-control filter-select">
            <option value="">Semua Platform</option>
        </select>
    </div>

    <!-- Game Grid -->
    <div id="game-grid" class="game-grid">
        <div class="empty-state">
            <div class="empty-icon">⏳</div>
            <div class="empty-title">Memuat library...</div>
        </div>
    </div>
</div>

<!-- ════════════════ ACCOUNT PAGE ══════════════════════════ -->
<div id="account-page" class="main-container" style="display:none">
    <h2 style="font-family:'Syne',sans-serif;margin-bottom:1.5rem">⚙️ Pengaturan Akun</h2>

    <div class="account-layout">
        <!-- Sidebar -->
        <div class="account-sidebar">
            <div class="avatar-circle" id="profile-avatar">U</div>
            <div class="account-username" id="profile-username">Username</div>
            <div class="account-email" id="profile-email-display">email@example.com</div>
            <div class="account-joined" id="profile-joined"></div>

            <div class="account-nav">
                <button class="account-nav-item" data-panel="profile" onclick="switchAccountPanel('profile')">
                    👤 Profil Saya
                </button>
                <button class="account-nav-item" data-panel="edit" onclick="switchAccountPanel('edit')">
                    ✏️ Edit Nickname & Email
                </button>
                <button class="account-nav-item" data-panel="password" onclick="switchAccountPanel('password')">
                    🔒 Ganti Password
                </button>
                <button class="account-nav-item danger" data-panel="delete" onclick="switchAccountPanel('delete')">
                    🗑️ Hapus Akun
                </button>
            </div>
        </div>

        <!-- Panels -->
        <div>
            <!-- Profile -->
            <div id="panel-profile" class="account-panel active">
                <div class="panel-title">Profil Saya</div>
                <p class="panel-desc">Informasi akun kamu tersimpan di sini.</p>
                <div style="display:grid;gap:1rem">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" id="view-username" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nickname</label>
                        <input type="text" class="form-control" id="view-nickname" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="text" class="form-control" id="view-email" readonly>
                    </div>
                </div>
            </div>

            <!-- Edit Profile -->
            <div id="panel-edit" class="account-panel">
                <div class="panel-title">Edit Nickname &amp; Email</div>
                <p class="panel-desc">Ubah nama tampilan dan alamat emailmu.</p>
                <div id="edit-profile-alert" class="alert"></div>
                <form id="edit-profile-form">
                    <div class="form-group">
                        <label class="form-label">Nickname</label>
                        <input type="text" id="edit-nickname" name="nickname" class="form-control" placeholder="Nama tampilan kamu" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" id="edit-email" name="email" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
                </form>
            </div>

            <!-- Change Password -->
            <div id="panel-password" class="account-panel">
                <div class="panel-title">Ganti Password</div>
                <p class="panel-desc">Pastikan passwordmu kuat dan unik.</p>
                <div id="change-pass-alert" class="alert"></div>
                <form id="change-pass-form">
                    <div class="form-group">
                        <label class="form-label">Password Lama</label>
                        <input type="password" name="old_password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>
                    <button type="submit" class="btn btn-primary">🔒 Ubah Password</button>
                </form>
            </div>

            <!-- Delete Account -->
            <div id="panel-delete" class="account-panel">
                <div class="panel-title">Hapus Akun</div>
                <p class="panel-desc">Ini tindakan permanen dan tidak bisa dibatalkan.</p>
                <div class="danger-zone">
                    <div class="danger-title">⚠️ Zona Berbahaya</div>
                    <p class="danger-desc">
                        Seluruh data akun dan library game kamu akan dihapus secara permanen.
                        Masukkan password untuk konfirmasi.
                    </p>
                    <div id="delete-account-alert" class="alert"></div>
                    <form id="delete-account-form">
                        <div class="form-group">
                            <label class="form-label">Konfirmasi Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Masukkan password kamu" required>
                        </div>
                        <button type="submit" class="btn btn-danger">🗑️ Hapus Akun Sekarang</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════ AUTH OVERLAY ═══════════════════════════ -->
<div id="auth-overlay" class="overlay">
    <div class="modal">
        <button class="modal-close" onclick="closeAuthModal()">✕</button>
        <div class="modal-title">Selamat Datang</div>
        <p class="modal-subtitle">Masuk atau buat akun baru untuk mulai.</p>

        <div class="modal-tabs">
            <button class="modal-tab active" data-tab="login" onclick="switchAuthTab('login')">Masuk</button>
            <button class="modal-tab" data-tab="register" onclick="switchAuthTab('register')">Daftar</button>
        </div>

        <!-- Login Form -->
        <div id="login-form-wrap">
            <div id="login-alert" class="alert"></div>
            <form id="login-form">
                <div class="form-group">
                    <label class="form-label">Username / Email</label>
                    <input type="text" name="identifier" class="form-control" placeholder="username atau email" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem">🔓 Masuk</button>
            </form>
            <p style="text-align:center;color:var(--text-muted);font-size:0.8rem;margin-top:1.2rem">
                Belum punya akun? <a href="#" onclick="switchAuthTab('register')" style="color:var(--accent)">Daftar gratis</a>
            </p>
        </div>

        <!-- Register Form -->
        <div id="register-form-wrap" style="display:none">
            <div id="register-alert" class="alert"></div>
            <form id="register-form">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="username unik" required autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nickname</label>
                        <input type="text" name="nickname" class="form-control" placeholder="nama tampilan" autocomplete="nickname">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="email@kamu.com" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="min. 6 karakter" required autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Konfirmasi</label>
                        <input type="password" name="password2" class="form-control" placeholder="ulangi password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem">🎮 Buat Akun</button>
            </form>
        </div>
    </div>
</div>

<!-- ════════════════ ADD/EDIT GAME OVERLAY ══════════════════ -->
<div id="game-overlay" class="overlay">
    <div class="modal" style="max-width:520px">
        <button class="modal-close" onclick="closeGameModal()">✕</button>
        <div class="modal-title" id="game-modal-title">Tambah Game</div>
        <p class="modal-subtitle">Isi detail game yang ingin kamu tambahkan.</p>

        <form id="game-form" enctype="multipart/form-data">
            <input type="hidden" id="game-id" name="id">
            <input type="hidden" id="remove-cover-flag" name="remove_cover" value="0">

            <div class="form-group">
                <label class="form-label">Judul Game *</label>
                <input type="text" id="g-title" name="title" class="form-control" placeholder="cth. The Witcher 3: Wild Hunt" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Genre *</label>
                    <input type="text" id="g-genre" name="genre" class="form-control" placeholder="cth. RPG, Action, Puzzle..." required list="genre-suggestions">
                    <datalist id="genre-suggestions">
                        <option value="Action">
                        <option value="RPG">
                        <option value="Adventure">
                        <option value="Strategy">
                        <option value="Simulation">
                        <option value="Sports">
                        <option value="Racing">
                        <option value="Fighting">
                        <option value="Horror">
                        <option value="Puzzle">
                        <option value="Shooter">
                        <option value="Platformer">
                        <option value="Sandbox">
                        <option value="MMORPG">
                        <option value="Visual Novel">
                        <option value="Rhythm">
                    </datalist>
                </div>
                <div class="form-group">
                    <label class="form-label">Platform *</label>
                    <select id="g-platform" name="platform" class="form-control platform-select" onchange="togglePlatformCustom()" required>
                        <option value="">Pilih Platform</option>
                    </select>
                </div>
            </div>

            <!-- Custom platform input -->
            <div class="form-group" id="platform-custom-group" style="display:none;margin-top:-6px">
                <input type="text" id="g-platform-custom" class="form-control" placeholder="Ketik nama platform...">
            </div>

            <!-- Cover upload -->
            <div class="form-group">
                <label class="form-label">Cover Game (opsional)</label>
                <div id="cover-upload-area" class="cover-upload-area">
                    <input type="file" id="cover-input" name="cover" accept="image/jpeg,image/png,image/webp">
                    <div class="cover-upload-icon">🖼️</div>
                    <div class="cover-upload-text">Klik untuk upload · JPG, PNG, WEBP · Maks 2MB</div>
                </div>
                <div id="cover-preview-wrap" style="display:none;margin-top:8px">
                    <div class="cover-preview-wrap">
                        <img id="cover-preview-img" class="cover-preview" src="" alt="Preview Cover">
                        <button type="button" class="cover-remove" onclick="removeCover()" title="Hapus cover">✕</button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Catatan (opsional)</label>
                <textarea id="g-notes" name="notes" class="form-control" placeholder="Kesan, rating personal, atau catatan lainnya..." rows="2"></textarea>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:0.5rem">
                <button type="button" class="btn btn-ghost" onclick="closeGameModal()">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ════════════════ DELETE CONFIRM OVERLAY ═════════════════ -->
<div id="delete-overlay" class="overlay">
    <div class="modal" style="max-width:380px;text-align:center">
        <div style="font-size:2.5rem;margin-bottom:1rem">🗑️</div>
        <div class="modal-title" style="margin-bottom:0.5rem">Hapus Game?</div>
        <p style="color:var(--text-secondary);margin-bottom:1.5rem">
            Kamu yakin ingin menghapus<br>
            <strong id="delete-game-title" style="color:var(--text-primary)"></strong>?<br>
            <small style="color:var(--text-muted)">Tindakan ini tidak bisa dibatalkan.</small>
        </p>
        <div style="display:flex;gap:10px;justify-content:center">
            <button class="btn btn-outline" onclick="closeDeleteModal()">Batal</button>
            <button id="confirm-delete-btn" class="btn btn-danger">Hapus</button>
        </div>
    </div>
</div>

<!-- ════════════════ TOAST CONTAINER ════════════════════════ -->
<div id="toast-container" class="toast-container"></div>

<script src="assets/js/app.js"></script>
<script>
// Patch: load profile view fields after DOM ready
const origLoadProfile = loadProfile;
loadProfile = async function() {
    await origLoadProfile();
    const u = await fetch('includes/account.php', {
        method: 'POST',
        body: new URLSearchParams({ action: 'get_profile' })
    }).then(r => r.json());
    if (u.success) {
        const vun = document.getElementById('view-username');
        const vnn = document.getElementById('view-nickname');
        const vem = document.getElementById('view-email');
        if (vun) vun.value = u.user.username;
        if (vnn) vnn.value = u.user.nickname || u.user.username;
        if (vem) vem.value = u.user.email;
    }
};
// Fix platform custom toggle reference
function togglePlatformCustom() {
    const sel = document.getElementById('g-platform');
    const customGrp = document.getElementById('platform-custom-group');
    const custom    = document.getElementById('g-platform-custom');
    const show = sel.value === 'Lainnya (Ketik Manual)';
    customGrp.style.display = show ? '' : 'none';
    custom.required = show;
}
</script>
</body>
</html>
