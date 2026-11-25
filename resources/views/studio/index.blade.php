@php
  $initial = mb_strtoupper(mb_substr(trim((string) ($user->name ?: $user->business_name ?: 'U')), 0, 1));
@endphp
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>INAweb Studio — Website Usaha untuk UMKM</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('studio-assets/style.css') }}" />
</head>
<body>

<header class="topbar">
  <a class="brand" href="#" id="brandHome">
    <span class="brand-mark">i</span>
    <span class="brand-text">INA<strong>web</strong> <em>Studio</em></span>
  </a>

  <nav class="topnav" id="topnav">
    <a class="topnav-link is-active" href="#" data-view="gallery">Template</a>
    <a class="topnav-link" href="#" data-view="mysites">Situs saya</a>
    <a class="topnav-link" href="#" data-view="guide">Panduan</a>
  </nav>

  <div class="topbar-right">
    <span class="pill pill-soft">Gratis untuk anggota INAmikro</span>
    <div class="profile-wrap">
      <button
        type="button"
        class="avatar"
        id="avatarBtn"
        title="Detail akun {{ $user->name }}"
        aria-haspopup="true"
        aria-expanded="false"
        aria-controls="profilePop"
      >{{ $initial }}</button>

      <div class="profile-pop" id="profilePop" role="region" aria-label="Detail profil akun" hidden>
        <div class="profile-head">
          <div class="avatar avatar-lg" aria-hidden="true">{{ $initial }}</div>
          <div class="profile-id">
            <b>{{ $user->name }}</b>
            <small>{{ $user->email }}</small>
          </div>
        </div>

        <div class="profile-meta">
          <div class="profile-row">
            <span>Nama usaha</span>
            <b>{{ $user->business_name ?: '-' }}</b>
          </div>
          <div class="profile-row">
            <span>Alamat situs</span>
            <code>/{{ $user->slug }}</code>
          </div>
          <div class="profile-row">
            <span>Status akun</span>
            <span class="profile-role">{{ strtoupper((string) ($user->role ?: 'umkm')) }}</span>
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

<nav class="mobile-nav" id="mobileNav" aria-label="Navigasi utama">
  <button type="button" class="mobile-nav-item is-active" data-view="gallery"><span>▦</span>Template</button>
  <button type="button" class="mobile-nav-item" data-view="mysites"><span>▤</span>Situs saya</button>
  <button type="button" class="mobile-nav-item" data-view="guide"><span>?</span>Panduan</button>
</nav>

<main class="layout">

  <aside class="rail">
    <ol class="steps" id="steps">
      <li class="step is-active" data-step="1"><span class="step-num">1</span><div><b>Pilih template</b><small>Mulai dari desain siap pakai</small></div></li>
      <li class="step" data-step="2"><span class="step-num">2</span><div><b>Isi konten</b><small>Ganti teks &amp; foto usaha</small></div></li>
      <li class="step" data-step="3"><span class="step-num">3</span><div><b>Warna &amp; gaya</b><small>Sesuaikan dengan brand</small></div></li>
      <li class="step" data-step="4"><span class="step-num">4</span><div><b>Publikasikan</b><small>Bagikan link ke pembeli</small></div></li>
    </ol>

    <div class="help-card">
      <div class="help-icon">?</div>
      <b>Butuh bantuan?</b>
      <p>Tim pendamping INAmikro siap bantu isi konten pertamamu.</p>
      <button class="btn btn-ghost btn-sm" type="button" data-go="guide">Tanya admin</button>
    </div>
  </aside>

  <section class="stage">

    <!-- ============ GALERI TEMPLATE ============ -->
    <div class="view" id="view-gallery">
      <div class="stage-head">
        <div>
          <span class="eyebrow">Langkah 1 dari 4</span>
          <h1>Pilih template untuk usahamu</h1>
          <p class="lead">Semua template sudah jadi. Kamu hanya perlu mengganti teks, foto, dan kontak — tanpa coding.</p>
        </div>
        <div class="head-stat">
          <b id="templateCount">8</b><small>template siap pakai</small>
        </div>
      </div>

      <div class="filters" id="filters">
        <button class="chip is-active" data-cat="all">Semua</button>
        <button class="chip" data-cat="kuliner">Kuliner</button>
        <button class="chip" data-cat="fashion">Fashion</button>
        <button class="chip" data-cat="jasa">Jasa</button>
        <button class="chip" data-cat="retail">Toko &amp; Retail</button>
      </div>

      <div class="grid" id="templateGrid"></div>

      <div class="banner">
        <div>
          <span class="eyebrow eyebrow-light">Benefit anggota</span>
          <h3>Sudah punya situs dari pendamping INAmikro?</h3>
          <p>Hubungkan domain dan kelola kontennya dari studio ini.</p>
        </div>
        <button class="btn btn-light" type="button" id="btnConnectSite">Hubungkan situs</button>
      </div>
    </div>

    <!-- ============ EDITOR ============ -->
    <div class="view" id="view-editor" hidden>
      <div class="editor-bar">
        <button class="btn btn-ghost btn-icon" id="btnBack" type="button" title="Kembali ke template">←</button>
        <div class="editor-title">
          <b id="edTemplateName">Template</b>
          <small id="edStatus">Tersimpan otomatis</small>
        </div>

        <div class="seg" id="deviceToggle">
          <button class="seg-btn is-active" data-device="desktop" type="button">Desktop</button>
          <button class="seg-btn" data-device="mobile" type="button">Ponsel</button>
        </div>

        <div class="editor-actions">
          <button class="btn btn-ghost btn-sm" id="btnUndo" type="button" title="Urungkan perubahan terakhir">↶ Urungkan</button>
          <div class="history-wrap">
            <button class="btn btn-ghost btn-sm" id="btnHistory" type="button">Riwayat</button>
            <div class="history-pop" id="historyPop" hidden></div>
          </div>
          <button class="btn btn-ghost" id="btnReset" type="button">Reset</button>
          <button class="btn btn-outline" id="btnExport" type="button">Unduh HTML</button>
          <button class="btn btn-primary" id="btnPublish" type="button">Publikasikan</button>
        </div>
      </div>

      <div class="editor-body">
        <div class="form-col">
          <div class="revision-notice" id="revisionNotice" role="alert" hidden></div>
          <div class="checklist" id="checklist"></div>
          <form class="panel" id="form" autocomplete="off"></form>
        </div>

        <div class="preview-wrap">
          <div class="preview-chrome">
            <span class="dot"></span><span class="dot"></span><span class="dot"></span>
            <span class="url">{{ $publicBaseUrl }}/<span id="previewDomain">{{ $user->slug }}</span></span>
          </div>
          <div class="preview device-desktop" id="previewBox">
            <iframe id="preview" title="Pratinjau website"></iframe>
          </div>
          <div class="preview-hint">Perubahan langsung terlihat di sini.</div>
        </div>
      </div>
    </div>

    <!-- ============ SITUS SAYA ============ -->
    <div class="view" id="view-mysites" hidden>
      <div class="stage-head">
        <div>
          <span class="eyebrow">Satu akun, satu halaman</span>
          <h1>Situs saya</h1>
          <p class="lead">Satu UMKM cukup punya satu landing page. Pilih template, isi sekali, lalu edit lagi kapan saja kalau ada perubahan.</p>
        </div>
        <div class="head-stat"><b id="siteCount">0/1</b><small>landing page terpakai</small></div>
      </div>

      <div class="site-list" id="siteList"></div>

      <div class="empty-state" id="siteEmpty" hidden>
        <div class="empty-icon">▤</div>
        <h3>Belum punya landing page</h3>
        <p>Mulai dari template yang paling mirip dengan usahamu. Isinya bisa kamu ganti sesuai selera.</p>
        <button class="btn btn-primary" type="button" data-go="gallery">Buat landing page</button>
      </div>

      <div class="note-card">
        <b>Satu UMKM satu landing page</b>
        <p>Agar tidak ribet, setiap akun hanya punya satu halaman. Setelah dipublikasikan, alamatnya jadi <b>{{ $publicSiteUrl }}</b>. Semua perubahan tersimpan otomatis di akun INAmikro milikmu.</p>
      </div>
    </div>

    <!-- ============ PANDUAN ============ -->
    <div class="view" id="view-guide" hidden>
      <div class="stage-head">
        <div>
          <span class="eyebrow">Panduan singkat</span>
          <h1>Bikin website usaha dalam 4 langkah</h1>
          <p class="lead">Tidak perlu tahu coding. Pilih template, ganti isinya, lalu situs siap dipakai.</p>
        </div>
      </div>

      <div class="guide-grid">
        <article class="guide-card"><span class="guide-num">1</span><h3>Pilih template</h3><p>Pilih desain yang paling mirip dengan usahamu. Ada template untuk kuliner, fashion, jasa, dan toko.</p></article>
        <article class="guide-card"><span class="guide-num">2</span><h3>Isi konten</h3><p>Ganti nama usaha, tagline, deskripsi, menu atau produk, dan kontak. Foto utama opsional — kalau belum punya, template memakai inisial usaha. Upload foto dari galeri HP atau folder komputer; foto tiap item produk juga bisa berbeda. Jumlah item tidak dibatasi.</p></article>
        <article class="guide-card"><span class="guide-num">3</span><h3>Atur warna</h3><p>Pilih warna utama supaya situs mirip identitas brand-mu. Perubahan langsung terlihat di pratinjau.</p></article>
        <article class="guide-card"><span class="guide-num">4</span><h3>Simpan &amp; pakai</h3><p>Klik <b>Publikasikan</b> agar halaman tampil di <b>{{ $publicSiteUrl }}</b>, atau klik <b>Unduh HTML</b> untuk menyimpan salinan file situs.</p></article>
      </div>

      <div class="guide-block">
        <h2>Tips supaya situs lebih dipercaya</h2>
        <div class="tip-row">
          <div><b>Foto asli</b><p>Gunakan foto produk atau usahamu sendiri, bukan gambar dari internet.</p></div>
          <div><b>Nomor WhatsApp aktif</b><p>Pembeli akan langsung menemukan tombol WhatsApp di situs.</p></div>
          <div><b>Harga jelas</b><p>Isi harga dalam angka, tanpa titik atau huruf. Nanti otomatis jadi format Rupiah.</p></div>
        </div>
      </div>

      <div class="guide-block">
        <h2>Pertanyaan yang sering ditanyakan</h2>
        <details class="faq"><summary>Apakah bisa gratis?</summary><p>Gratis untuk seluruh anggota UMKM INAmikro. Kamu dapat membuat, menyimpan, dan mempublikasikan halaman usaha tanpa biaya.</p></details>
        <details class="faq"><summary>Foto dari HP bisa langsung diupload?</summary><p>Bisa. Saat memilih foto, HP akan membuka galeri dan komputer akan membuka folder file. Foto akan diperkecil otomatis supaya situs tetap ringan.</p></details>
        <details class="faq"><summary>Bisa diedit lagi nanti?</summary><p>Bisa. Buka menu <b>Situs saya</b>, lalu pilih situs untuk melanjutkan pengeditan kapan saja.</p></details>
        <details class="faq"><summary>Bagaimana cara mempublikasikannya?</summary><p>Lengkapi 4 item checklist di editor lalu klik <b>Publikasikan</b>. Alamat situsmu aktif di <b>{{ $publicSiteUrl }}</b>.</p></details>
        <details class="faq"><summary>Bagaimana cara masuk ke akun?</summary><p>Memakai email dan kata sandi akun UMKM yang kamu daftarkan saat membuat akun INAweb Studio.</p></details>
      </div>

      <div class="banner">
        <div>
          <span class="eyebrow eyebrow-light">Masih bingung?</span>
          <h3>Tim pendamping INAmikro siap membantu</h3>
          <p>Tanyakan cara mengisi konten atau upload foto lewat admin pendamping.</p>
        </div>
        <button class="btn btn-light" type="button" id="guideAsk">Tanya admin</button>
      </div>
    </div>

  </section>
</main>

<div class="modal-backdrop" id="confirmModal" hidden>
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle" aria-describedby="confirmText">
    <div class="modal-mark" aria-hidden="true">!</div>
    <h2 id="confirmTitle">Ganti landing page?</h2>
    <p id="confirmText">Kamu sudah punya landing page <b id="confirmName">halaman lama</b>. Memilih template <b id="confirmTpl">template baru</b> akan mengganti tampilannya.</p>
    <p class="modal-sub" id="confirmSub">Nama, deskripsi, produk, dan foto kamu akan ikut terbawa.</p>
    <div class="modal-actions">
      <button class="btn btn-outline" type="button" id="confirmCancel">Batal</button>
      <button class="btn btn-primary" type="button" id="confirmOk">Ya, ganti</button>
    </div>
  </div>
</div>

<div class="toast" id="toast" role="status"></div>

<script>
  window.INAWEB_CONFIG = @json($studioConfig);
</script>
<script src="{{ asset('studio-assets/templates.js') }}"></script>
<script src="{{ asset('studio-assets/app.js') }}"></script>
</body>
</html>
