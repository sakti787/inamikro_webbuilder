@php
  $initial = mb_strtoupper(mb_substr(trim((string) ($user->name ?: 'A')), 0, 1));
@endphp
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Panel Pendamping — INAweb Studio</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('admin-assets/style.css') }}" />
</head>
<body>

<header class="topbar">
  <a class="brand" href="#" data-view="dashboard">
    <span class="brand-mark">i</span>
    <span class="brand-text">INA<strong>web</strong> <em>Studio</em></span>
  </a>
  <span class="role-chip">Panel Pendamping</span>
  <div class="topbar-right">
    <div class="profile-wrap">
      <button
        type="button"
        class="avatar"
        id="adminAvatarBtn"
        title="Detail akun {{ $user->name }}"
        aria-haspopup="true"
        aria-expanded="false"
        aria-controls="adminProfilePop"
      >{{ $initial }}</button>

      <div class="profile-pop" id="adminProfilePop" role="region" aria-label="Detail profil admin" hidden>
        <div class="profile-head">
          <div class="avatar avatar-lg" aria-hidden="true">{{ $initial }}</div>
          <div class="profile-id">
            <b>{{ $user->name }}</b>
            <small>{{ $user->email }}</small>
          </div>
        </div>

        <div class="profile-meta">
          <div class="profile-row">
            <span>Peran akun</span>
            <span class="profile-role">{{ strtoupper((string) ($user->role ?: 'admin')) }}</span>
          </div>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="logout-form">
          @csrf
          <button type="submit" class="profile-logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
              <polyline points="16 17 21 12 16 7"></polyline>
              <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Keluar</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</header>

<div class="layout">
  <aside class="rail">
    <div class="rail-label">MENU ADMIN</div>
    <nav class="side-nav" id="sideNav">
      <button class="side-link is-active" data-view="dashboard" type="button"><span>▦</span>Beranda</button>
      <button class="side-link" data-view="umkm" type="button"><span>▤</span>UMKM</button>
      <button class="side-link" data-view="antrean" type="button"><span>✓</span>Antrean tayang <b class="nav-count" id="navQueue">0</b></button>
      <button class="side-link" data-view="log" type="button"><span>◷</span>Aktivitas</button>
      <button class="side-link" data-view="template" type="button"><span>▧</span>Template</button>
      <button class="side-link" data-view="statistik" type="button"><span>▥</span>Statistik</button>
      <button class="side-link" data-view="pengaturan" type="button"><span>⚙</span>Pengaturan</button>
    </nav>
    <div class="rail-foot">
      <b>Prinsip pendamping</b>
      <p>Periksa kelengkapan 4 syarat wajib, beri catatan revisi bila perlu, dan setujui agar situs tayang publik.</p>
    </div>
  </aside>

  <main class="stage">

    <!-- BERANDA -->
    <section class="view" id="view-dashboard">
      <div class="stage-head">
        <div><span class="eyebrow">Ringkasan</span><h1>Halo, admin pendamping</h1><p class="lead">Pantau UMKM dan bantu mereka sampai situsnya tayang.</p></div>
      </div>
      <div class="stats" id="statCards"></div>
      <div class="panel">
        <div class="panel-head"><b>Butuh tindakan</b><button class="btn btn-ghost btn-sm" data-view="antrean" type="button">Lihat antrean →</button></div>
        <div id="actionList"></div>
      </div>
      <div class="panel">
        <div class="panel-head"><b>Aktivitas terakhir</b><button class="btn btn-ghost btn-sm" data-view="log" type="button">Lihat semua log →</button></div>
        <div id="dashboardLogs"></div>
      </div>
      <div class="tip-card"><b>Prinsip pendamping</b><p>Admin memberi catatan dan menyetujui tayang. Konten tetap diisi UMKM sendiri, supaya jejak perubahannya jelas.</p></div>
    </section>

    <!-- DAFTAR UMKM -->
    <section class="view" id="view-umkm" hidden>
      <div class="stage-head">
        <div><span class="eyebrow">Direktori UMKM</span><h1>Daftar UMKM</h1><p class="lead">Satu akun, satu landing page. Cari nama usaha atau saring berdasarkan status.</p></div>
      </div>
      <div class="toolbar">
        <input class="input search" id="umkmSearch" type="search" placeholder="Cari nama usaha atau email..." aria-label="Cari UMKM" />
        <div class="chips" id="umkmFilters">
          <button class="chip is-active" data-status="semua" type="button">Semua</button>
          <button class="chip" data-status="Menunggu" type="button">Menunggu</button>
          <button class="chip" data-status="Tayang" type="button">Tayang</button>
          <button class="chip" data-status="Draf" type="button">Draf</button>
        </div>
      </div>
      <div class="panel table-wrap"><table class="table"><thead><tr><th>Usaha</th><th>Status</th><th>Kelengkapan</th><th>Template</th><th>Diubah</th><th></th></tr></thead><tbody id="umkmRows"></tbody></table></div>
    </section>

    <!-- DETAIL UMKM -->
    <section class="view" id="view-detail" hidden>
      <div class="detail-head">
        <button class="btn btn-ghost btn-sm" data-view="umkm" type="button">← Kembali</button>
        <span class="eyebrow">Detail UMKM</span>
        <div class="detail-head-row">
          <h1 id="detailName">Nama usaha</h1>
          <a class="btn btn-outline btn-sm" id="detailPublicLink" href="#" target="_blank" rel="noopener" hidden>Lihat situs ↗</a>
        </div>
        <p class="lead" id="detailMeta"></p>
      </div>
      <div class="detail-grid">
        <div>
          <div class="panel preview-panel">
            <div class="panel-head"><b>Pratinjau situs</b><span class="muted" id="detailTpl"></span></div>
            <iframe id="detailPreview" sandbox="allow-scripts" title="Pratinjau landing page UMKM"></iframe>
          </div>
          <div class="panel">
            <div class="panel-head"><b>Informasi usaha</b></div>
            <div class="info-grid" id="detailInfo"></div>
          </div>
        </div>
        <div>
          <div class="panel">
            <div class="panel-head"><b>Catatan pendamping</b><span class="muted">Hanya untuk admin</span></div>
            <div class="detail-nosite-note" id="detailNoSiteNote" hidden>UMKM ini belum membuat draf situs di Studio.</div>
            <textarea class="input" id="detailNote" rows="4" placeholder="Tulis catatan untuk tim, misalnya: nomor WA belum aktif."></textarea>
            <div class="row-actions"><button class="btn btn-outline btn-sm" id="btnSaveNote" type="button">Simpan catatan</button><button class="btn btn-primary btn-sm" id="btnApprove" type="button">Setujui tayang</button></div>
            <button class="btn btn-ghost btn-sm wide" id="btnRevise" type="button">Minta revisi ke UMKM</button>
          </div>
          <div class="panel">
            <div class="panel-head"><b>Riwayat pendampingan</b></div>
            <div class="timeline" id="detailHistory"></div>
          </div>
        </div>
      </div>
    </section>

    <!-- ANTREAN TAYANG -->
    <section class="view" id="view-antrean" hidden>
      <div class="stage-head"><div><span class="eyebrow">Verifikasi</span><h1>Antrean tayang</h1><p class="lead">Periksa situs yang diminta publish. Setujui bila sudah layak, atau kembalikan dengan catatan revisi.</p></div></div>
      <div class="auto-bar">
        <div class="auto-bar-text">
          <b>Auto setujui tayang</b>
          <span id="autoNote">Nonaktif — setiap situs ditinjau manual.</span>
        </div>
        <label class="toggle" title="Setujui otomatis saat 4 syarat terpenuhi">
          <input type="checkbox" id="autoApprove" aria-label="Auto setujui tayang" />
          <span class="track"></span>
        </label>
      </div>
      <div class="queue-list" id="queueList"></div>
    </section>

    <!-- LOG AKTIVITAS -->
    <section class="view" id="view-log" hidden>
      <div class="stage-head"><div><span class="eyebrow">Jejak aktivitas</span><h1>Log aktivitas</h1><p class="lead">Setiap tindakan pendamping tercatat di sini. Entri terbaru ada di atas.</p></div></div>
      <div class="panel">
        <div class="panel-head"><b>Semua aktivitas</b><span class="muted" id="logCount"></span></div>
        <div id="logList"></div>
        <div class="pager" id="logPager"></div>
      </div>
    </section>

    <!-- PENGATURAN -->
    <section class="view" id="view-pengaturan" hidden>
      <div class="stage-head"><div><span class="eyebrow">Pengaturan</span><h1>Pengaturan</h1><p class="lead">Atur identitas pendamping yang dipakai saat UMKM menghubungi tim.</p></div></div>
      <div class="panel form-panel">
        <div class="panel-head"><b>Kontak pendamping</b><span class="muted">Aktif untuk Studio UMKM</span></div>
        <div class="form-grid">
          <div class="form-field"><label for="adminNama">Nama tampilan</label><input class="input" id="adminNama" type="text" placeholder="Tim INAmikro" /></div>
          <div class="form-field"><label for="adminWa">Nomor WhatsApp</label><input class="input" id="adminWa" type="tel" placeholder="0812xxxxxxx" /><small>Kosongkan bila belum ada. Boleh ditulis 08… atau +62…</small></div>
        </div>
        <div class="form-preview"><b>Pratinjau tautan</b><p class="preview-note" id="adminPreview"></p></div>
        <div class="form-actions"><button class="btn btn-primary btn-sm" id="btnSaveAdmin" type="button">Simpan perubahan</button></div>
      </div>
      <div class="tip-card"><b>Catatan</b><p>Nomor WhatsApp di atas langsung dipakai oleh tombol Tanya admin di Studio UMKM.</p></div>
    </section>

    <!-- TEMPLATE -->
    <section class="view" id="view-template" hidden>
      <div class="stage-head"><div><span class="eyebrow">Tampilan</span><h1>Kelola template</h1><p class="lead">Aktif atau nonaktifkan template yang boleh dipakai UMKM.</p></div></div>
      <div class="tpl-grid" id="tplAdminGrid"></div>
    </section>

    <!-- STATISTIK -->
    <section class="view" id="view-statistik" hidden>
      <div class="stage-head"><div><span class="eyebrow">Statistik</span><h1>Statistik</h1><p class="lead">Ringkasan status landing page UMKM dan kategori yang sudah tayang.</p></div></div>
      <div class="stats" id="statBody"></div>
      <div class="panel"><div class="panel-head"><b>Situs tayang per kategori</b></div><div id="statBars"></div></div>
    </section>

  </main>
</div>

<nav class="mobile-nav" id="mobileNav" aria-label="Navigasi admin">
  <button class="mobile-nav-item is-active" data-view="dashboard" type="button"><span>▦</span>Beranda</button>
  <button class="mobile-nav-item" data-view="umkm" type="button"><span>▤</span>UMKM</button>
  <button class="mobile-nav-item" data-view="antrean" type="button"><span>✓</span>Antrean</button>
  <button class="mobile-nav-item" data-view="log" type="button"><span>◷</span>Log</button>
</nav>

<div class="modal-backdrop" id="reasonModal" hidden>
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="reasonTitle">
    <h2 id="reasonTitle">Minta revisi</h2>
    <p id="reasonFor">Tulis bagian yang perlu diperbaiki UMKM.</p>
    <textarea class="input" id="reasonText" rows="3" placeholder="Contoh: jam buka belum diisi, tolong lengkapi ya."></textarea>
    <div class="modal-actions"><button class="btn btn-outline" id="reasonCancel" type="button">Batal</button><button class="btn btn-primary" id="reasonOk" type="button">Kirim revisi</button></div>
  </div>
</div>

<div class="toast" id="toast" role="status"></div>
<script>
  window.INAWEB_ADMIN_CONFIG = @json($adminConfig);
</script>
<script src="{{ asset('studio-assets/templates.js') }}"></script>
<script src="{{ asset('admin-assets/log.js') }}"></script>
<script src="{{ asset('admin-assets/settings.js') }}"></script>
<script src="{{ asset('admin-assets/app.js') }}"></script>
</body>
</html>
