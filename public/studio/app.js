/* ============================================================
   app.js — galeri template, editor konten, pratinjau, API Laravel
   ============================================================ */

const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];

const CFG = window.INAWEB_CONFIG || {};
const ROUTES = CFG.routes || {
  siteShow: "/api/site",
  siteSave: "/api/site",
  sitePublish: "/api/site/publish",
  siteDelete: "/api/site",
  mediaStore: "/api/media",
  mediaDelete: "/api/media",
};

const SWATCHES = ["#dc2626", "#0f172a", "#0d9488", "#7c3aed", "#d97706", "#e11d48", "#2563eb", "#15803d"];

const getCsrfToken = () =>
  CFG.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

function normalizeHex(color, fallback = "#dc2626") {
  return /^#[0-9a-fA-F]{6}$/.test(String(color || "")) ? String(color) : fallback;
}

function normalizeProduct(p) {
  return {
    nama: String(p?.nama ?? ""),
    harga: p?.harga !== undefined && p?.harga !== null ? String(p.harga) : "",
    ket: String(p?.ket ?? ""),
    img: String(p?.img ?? ""),
    imgName: String(p?.imgName ?? ""),
    imgSize: String(p?.imgSize ?? ""),
  };
}

function normalizeContent(raw, tpl) {
  const fresh = defaultsFor(tpl);
  const src = raw && typeof raw === "object" ? raw : {};
  const produk = Array.isArray(src.produk) && src.produk.length
    ? src.produk.map(normalizeProduct)
    : fresh.produk.map((p) => normalizeProduct(p));

  return {
    ...fresh,
    ...src,
    template: tpl.id,
    variant: src.variant !== undefined && src.variant !== null ? !!src.variant : !!tpl.variant,
    brand: normalizeHex(src.brand || fresh.brand, fresh.brand),
    nama: src.nama !== undefined ? String(src.nama ?? "") : fresh.nama,
    tagline: src.tagline !== undefined ? String(src.tagline ?? "") : fresh.tagline,
    kategori: src.kategori !== undefined ? String(src.kategori ?? "") : fresh.kategori,
    deskripsi: src.deskripsi !== undefined ? String(src.deskripsi ?? "") : fresh.deskripsi,
    heroImg: String(src.heroImg ?? ""),
    heroName: String(src.heroName ?? ""),
    heroSize: String(src.heroSize ?? ""),
    wa: src.wa !== undefined ? String(src.wa ?? "") : fresh.wa,
    ig: src.ig !== undefined ? String(src.ig ?? "") : fresh.ig,
    alamat: src.alamat !== undefined ? String(src.alamat ?? "") : fresh.alamat,
    jam: src.jam !== undefined ? String(src.jam ?? "") : fresh.jam,
    updatedAt: typeof src.updatedAt === "number" ? src.updatedAt : Date.now(),
    produk,
  };
}

function normalizeSite(raw) {
  if (!raw || typeof raw !== "object") return null;
  const tpl = TEMPLATES.find((x) => x.id === raw.template_id) || TEMPLATES[0];
  const content = normalizeContent({ ...(raw.content || {}), brand: raw.brand_color || raw.content?.brand }, tpl);
  return {
    id: raw.id ?? null,
    template_id: tpl.id,
    slug: String(raw.slug || CFG.siteSlug || ""),
    status: raw.status === "published" ? "published" : "draft",
    brand_color: normalizeHex(raw.brand_color || content.brand, "#dc2626"),
    content,
    published_at: raw.published_at || null,
    updated_at: raw.updated_at || null,
  };
}

function defaultsFor(t) {
  const base = {
    template: t.id,
    brand: t.cat === "kuliner" ? "#dc2626" : t.cat === "fashion" ? "#0f172a" : t.cat === "jasa" ? "#2563eb" : "#0d9488",
    nama: "",
    tagline: "",
    kategori: "",
    deskripsi: "",
    heroImg: "",
    heroName: "",
    heroSize: "",
    wa: "",
    ig: "",
    alamat: "",
    jam: "",
    produk: [
      { nama: "", harga: "", ket: "", img: "", imgName: "", imgSize: "" },
      { nama: "", harga: "", ket: "", img: "", imgName: "", imgSize: "" },
      { nama: "", harga: "", ket: "", img: "", imgName: "", imgSize: "" },
    ],
  };

  const samples = {
    kuliner: {
      nama: "Dapur Bu Sari",
      tagline: "Masakan rumahan, rasa yang bikin rindu",
      kategori: "Kuliner rumahan",
      deskripsi:
        "Kami memasak dengan bumbu segar setiap pagi. Cocok untuk makan siang keluarga, arisan, dan pesanan catering kecil. Bisa pesan antar area kota.",
      wa: "081234567890",
      ig: "dapurbusari",
      alamat: "Jl. Melati No. 12, Bekasi",
      jam: "Setiap hari, 08.00 – 21.00",
      produk: [
        { nama: "Nasi Ayam Bakar", harga: "25000", ket: "Ayam kampung, sambal matah" },
        { nama: "Nasi Rendang", harga: "30000", ket: "Dimasak 6 jam, daging empuk" },
        { nama: "Paket Catering 20 pax", harga: "550000", ket: "Nasi, lauk, sayur, buah" },
      ],
    },
    fashion: {
      nama: "Tenun Loka",
      tagline: "Kain tenun tangan, cerita dari penenun lokal",
      kategori: "Koleksi tenun",
      deskripsi:
        "Setiap lembar kain ditenun tangan oleh perajin di Nusa Tenggara. Kami membeli langsung dengan harga adil, lalu menjahitnya menjadi pakaian siap pakai.",
      wa: "081234567891",
      ig: "tenunloka",
      alamat: "Studio di Yogyakarta, kirim seluruh Indonesia",
      jam: "Pengiriman setiap Senin & Kamis",
      produk: [
        { nama: "Kemeja Tenun Linen", harga: "385000", ket: "Ukuran S–XL, motif ikat" },
        { nama: "Selendang Tenun", harga: "245000", ket: "Pewarna alami, 180 × 60 cm" },
        { nama: "Totebag Tenun", harga: "165000", ket: "Lapisan dalam kain katun" },
      ],
    },
    jasa: {
      nama: "Sigap Teknik",
      tagline: "Servis AC dan kulkas, datang di hari yang sama",
      kategori: "Jasa servis",
      deskripsi:
        "Teknisi berpengalaman untuk AC, kulkas, dan mesin cuci. Kami datang ke rumah, cek dulu, lalu beri estimasi biaya sebelum dikerjakan.",
      wa: "081234567892",
      ig: "sigapteknik",
      alamat: "Melayani Jabodetabek",
      jam: "Senin – Sabtu, 08.00 – 18.00",
      produk: [
        { nama: "Cuci AC", harga: "85000", ket: "Bongkar|Cuci lengkap|Cek freon|Garansi 7 hari" },
        { nama: "Servis Kulkas", harga: "150000", ket: "Diagnosa|Perbaikan|Test 24 jam" },
        { nama: "Pasang AC Baru", harga: "350000", ket: "Bracket|Pipa 3m|Vakum" },
      ],
    },
    retail: {
      nama: "Sembako Jaya",
      tagline: "Belanja harian, harga tetangga",
      kategori: "Toko sembako",
      deskripsi:
        "Beras, minyak, gula, dan kebutuhan dapur lengkap. Pesan lewat WhatsApp, kami antar sampai depan rumah tanpa biaya tambahan untuk area sekitar.",
      wa: "081234567893",
      ig: "sembakojaya",
      alamat: "Jl. Pasar Baru No. 8, Depok",
      jam: "Setiap hari, 06.00 – 21.00",
      produk: [
        { nama: "Beras Premium 5 kg", harga: "68000", ket: "Pulen, kemasan baru" },
        { nama: "Minyak Goreng 2 L", harga: "36000", ket: "Kemasan pouch" },
        { nama: "Gula Pasir 1 kg", harga: "17500", ket: "Beli 5 lebih murah" },
      ],
    },
  };

  const sample = samples[t.cat];
  const businessName = String(CFG.businessName || "").trim();
  return {
    ...base,
    ...sample,
    nama: businessName || sample.nama,
    template: t.id,
    variant: !!t.variant,
    brand: t.brand || base.brand,
    produk: sample.produk.map((p) => normalizeProduct(p)),
  };
}

let currentSite = CFG.initialSite ? normalizeSite(CFG.initialSite) : null;
let state = null;
let activeTpl = null;

/* ---------------- HTTP helper ---------------- */
async function apiFetch(url, options = {}) {
  const isFormData = options.body instanceof FormData;
  const headers = {
    Accept: "application/json",
    "X-CSRF-TOKEN": getCsrfToken(),
    "X-Requested-With": "XMLHttpRequest",
    ...(options.headers || {}),
  };
  if (!isFormData && options.body && !headers["Content-Type"]) {
    headers["Content-Type"] = "application/json";
  }

  const res = await fetch(url, {
    credentials: "same-origin",
    ...options,
    headers,
  });

  if (res.status === 401 || res.status === 419) {
    throw new Error("Sesi kamu berakhir. Silakan muat ulang halaman.");
  }

  let data = null;
  try {
    data = await res.json();
  } catch (e) {
    data = null;
  }

  if (!res.ok) {
    let msg = data?.message || "Terjadi kesalahan saat menghubungi server.";
    if (data?.errors && typeof data.errors === "object") {
      const firstKey = Object.keys(data.errors)[0];
      if (firstKey && Array.isArray(data.errors[firstKey]) && data.errors[firstKey][0]) {
        msg = data.errors[firstKey][0];
      }
    }
    throw new Error(msg);
  }

  return data;
}

/* ---------------- kelengkapan situs ---------------- */
const CHECKS = [
  { k: "nama", label: "Nama usaha", sel: '[data-k="nama"]' },
  { k: "wa", label: "Nomor WhatsApp", sel: '[data-k="wa"]' },
  { k: "jam", label: "Jam buka", sel: '[data-k="jam"]' },
  { k: "produk", label: "Minimal satu produk", sel: '[data-p="nama"]' },
];

function checkState(targetState = state) {
  if (!targetState) return CHECKS.map((c) => ({ ...c, done: false }));
  return CHECKS.map((c) => ({
    ...c,
    done:
      c.k === "produk"
        ? Array.isArray(targetState.produk) && targetState.produk.some((p) => String(p.nama || "").trim())
        : !!(targetState[c.k] && String(targetState[c.k]).trim()),
  }));
}

function renderChecklist() {
  const el = $("#checklist");
  if (!el || !state) return;
  const list = checkState();
  const n = list.filter((x) => x.done).length;
  el.innerHTML = `
    <div class="ck-head"><b>Kelengkapan situs</b><span class="${n === list.length ? "is-full" : ""}">${n}/${list.length} siap</span></div>
    <div class="ck-bar"><i style="width:${(n / list.length) * 100}%"></i></div>
    <div class="ck-items">${list
      .map(
        (x) => `<button type="button" class="ck-item ${x.done ? "is-done" : ""}" data-focus="${x.sel}">
          <span>${x.done ? "✓" : "○"}</span>${x.label}</button>`
      )
      .join("")}</div>`;
}

function focusField(sel) {
  let t = $(sel);
  if (!t) return;
  if (t.hidden || getComputedStyle(t).display === "none") {
    const label = t.closest("label") || (t.id && $('label[for="' + t.id + '"]'));
    if (label) {
      label.tabIndex = -1;
      t = label;
    }
  }
  t.scrollIntoView({ behavior: "smooth", block: "center" });
  if (t.focus) t.focus({ preventScroll: true });
  t.classList.add("flash");
  setTimeout(() => t.classList.remove("flash"), 1200);
}

/* ---------------- riwayat versi ---------------- */
let history = [];
let histTimer = null;
let restoring = false;

const snapshot = () => JSON.parse(JSON.stringify(state));

function pushHistory() {
  const cur = snapshot();
  const last = history[history.length - 1];
  if (last && JSON.stringify(last.data) === JSON.stringify(cur)) return;
  history.push({ time: Date.now(), data: cur });
  if (history.length > 10) history.shift();
  renderHistory();
}

function scheduleHistory() {
  if (restoring) return;
  clearTimeout(histTimer);
  histTimer = setTimeout(() => {
    pushHistory();
    histTimer = null;
  }, 900);
}

function renderHistory() {
  const pop = $("#historyPop");
  if (!pop) return;
  const rows = history
    .slice()
    .reverse()
    .map((h, i) => {
      const idx = history.length - 1 - i;
      const jam = new Date(h.time).toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" });
      return `<button type="button" class="history-row" data-hist="${idx}">
        <b>Tersimpan ${jam}</b><small>${idx === history.length - 1 ? "Versi terkini" : "Klik untuk pulihkan"}</small>
      </button>`;
    })
    .join("");
  pop.innerHTML = `<div class="history-head">Riwayat perubahan</div>${rows || '<div class="history-empty">Belum ada riwayat.</div>'}`;
}

function applySnapshot(data, msg) {
  clearTimeout(histTimer);
  histTimer = null;
  restoring = true;
  state = JSON.parse(JSON.stringify(data));
  buildForm(activeTpl);
  updatePreview(true);
  saveNow();
  restoring = false;
  renderChecklist();
  toast(msg || "Versi dipulihkan.");
}

function undo() {
  const pending = !!histTimer;
  clearTimeout(histTimer);
  histTimer = null;
  if (!history.length) return toast("Belum ada riwayat perubahan.");
  if (!pending && history.length === 1) return toast("Ini versi awal. Belum ada yang bisa diurungkan.");
  if (pending) {
    return applySnapshot(history[history.length - 1].data, "Perubahan terakhir diurungkan.");
  }
  history.pop();
  applySnapshot(history[history.length - 1].data, "Perubahan terakhir diurungkan.");
}

/* ---------------- galeri ---------------- */
function renderGallery() {
  const grid = $("#templateGrid");
  grid.innerHTML = TEMPLATES.map((t) => {
    const html = t.render(defaultsFor(t));
    return `<article class="card" data-id="${t.id}" data-cat="${t.cat}">
      <div class="thumb">
        ${t.badge ? `<span class="thumb-badge ${t.badge === "Populer" ? "hot" : ""}">${t.badge}</span>` : ""}
        <iframe title="Pratinjau ${esc(t.name)}" tabindex="-1" srcdoc="${esc(html)}"></iframe>
      </div>
      <div class="card-body">
        <h3>${t.name}</h3>
        <p>${t.blurb}</p>
        <div class="card-meta">
          <span class="tag">${t.tag1}</span><span class="tag">${t.tag2}</span>
          <button class="btn btn-primary btn-sm card-use" data-open="${t.id}">Pakai</button>
        </div>
      </div>
    </article>`;
  }).join("");
}

/* ---------------- form ---------------- */
const groupDefs = (t) => [
  {
    key: "identitas",
    title: "Identitas usaha",
    note: "Wajib",
    fields: [
      { k: "nama", label: "Nama usaha", type: "text", ph: "Contoh: Dapur Bu Sari" },
      { k: "tagline", label: "Tagline", type: "text", ph: "Satu kalimat singkat yang menjual" },
      { k: "kategori", label: "Label kecil di atas judul", type: "text", ph: "Contoh: Kuliner rumahan" },
      { k: "deskripsi", label: "Deskripsi usaha", type: "textarea", ph: "Ceritakan apa yang kamu jual dan kenapa orang harus memilihmu." },
    ],
  },
  {
    key: "visual",
    title: "Foto & warna",
    note: "Tampilan",
    fields: [],
  },
  {
    key: "produk",
    title: (t) => t.productLabel || "Produk",
    note: "Bebas",
    fields: [],
  },
  {
    key: "kontak",
    title: "Kontak & lokasi",
    note: "Wajib",
    fields: [
      { k: "wa", label: "Nomor WhatsApp", type: "text", ph: "0812xxxxxxx" },
      { k: "ig", label: "Instagram", type: "text", ph: "tanpa @" },
      { k: "alamat", label: "Alamat / area layanan", type: "text", ph: "Jl. ... atau nama kota" },
      { k: "jam", label: "Jam buka / info tambahan", type: "text", ph: "Setiap hari, 08.00 – 21.00" },
    ],
  },
];

function buildForm(t) {
  const form = $("#form");
  const groups = groupDefs(t);

  form.innerHTML = groups
    .map((g) => {
      const title = typeof g.title === "function" ? g.title(t) : g.title;

      if (g.key === "produk") {
        return `<section class="group">
          <div class="group-head"><b>${title}</b><span>${g.note}</span></div>
          <div id="items">${state.produk.map((_, i) => prodItem(i)).join("")}</div>
          <button type="button" class="add-item" id="addItem">+ Tambah ${title.toLowerCase()}</button>
        </section>`;
      }

      const fields = g.fields
        .map((f) => {
          const v = state[f.k] || "";
          const input =
            f.type === "textarea"
              ? `<textarea class="input" data-k="${f.k}" placeholder="${f.ph}">${esc(v)}</textarea>`
              : `<input class="input" data-k="${f.k}" value="${esc(v)}" placeholder="${f.ph}">`;
          return `<div class="field"><label>${f.label}</label>${input}${f.hint ? `<div class="hint">${f.hint}</div>` : ""}</div>`;
        })
        .join("");

      const extra =
        g.key === "visual"
          ? `<div class="field">
              <label>Foto utama usaha <span class="optional-tag">opsional</span></label>
              <label class="upload" for="heroUpload">
                <input type="file" id="heroUpload" accept="image/*" hidden>
                <span class="upload-icon">＋</span>
                <span class="upload-copy"><b>Pilih foto dari perangkat</b><small>HP membuka galeri · Komputer membuka folder</small></span>
              </label>
              <div class="upload-preview" id="uploadPreview" ${state.heroImg ? "" : "hidden"}>
                <img id="uploadImg" alt="Foto utama usaha" src="${esc(state.heroImg)}">
                <div class="upload-meta"><b id="uploadName">${esc(state.heroName || "Foto utama")}</b><small id="uploadSize">${esc(state.heroSize || "Siap dipakai")}</small></div>
                <button type="button" class="upload-remove" id="removePhoto" title="Hapus foto">×</button>
              </div>
              <div class="hint">Foto asli lebih dipercaya pembeli. Tanpa foto, template memakai huruf inisial usaha.</div>
            </div>
            <div class="field"><label>Warna utama</label>
              <div class="swatches">${SWATCHES.map((c) => `<button type="button" class="swatch ${state.brand === c ? "is-active" : ""}" data-color="${c}" style="background:${c}" title="${c}"></button>`).join("")}</div>
              <div class="swatch-row" style="margin-top:9px"><input type="color" id="brandPick" value="${normalizeHex(state.brand)}"><span>atau pilih warna sendiri</span></div>
            </div>`
          : "";

      return `<section class="group">
        <div class="group-head"><b>${title}</b><span>${g.note}</span></div>
        ${fields}${extra}
      </section>`;
    })
    .join("");

  $("#addItem").addEventListener("click", () => {
    if (state.produk.length >= 100) return toast("Maksimal 100 item.");
    state.produk.push(normalizeProduct({}));
    $("#items").insertAdjacentHTML("beforeend", prodItem(state.produk.length - 1));
    save();
  });

  bindUpload();
}

function prodItem(i) {
  const p = state.produk[i] || normalizeProduct({});
  return `<div class="item" data-i="${i}">
    <div class="item-head"><b>Item ${i + 1}</b><button type="button" class="item-remove" data-remove="${i}" title="Hapus item">×</button></div>
    <div class="field"><label>Nama</label><input class="input" data-p="nama" data-i="${i}" value="${esc(p.nama)}" placeholder="Nama produk / menu"></div>
    <div class="row2">
      <div class="field"><label>Harga</label><input class="input" data-p="harga" data-i="${i}" value="${esc(p.harga)}" placeholder="25000"></div>
      <div class="field"><label>Keterangan</label><input class="input" data-p="ket" data-i="${i}" value="${esc(p.ket)}" placeholder="Singkat saja"></div>
    </div>
    <div class="field">
      <label>Foto item</label>
      <label class="upload upload-sm" for="prodUp${i}">
        <input type="file" id="prodUp${i}" data-prod-upload="${i}" accept="image/*" hidden>
        <span class="upload-icon">＋</span>
        <span class="upload-copy"><b>Pilih foto item</b><small>Galeri HP · folder komputer</small></span>
      </label>
      <div class="upload-preview" data-prod-preview="${i}" ${p.img ? "" : "hidden"}>
        <img src="${esc(p.img || "")}" alt="Foto ${esc(p.nama || "item")}">
        <div class="upload-meta"><b>${esc(p.imgName || "Foto item")}</b><small>${esc(p.imgSize || "Siap dipakai")}</small></div>
        <button type="button" class="upload-remove" data-prod-rmimg="${i}" title="Hapus foto">×</button>
      </div>
    </div>
  </div>`;
}

/* ---------------- upload & media API ---------------- */
async function uploadImageBlob(blob, originalName) {
  const fd = new FormData();
  const baseName = (originalName || "foto").replace(/\.[^.]+$/, "") || "foto";
  fd.append("image", blob, baseName + ".jpg");
  const res = await apiFetch(ROUTES.mediaStore, {
    method: "POST",
    body: fd,
  });
  if (!res?.url) throw new Error("Server tidak mengembalikan URL foto.");
  return res.url;
}

async function deleteMedia(url) {
  if (!url || !String(url).startsWith("/storage/sites/")) return;
  try {
    await apiFetch(ROUTES.mediaDelete, {
      method: "DELETE",
      body: JSON.stringify({ url }),
    });
  } catch (e) {
    // Best-effort cleanup; tidak memblokir pengguna
  }
}

function bindUpload() {
  const input = $("#heroUpload");
  if (!input) return;

  input.addEventListener("change", async (e) => {
    const file = e.target.files && e.target.files[0];
    if (!file) return;
    if (!file.type.startsWith("image/")) return toast("Pilih file gambar, bukan dokumen.");
    if (file.size > 15 * 1024 * 1024) return toast("Foto terlalu besar. Pilih file di bawah 15 MB.");

    toast("Mengunggah foto...");
    try {
      const { blob } = await compressImage(file);
      const uploadedUrl = await uploadImageBlob(blob, file.name);
      const prevUrl = state.heroImg;

      state.heroImg = uploadedUrl;
      state.heroName = file.name;
      state.heroSize = Math.max(1, Math.round(blob.size / 1024)) + " KB";

      const box = $("#uploadPreview");
      if (box) box.hidden = false;
      const img = $("#uploadImg");
      if (img) img.src = uploadedUrl;
      const nm = $("#uploadName");
      if (nm) nm.textContent = file.name;
      const sz = $("#uploadSize");
      if (sz) sz.textContent = state.heroSize;

      updatePreview();
      const saved = await saveNow();
      if (saved && prevUrl && prevUrl !== uploadedUrl) deleteMedia(prevUrl);
      toast(saved ? "Foto berhasil diunggah." : "Foto terunggah, tetapi gagal menyimpan draf.");
    } catch (err) {
      toast(err.message || "Gagal mengunggah foto. Coba file lain.");
    } finally {
      input.value = "";
    }
  });

  const rm = $("#removePhoto");
  if (rm) {
    rm.addEventListener("click", async () => {
      const oldUrl = state.heroImg;
      state.heroImg = "";
      state.heroName = "";
      state.heroSize = "";
      const box = $("#uploadPreview");
      if (box) box.hidden = true;
      input.value = "";
      updatePreview();
      const saved = await saveNow();
      if (saved && oldUrl) deleteMedia(oldUrl);
      toast("Foto dihapus. Template memakai inisial usaha.");
    });
  }
}

async function handleProdUpload(input) {
  const i = Number(input.dataset.prodUpload);
  const file = input.files && input.files[0];
  if (!file) return;
  if (!file.type.startsWith("image/")) return toast("Pilih file gambar, bukan dokumen.");
  if (file.size > 15 * 1024 * 1024) return toast("Foto terlalu besar. Pilih file di bawah 15 MB.");

  toast("Mengunggah foto item...");
  try {
    const { blob } = await compressImage(file, 720, 0.7);
    const uploadedUrl = await uploadImageBlob(blob, file.name);
    const p = state.produk[i];
    if (!p) return;
    const prevUrl = p.img;

    p.img = uploadedUrl;
    p.imgName = file.name;
    p.imgSize = Math.max(1, Math.round(blob.size / 1024)) + " KB";

    const box = $('[data-prod-preview="' + i + '"]');
    if (box) {
      box.hidden = false;
      const im = box.querySelector("img");
      if (im) im.src = uploadedUrl;
      const nm = box.querySelector(".upload-meta b");
      if (nm) nm.textContent = file.name;
      const sz = box.querySelector(".upload-meta small");
      if (sz) sz.textContent = p.imgSize;
    }

    updatePreview();
    const saved = await saveNow();
    if (saved && prevUrl && prevUrl !== uploadedUrl) deleteMedia(prevUrl);
    toast(saved ? "Foto item berhasil diunggah." : "Foto item terunggah, tetapi gagal menyimpan draf.");
  } catch (err) {
    toast(err.message || "Gagal mengunggah foto item. Coba file lain.");
  } finally {
    input.value = "";
  }
}

async function removeProdImage(i) {
  const p = state.produk[i];
  if (!p) return;
  const oldUrl = p.img;
  p.img = "";
  p.imgName = "";
  p.imgSize = "";
  const box = $('[data-prod-preview="' + i + '"]');
  if (box) box.hidden = true;
  const input = $('[data-prod-upload="' + i + '"]');
  if (input) input.value = "";
  updatePreview();
  const saved = await saveNow();
  if (saved && oldUrl) deleteMedia(oldUrl);
  toast("Foto item dihapus.");
}

function dataUrlToBlob(dataUrl) {
  const parts = dataUrl.split(",");
  const mime = (parts[0].match(/:(.*?);/) || [])[1] || "image/jpeg";
  const bin = atob(parts[1] || "");
  const len = bin.length;
  const u8 = new Uint8Array(len);
  for (let i = 0; i < len; i++) u8[i] = bin.charCodeAt(i);
  return new Blob([u8], { type: mime });
}

function compressImage(file, max = 1280, quality = 0.82) {
  return new Promise((resolve, reject) => {
    const fr = new FileReader();
    fr.onerror = () => reject(new Error("gagal membaca file"));
    fr.onload = () => {
      const img = new Image();
      img.onerror = () => reject(new Error("gambar tidak valid"));
      img.onload = () => {
        let w = img.width;
        let h = img.height;
        if (w > max || h > max) {
          const k = max / Math.max(w, h);
          w = Math.round(w * k);
          h = Math.round(h * k);
        }
        const c = document.createElement("canvas");
        c.width = w;
        c.height = h;
        const ctx = c.getContext("2d");
        ctx.fillStyle = "#ffffff";
        ctx.fillRect(0, 0, w, h);
        ctx.drawImage(img, 0, 0, w, h);
        if (c.toBlob) {
          c.toBlob(
            (blob) => {
              if (blob) resolve({ blob });
              else resolve({ blob: dataUrlToBlob(c.toDataURL("image/jpeg", quality)) });
            },
            "image/jpeg",
            quality
          );
        } else {
          resolve({ blob: dataUrlToBlob(c.toDataURL("image/jpeg", quality)) });
        }
      };
      img.src = fr.result;
    };
    fr.readAsDataURL(file);
  });
}

/* ---------------- preview ---------------- */
let previewTimer = null;
const slugOf = (nama) =>
  String(CFG.siteSlug || "").trim() ||
  String(nama || "")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-|-$/g, "") ||
  "usaha-kamu";

function updatePreview(instant) {
  clearTimeout(previewTimer);
  const run = () => {
    if (!activeTpl || !state) return;
    $("#preview").srcdoc = activeTpl.render(state);
    $("#previewDomain").textContent = slugOf(state.nama);
  };
  instant ? run() : (previewTimer = setTimeout(run, 220));
}

/* ---------------- persistence (Laravel API) ---------------- */
let saveTimer = null;
let saveSeq = 0;
let inFlightSave = null;

function stripUnsafeImg(val) {
  const s = String(val || "");
  if (/^\s*(data:|javascript:|vbscript:)/i.test(s)) return "";
  return s;
}

function buildPayload(targetState = state, targetTpl = activeTpl) {
  const brand = normalizeHex(targetState.brand, "#dc2626");
  const cleanContent = {
    template: targetTpl.id,
    variant: !!targetTpl.variant,
    brand,
    nama: String(targetState.nama ?? ""),
    tagline: String(targetState.tagline ?? ""),
    kategori: String(targetState.kategori ?? ""),
    deskripsi: String(targetState.deskripsi ?? ""),
    heroImg: stripUnsafeImg(targetState.heroImg),
    heroName: String(targetState.heroName ?? ""),
    heroSize: String(targetState.heroSize ?? ""),
    wa: String(targetState.wa ?? ""),
    ig: String(targetState.ig ?? ""),
    alamat: String(targetState.alamat ?? ""),
    jam: String(targetState.jam ?? ""),
    updatedAt: Date.now(),
    produk: (Array.isArray(targetState.produk) ? targetState.produk : []).slice(0, 100).map((p) => ({
      nama: String(p?.nama ?? ""),
      harga: String(p?.harga ?? ""),
      ket: String(p?.ket ?? ""),
      img: stripUnsafeImg(p?.img),
      imgName: String(p?.imgName ?? ""),
      imgSize: String(p?.imgSize ?? ""),
    })),
  };

  return {
    template_id: targetTpl.id,
    brand_color: brand,
    content: cleanContent,
    rendered_html: targetTpl.render({ ...cleanContent, variant: !!targetTpl.variant }),
  };
}

function persistDraft(seq) {
  if (!state || !activeTpl) return Promise.resolve(false);
  const el = $("#edStatus");
  if (el) el.textContent = "Menyimpan...";

  const prev = inFlightSave;
  const activePromise = (async () => {
    if (prev) {
      await prev.catch(() => {});
    }
    if (!state || !activeTpl) return false;

    try {
      state.updatedAt = Date.now();
      const payload = buildPayload(state, activeTpl);
      const res = await apiFetch(ROUTES.siteSave, {
        method: "PUT",
        body: JSON.stringify(payload),
      });

      if (seq === saveSeq) {
        if (res?.site) {
          currentSite = normalizeSite(res.site);
        }
        if (el) {
          el.textContent = "Tersimpan otomatis · " + new Date().toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" });
        }
      }
      return true;
    } catch (err) {
      if (seq === saveSeq && el) {
        el.textContent = "Gagal menyimpan";
      }
      toast(err.message || "Gagal menyimpan draf.");
      return false;
    }
  })().finally(() => {
    if (inFlightSave === activePromise) {
      inFlightSave = null;
    }
  });

  inFlightSave = activePromise;
  return activePromise;
}

function save() {
  if (!state || !activeTpl) return;
  const seq = ++saveSeq;
  const el = $("#edStatus");
  if (el) el.textContent = "Menyimpan...";
  renderChecklist();
  scheduleHistory();
  clearTimeout(saveTimer);
  saveTimer = setTimeout(() => {
    saveTimer = null;
    persistDraft(seq);
  }, 600);
}

async function saveNow() {
  if (!state || !activeTpl) {
    if (inFlightSave) await inFlightSave;
    return false;
  }
  clearTimeout(saveTimer);
  saveTimer = null;
  const seq = ++saveSeq;
  renderChecklist();
  scheduleHistory();
  return persistDraft(seq);
}

async function flushPendingSave() {
  while ((saveTimer && state && activeTpl) || inFlightSave) {
    if (saveTimer && state && activeTpl) {
      await saveNow();
    } else if (inFlightSave) {
      await inFlightSave;
    }
  }
}

async function publishSite(targetState, targetTpl) {
  clearTimeout(saveTimer);
  saveTimer = null;
  const seq = ++saveSeq;
  while (inFlightSave) {
    await inFlightSave;
  }

  const payload = buildPayload(targetState, targetTpl);
  const res = await apiFetch(ROUTES.sitePublish, {
    method: "POST",
    body: JSON.stringify(payload),
  });

  if (seq === saveSeq && res?.site) {
    currentSite = normalizeSite(res.site);
  }
  return res;
}

/* ---------------- confirm popup ---------------- */
let replaceResolver = null;
let prevOverflow = "";
function askConfirm(opts) {
  const modal = $("#confirmModal");
  if (replaceResolver) replaceResolver(false);
  $("#confirmTitle").textContent = opts.title;
  $("#confirmText").innerHTML = opts.text;
  $("#confirmSub").textContent = opts.sub || "";
  $("#confirmOk").textContent = opts.ok || "Ya, ganti";
  modal.hidden = false;
  prevOverflow = document.body.style.overflow;
  document.body.style.overflow = "hidden";
  $("#confirmCancel").focus();
  return new Promise((resolve) => { replaceResolver = resolve; });
}
function askReplace(nama, tplName) {
  return askConfirm({
    title: "Ganti landing page?",
    text: `Kamu sudah punya landing page <b>${esc(nama)}</b>. Memilih template <b>${esc(tplName)}</b> akan mengganti tampilannya.`,
    sub: "Nama, deskripsi, produk, dan foto kamu akan ikut terbawa.",
    ok: "Ya, ganti",
  });
}
function closeReplace(answer) {
  const modal = $("#confirmModal");
  if (modal.hidden) return;
  modal.hidden = true;
  document.body.style.overflow = prevOverflow;
  const done = replaceResolver;
  replaceResolver = null;
  if (done) done(answer);
}

/* ---------------- navigasi editor ---------------- */
let openEditorSeq = 0;

async function openEditor(id, fromGallery = false) {
  const editorSeq = ++openEditorSeq;
  await flushPendingSave();
  if (editorSeq !== openEditorSeq) return;

  const tpl = TEMPLATES.find((t) => t.id === id) || TEMPLATES[0];
  const existing = currentSite;
  const isDifferentTemplate = existing && existing.template_id !== tpl.id;
  let carried = null;

  if (isDifferentTemplate) {
    if (fromGallery) {
      const nm = existing.content?.nama ? existing.content.nama : "halaman lama";
      if (!(await askReplace(nm, tpl.name)) || editorSeq !== openEditorSeq) return;
    }
    carried = existing.content;
  }

  activeTpl = tpl;
  const fresh = defaultsFor(activeTpl);

  if (carried) {
    const keep = ["nama", "tagline", "kategori", "deskripsi", "heroImg", "heroName", "heroSize", "wa", "ig", "alamat", "jam", "brand"];
    const merged = { ...fresh };
    keep.forEach((k) => {
      if (carried[k] !== undefined && String(carried[k]).trim()) merged[k] = carried[k];
    });
    merged.produk = Array.isArray(carried.produk) && carried.produk.length
      ? carried.produk.map((p) => normalizeProduct(p))
      : [normalizeProduct({})];
    merged.template = tpl.id;
    merged.variant = !!tpl.variant;
    merged.brand = normalizeHex(merged.brand, fresh.brand);
    state = merged;
  } else if (existing && existing.template_id === tpl.id) {
    state = normalizeContent(existing.content, activeTpl);
  } else {
    state = { ...fresh, produk: fresh.produk.map((p) => normalizeProduct(p)) };
  }

  clearTimeout(histTimer);
  histTimer = null;
  history = [];
  await showView("editor");
  $("#edTemplateName").textContent = activeTpl.name;
  $("#edStatus").textContent = existing?.status === "published" ? "Sudah terbit" : "Tersimpan otomatis";
  buildForm(activeTpl);
  pushHistory();
  updatePreview(true);
  setStep(2);
  renderChecklist();

  if (carried) {
    await saveNow();
    toast("Isi dan foto tersalin ke template " + tpl.name + ".");
  }
  window.scrollTo({ top: 0, behavior: "instant" });
}

async function backToGallery() {
  if (await showView("gallery")) {
    setStep(1);
  }
}

function setStep(n) {
  $$(".step").forEach((s) => {
    const k = Number(s.dataset.step);
    s.classList.toggle("is-active", k === n);
    s.classList.toggle("is-done", k < n);
  });
}

function toast(msg) {
  const el = $("#toast");
  el.textContent = msg;
  el.classList.add("show");
  clearTimeout(el._t);
  el._t = setTimeout(() => el.classList.remove("show"), 2600);
}

/* ---------------- ekspor ---------------- */
function absolutizeStorageUrls(html) {
  const base = String(CFG.publicBaseUrl || window.location.origin || "").replace(/\/$/, "");
  if (!base) return html;
  return String(html).replace(/(["'(])(\/storage\/)/g, `$1${base}$2`);
}

function downloadHtml(html, nama) {
  const finalHtml = absolutizeStorageUrls(html);
  const blob = new Blob([finalHtml], { type: "text/html;charset=utf-8" });
  const a = document.createElement("a");
  a.href = URL.createObjectURL(blob);
  a.download = ((nama || "website-usaha").toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "") || "website-usaha") + ".html";
  a.click();
  setTimeout(() => URL.revokeObjectURL(a.href), 1000);
}

function exportHtml() {
  if (!activeTpl || !state) return;
  downloadHtml(activeTpl.render(state), state.nama);
  toast("HTML terunduh. File ini bisa langsung diunggah ke hosting mana pun.");
}

/* ---------------- halaman: situs saya & panduan ---------------- */
const VIEWS = ["gallery", "editor", "mysites", "guide"];
let viewSeq = 0;

async function showView(name) {
  const seq = ++viewSeq;
  if (name !== "editor") {
    await flushPendingSave();
  }
  if (seq !== viewSeq) return false;
  VIEWS.forEach((v) => {
    const el = $("#view-" + v);
    if (el) el.hidden = v !== name;
  });
  $$(".topnav-link").forEach((a) => a.classList.toggle("is-active", a.dataset.view === name || (name === "editor" && a.dataset.view === "gallery")));
  $$(".mobile-nav-item").forEach((b) => b.classList.toggle("is-active", b.dataset.view === name || (name === "editor" && b.dataset.view === "gallery")));
  if (name === "mysites") renderMySites();
  window.scrollTo({ top: 0, behavior: "instant" });
  return true;
}

function renderMySites() {
  const site = currentSite;

  $("#siteCount").textContent = (site ? "1" : "0") + "/1";
  $("#siteEmpty").hidden = !!site;
  $("#siteList").hidden = !site;
  if (!site) {
    $("#siteList").innerHTML = "";
    return;
  }

  const d = site.content;
  const t = TEMPLATES.find((x) => x.id === site.template_id) || TEMPLATES[0];
  const stamp = site.updated_at || d.updatedAt;
  const when = stamp
    ? new Date(stamp).toLocaleString("id-ID", { day: "numeric", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" })
    : "baru saja";
  const initial = (d.nama || CFG.businessName || "S").charAt(0).toUpperCase();
  const slug = slugOf(d.nama);
  const isPublished = site.status === "published";
  const publicUrl = CFG.publicSiteUrl || ((CFG.publicBaseUrl || window.location.origin || "https://inamikro.co.id").replace(/\/$/, "") + "/" + slug);
  const baseDisplay = String(CFG.publicBaseUrl || window.location.origin || "https://inamikro.co.id").replace(/^https?:\/\//, "").replace(/\/$/, "");

  $("#siteList").innerHTML = `<article class="site-card" data-id="${esc(t.id)}">
    <div class="site-thumb" style="background:linear-gradient(145deg,${esc(site.brand_color || t.brand || "#dc2626")},#0f172a)"><span>${esc(initial)}</span></div>
    <div class="site-info">
      <div class="site-title"><b>${esc(d.nama || "Belum diberi nama")}</b><span class="site-status ${isPublished ? "is-published" : ""}">${isPublished ? "Terbit" : "Draf"}</span></div>
      <p>${esc(t.name)} · Diubah ${esc(when)}</p>
      <div class="site-url">${esc(baseDisplay)}/<b>${esc(slug)}</b><small>${isPublished ? "Sudah tayang dan dapat diakses publik" : "Belum dipublikasikan"}</small></div>
      <div class="site-actions">
        <button class="btn btn-primary btn-sm" type="button" data-act="edit" data-id="${esc(t.id)}">Edit landing page</button>
        ${isPublished ? `<a class="btn btn-outline btn-sm" href="${esc(publicUrl)}" target="_blank" rel="noopener">Lihat situs</a>` : ""}
        <button class="btn btn-outline btn-sm" type="button" data-act="publish" data-id="${esc(t.id)}">Publikasikan</button>
        <button class="btn btn-ghost btn-sm" type="button" data-act="download" data-id="${esc(t.id)}">Unduh HTML</button>
        <button class="btn btn-ghost btn-sm" type="button" data-act="delete" data-id="${esc(t.id)}">Hapus</button>
      </div>
    </div>
  </article>`;
}

/* ---------------- event ---------------- */
document.addEventListener("DOMContentLoaded", () => {
  renderGallery();

  $("#templateGrid").addEventListener("click", (e) => {
    const b = e.target.closest("[data-open]");
    if (b) return openEditor(b.dataset.open, true);
    const card = e.target.closest(".card");
    if (card && !e.target.closest("iframe")) openEditor(card.dataset.id, true);
  });

  $("#filters").addEventListener("click", (e) => {
    const c = e.target.closest(".chip");
    if (!c) return;
    $$(".chip").forEach((x) => x.classList.toggle("is-active", x === c));
    const cat = c.dataset.cat;
    $$(".card").forEach((card) => (card.style.display = cat === "all" || card.dataset.cat === cat ? "" : "none"));
  });

  $("#btnBack").addEventListener("click", backToGallery);
  $("#brandHome").addEventListener("click", async (e) => {
    e.preventDefault();
    if (await showView("gallery")) setStep(1);
  });

  document.addEventListener("click", async (e) => {
    const nav = e.target.closest("[data-view]");
    if (nav) {
      e.preventDefault();
      if ((await showView(nav.dataset.view)) && nav.dataset.view === "gallery") setStep(1);
      return;
    }
    const go = e.target.closest("[data-go]");
    if (go) {
      if (go.dataset.go === "guide") {
        toast("Fitur segera hadir.");
        return;
      }
      if ((await showView(go.dataset.go)) && go.dataset.go === "gallery") setStep(1);
    }
  });

  $("#btnConnectSite")?.addEventListener("click", () => toast("Fitur segera hadir."));
  $("#guideAsk")?.addEventListener("click", () => toast("Fitur segera hadir."));

  $("#siteList").addEventListener("click", async (e) => {
    const b = e.target.closest("[data-act]");
    if (!b) return;
    const act = b.dataset.act;
    const site = currentSite;
    if (!site) return toast("Draf tidak ditemukan.");

    const t = TEMPLATES.find((x) => x.id === site.template_id) || TEMPLATES[0];

    if (act === "edit") return openEditor(site.template_id, false);

    if (act === "publish") {
      const missing = checkState(site.content).filter((x) => !x.done);
      if (missing.length) {
        toast("Lengkapi dulu: " + missing.map((x) => x.label).join(", "));
        await openEditor(site.template_id, false);
        focusField(missing[0].sel);
        return;
      }

      b.disabled = true;
      try {
        const res = await publishSite(site.content, t);
        renderMySites();
        toast("Situs berhasil dipublikasikan ke " + (res?.public_url || CFG.publicSiteUrl || ""));
      } catch (err) {
        toast(err.message || "Gagal mempublikasikan situs.");
      } finally {
        b.disabled = false;
      }
      return;
    }

    if (act === "download") {
      downloadHtml(t.render(site.content), site.content.nama);
      return toast("HTML terunduh.");
    }

    if (act === "delete") {
      const nama = site.content?.nama ? site.content.nama : "halaman ini";
      const ok = await askConfirm({
        title: "Hapus landing page?",
        text: `Landing page <b>${esc(nama)}</b> dan foto yang terunggah akan dihapus dari akunmu.`,
        sub: "Tindakan ini tidak bisa dibatalkan.",
        ok: "Ya, hapus",
      });
      if (!ok) return;

      b.disabled = true;
      clearTimeout(saveTimer);
      saveTimer = null;
      ++saveSeq;
      while (inFlightSave) {
        await inFlightSave;
      }
      try {
        await apiFetch(ROUTES.siteDelete, { method: "DELETE" });
        clearTimeout(histTimer);
        histTimer = null;
        currentSite = null;
        state = null;
        activeTpl = null;
        history = [];
        renderMySites();
        toast("Landing page dihapus.");
      } catch (err) {
        b.disabled = false;
        toast(err.message || "Gagal menghapus landing page.");
      }
    }
  });

  $("#deviceToggle").addEventListener("click", (e) => {
    const b = e.target.closest(".seg-btn");
    if (!b) return;
    $$(".seg-btn").forEach((x) => x.classList.toggle("is-active", x === b));
    $("#previewBox").className = "preview device-" + b.dataset.device;
  });

  $("#btnExport").addEventListener("click", exportHtml);

  $("#btnPublish").addEventListener("click", async () => {
    if (!state || !activeTpl) return;
    const missing = checkState().filter((x) => !x.done);
    if (missing.length) {
      toast("Lengkapi dulu: " + missing.map((x) => x.label).join(", "));
      focusField(missing[0].sel);
      return;
    }

    const btn = $("#btnPublish");
    btn.disabled = true;
    try {
      const res = await publishSite(state, activeTpl);
      setStep(4);
      const el = $("#edStatus");
      if (el) el.textContent = "Dipublikasikan · " + new Date().toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" });
      toast("Situs berhasil dipublikasikan ke " + (res?.public_url || CFG.publicSiteUrl || ""));
    } catch (err) {
      toast(err.message || "Gagal mempublikasikan situs.");
    } finally {
      btn.disabled = false;
    }
  });

  $("#btnReset").addEventListener("click", async () => {
    if (!activeTpl) return;
    state = defaultsFor(activeTpl);
    buildForm(activeTpl);
    updatePreview(true);
    await saveNow();
    toast("Konten dikembalikan ke contoh awal.");
  });

  $("#form").addEventListener("input", (e) => {
    const el = e.target;
    if (!state) return;
    if (el.dataset.k) {
      state[el.dataset.k] = el.value;
    } else if (el.dataset.p) {
      state.produk[Number(el.dataset.i)][el.dataset.p] = el.value;
    } else return;
    updatePreview();
    save();
  });

  $("#form").addEventListener("click", (e) => {
    const rmItem = e.target.closest("[data-remove]");
    if (rmItem) {
      if (state.produk.length <= 1) return toast("Minimal satu item.");
      state.produk.splice(Number(rmItem.dataset.remove), 1);
      $("#form").innerHTML = "";
      buildForm(activeTpl);
      updatePreview();
      save();
      return;
    }
    const rmImg = e.target.closest("[data-prod-rmimg]");
    if (rmImg) return removeProdImage(Number(rmImg.dataset.prodRmimg));
    const sw = e.target.closest("[data-color]");
    if (sw) {
      state.brand = sw.dataset.color;
      $$(".swatch").forEach((x) => x.classList.toggle("is-active", x === sw));
      $("#brandPick").value = state.brand;
      updatePreview();
      save();
    }
  });

  $("#form").addEventListener("change", (e) => {
    if (e.target.matches("[data-prod-upload]")) return handleProdUpload(e.target);
    if (e.target.id === "brandPick") {
      state.brand = e.target.value;
      $$(".swatch").forEach((x) => x.classList.remove("is-active"));
      updatePreview();
      save();
    }
  });

  $("#checklist").addEventListener("click", (e) => {
    const b = e.target.closest("[data-focus]");
    if (b) focusField(b.dataset.focus);
  });

  $("#btnUndo").addEventListener("click", undo);
  $("#btnHistory").addEventListener("click", (e) => {
    e.stopPropagation();
    const p = $("#historyPop");
    p.hidden = !p.hidden;
    if (!p.hidden) renderHistory();
  });
  $("#historyPop").addEventListener("click", (e) => {
    const b = e.target.closest("[data-hist]");
    if (!b) return;
    const idx = Number(b.dataset.hist);
    const h = history[idx];
    if (!h) return;
    history = history.slice(0, idx + 1);
    const jam = new Date(h.time).toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" });
    applySnapshot(h.data, "Versi " + jam + " dipulihkan.");
    $("#historyPop").hidden = true;
  });
  document.addEventListener("click", (e) => {
    if (!e.target.closest(".history-wrap")) $("#historyPop").hidden = true;
  });

  $("#confirmCancel").addEventListener("click", () => closeReplace(false));
  $("#confirmOk").addEventListener("click", () => closeReplace(true));
  $("#confirmModal").addEventListener("click", (e) => {
    if (e.target.id === "confirmModal") closeReplace(false);
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && !$("#confirmModal").hidden) closeReplace(false);
  });
});

window.esc = esc;
