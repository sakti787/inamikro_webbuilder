/* ============================================================
   Panel Pendamping — INAweb Studio (terhubung ke API Laravel)
   ============================================================ */
(function () {
  "use strict";

  const CFG = globalThis.INAWEB_ADMIN_CONFIG || {};
  const ROUTES = CFG.routes || {
    bootstrap: "/api/admin/bootstrap",
    logs: "/api/admin/logs",
    umkmBase: "/api/admin/umkm",
    templatesBase: "/api/admin/templates",
    autoApprove: "/api/admin/settings/auto-approve",
    contact: "/api/admin/settings/contact",
    logout: "/logout",
  };

  const LOG = globalThis.INAWebLog;
  const SET = globalThis.INAWebSettings;
  const $ = (s, r) => (r || document).querySelector(s);
  const $$ = (s, r) => Array.prototype.slice.call((r || document).querySelectorAll(s));
  const h = (s = "") =>
    String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

  const getCsrfToken = () =>
    CFG.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

  async function apiFetch(url, options = {}) {
    const headers = {
      Accept: "application/json",
      "X-CSRF-TOKEN": getCsrfToken(),
      "X-Requested-With": "XMLHttpRequest",
      ...(options.headers || {}),
    };
    if (options.body && !headers["Content-Type"]) {
      headers["Content-Type"] = "application/json";
    }

    const res = await fetch(url, {
      credentials: "same-origin",
      ...options,
      headers,
    });

    if (res.status === 401 || res.status === 419) {
      throw new Error("Sesi Anda berakhir. Silakan muat ulang halaman.");
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

  /* ---------------- state ---------------- */
  const DB = {
    umkm: [],
    tplActive: {},
    settings: {
      auto_approve: false,
      admin_name: "Tim INAmikro",
      admin_whatsapp: "",
    },
    logs: [],
  };

  let currentView = "dashboard";
  let detailId = null;
  let logPage = 1;
  let logPageData = null;
  let logFetchSeq = 0;

  const getTemplates = () => {
    if (Array.isArray(globalThis.TEMPLATES)) return globalThis.TEMPLATES;
    if (typeof TEMPLATES !== "undefined" && Array.isArray(TEMPLATES)) return TEMPLATES;
    return [];
  };

  function syncTemplates(list) {
    const map = {};
    getTemplates().forEach((t) => {
      map[t.id] = true;
    });
    if (Array.isArray(list)) {
      list.forEach((item) => {
        if (item && item.template_id) {
          map[String(item.template_id)] = !!item.is_active;
        }
      });
    }
    DB.tplActive = map;
  }

  function applyBootstrap(raw) {
    const payload = raw && typeof raw === "object" ? raw : {};
    DB.umkm = Array.isArray(payload.umkm) ? payload.umkm : [];
    syncTemplates(payload.templates);
    const s = payload.settings && typeof payload.settings === "object" ? payload.settings : {};
    DB.settings = {
      auto_approve: !!s.auto_approve,
      admin_name: String(s.admin_name || "Tim INAmikro"),
      admin_whatsapp: String(s.admin_whatsapp || ""),
    };
    DB.logs = Array.isArray(payload.logs) ? payload.logs : [];
    logPageData = null;
  }

  function upsertUmkm(item) {
    if (!item || item.id == null) return;
    const idx = DB.umkm.findIndex((x) => String(x.id) === String(item.id));
    if (idx >= 0) {
      DB.umkm[idx] = item;
    } else {
      DB.umkm.push(item);
    }
  }

  applyBootstrap(CFG.initialData);

  /* ---------------- util ---------------- */
  const TEMPLATE_BY_ID = (id) => {
    const list = getTemplates();
    return list.find((t) => t.id === id) || list[0] || {
      id: id || "warung",
      name: id || "Template",
      cat: "kuliner",
      blurb: "",
      tag1: "",
      tag2: "",
      render: () => "",
    };
  };
  const count = (status) => DB.umkm.filter((u) => u.status === status).length;
  function complete(u) {
    if (u && typeof u.completeness === "number") return u.completeness;
    const s = u?.state || {};
    return ["nama", "wa", "jam", "produk"].filter((k) => {
      if (k === "produk") return (s.produk || []).some((p) => p && p.nama);
      return !!String(s[k] || "").trim();
    }).length;
  }
  function displayName(u) {
    return (u?.state && u.state.nama) || u?.business_name || u?.name || "UMKM";
  }
  function toast(msg) {
    const el = $("#toast");
    el.textContent = msg;
    el.classList.add("show");
    clearTimeout(toast._t);
    toast._t = setTimeout(() => el.classList.remove("show"), 3200);
  }
  function badge(status) {
    const cls = status === "Tayang" ? "live" : status === "Menunggu" ? "wait" : "draft";
    return `<span class="badge ${cls}">${h(status)}</span>`;
  }
  function initial(name) {
    return h(String(name || "?").charAt(0).toUpperCase());
  }

  /* ---------------- navigasi ---------------- */
  function go(view) {
    currentView = view;
    $$(".view").forEach((v) => (v.hidden = v.id !== "view-" + view));
    $$(".side-link,.mobile-nav-item").forEach((b) => b.classList.toggle("is-active", b.dataset.view === view));
    window.scrollTo(0, 0);
    render();
    if (view === "log") {
      loadLogsPage(logPage);
    }
  }
  document.addEventListener("click", (e) => {
    const nav = e.target.closest("[data-view]");
    if (nav) {
      e.preventDefault();
      go(nav.dataset.view);
    }
  });

  /* ---------------- render: beranda ---------------- */
  function renderDashboard() {
    const wait = count("Menunggu");
    $("#statCards").innerHTML = [
      { k: "Total UMKM", v: DB.umkm.length, s: "akun terdaftar", cls: "" },
      { k: "Menunggu tayang", v: wait, s: "perlu diperiksa", cls: "hot" },
      { k: "Sudah tayang", v: count("Tayang"), s: "aktif secara publik", cls: "ok" },
      { k: "Masih draf", v: count("Draf"), s: "belum publish", cls: "warn" },
    ].map((x) => `<div class="stat ${x.cls}"><div class="k">${x.k}</div><div class="v">${x.v}</div><div class="s">${x.s}</div></div>`).join("");

    const queue = DB.umkm.filter((u) => u.status === "Menunggu").slice(0, 3);
    $("#actionList").innerHTML = queue.length ? queue.map((u) => `
      <div class="action-row">
        <div class="action-ico">${initial(displayName(u))}</div>
        <div class="action-body"><b>${h(displayName(u))}</b><span>Minta tayang · ${h(u.changed)} · ${complete(u)}/4 lengkap</span></div>
        <button class="btn btn-outline btn-sm" data-detail="${h(u.id)}" type="button">Periksa</button>
      </div>`).join("")
      : `<div class="pad muted" style="font-size:13px">Semua antrean sudah beres. Kerjaan rapi 👌</div>`;
  }

  /* ---------------- render: daftar UMKM ---------------- */
  let umkmSearch = "", umkmStatus = "semua";
  function renderUmkm() {
    const q = umkmSearch.toLowerCase();
    const rows = DB.umkm.filter((u) => {
      const st = umkmStatus === "semua" || u.status === umkmStatus;
      const tx = !q || (displayName(u) + " " + (u.email || "")).toLowerCase().indexOf(q) >= 0;
      return st && tx;
    });
    $("#umkmRows").innerHTML = rows.length ? rows.map((u) => {
      const tpl = TEMPLATE_BY_ID(u.state?.template);
      const c = complete(u);
      const nama = displayName(u);
      return `<tr>
        <td><div class="cell-usaha"><div class="cell-logo">${initial(nama)}</div><div><b>${h(nama)}</b><small>${h(u.email)}</small></div></div></td>
        <td>${badge(u.status)}</td>
        <td><span class="bar"><i style="width:${c * 25}%"></i></span> <small class="muted">${c}/4</small></td>
        <td><span class="muted" style="font-size:12px">${u.has_site ? h(tpl.name) : "Belum pilih template"}</span></td>
        <td class="muted" style="font-size:12px">${h(u.changed)}</td>
        <td><button class="linklike" data-detail="${h(u.id)}" type="button">Lihat →</button></td>
      </tr>`;
    }).join("") : `<tr><td colspan="6" class="pad muted" style="text-align:center">Tidak ada UMKM yang cocok.</td></tr>`;
  }

  /* ---------------- render: detail ---------------- */
  function renderDetail() {
    if (detailId == null) return;
    const u = DB.umkm.find((x) => String(x.id) === String(detailId));
    if (!u) return;
    const s = u.state || {};
    const tpl = TEMPLATE_BY_ID(s.template);
    const nama = displayName(u);
    const noSite = !u.has_site;

    $("#detailName").textContent = nama;
    $("#detailMeta").textContent = (u.email || "") + " · " + u.status;
    $("#detailTpl").textContent = noSite ? "Belum ada draf situs" : "Template: " + tpl.name;

    const safePublicUrl = typeof u.public_url === "string" && /^https?:\/\//i.test(u.public_url) ? u.public_url : "";
    const publicLink = $("#detailPublicLink");
    if (publicLink) {
      if (u.status_key === "published" && safePublicUrl) {
        publicLink.hidden = false;
        publicLink.href = safePublicUrl;
      } else {
        publicLink.hidden = true;
        publicLink.href = "#";
      }
    }

    const infoRows = [
      ["Status", badge(u.status)],
      ...(u.status_key === "published" && safePublicUrl
        ? [["Alamat situs", `<a class="info-link" href="${h(safePublicUrl)}" target="_blank" rel="noopener">${h(safePublicUrl)} ↗</a>`]]
        : []),
      ["WhatsApp", h(s.wa) || "—"],
      ["Alamat", h(s.alamat) || "—"],
      ["Jam buka", h(s.jam) || "Belum diisi"],
      ["Instagram", s.ig ? "@" + h(String(s.ig).replace("@", "")) : "—"],
      ["Kelengkapan", complete(u) + "/4"],
      ["Diubah", h(u.changed)],
      ...(u.revision_note ? [["Catatan revisi", h(u.revision_note)]] : []),
    ];
    $("#detailInfo").innerHTML = infoRows.map((r) => `<div class="info-row"><span>${r[0]}</span><b>${r[1]}</b></div>`).join("");

    $("#detailNoSiteNote").hidden = !noSite;
    $("#detailNote").value = u.note || "";
    $("#detailNote").disabled = noSite;
    $("#btnSaveNote").disabled = noSite;
    $("#btnApprove").disabled = noSite;
    $("#btnRevise").disabled = noSite;

    $("#detailHistory").innerHTML = (u.history || []).map((x) => `
      <div class="tl-item"><b>${h(x.title)}</b><small>${h(x.t || (x.at ? LOG.timeAgo(x.at) : ""))}</small>${x.text ? `<p>${h(x.text)}</p>` : ""}</div>`).join("") || `<p class="muted" style="font-size:12px">Belum ada riwayat.</p>`;

    const iframe = $("#detailPreview");
    if (noSite) {
      iframe.srcdoc = "<!doctype html><html><body style='margin:0;padding:28px;font-family:sans-serif;color:#475569;background:#f8fafc'><p style='margin:0;font-size:14px;line-height:1.6'>UMKM ini belum membuat draf situs di Studio.</p></body></html>";
    } else if (u.rendered_html && String(u.rendered_html).trim()) {
      iframe.srcdoc = u.rendered_html;
    } else {
      try {
        iframe.srcdoc = tpl.render(s);
      } catch (e) {
        iframe.srcdoc = "<p style='padding:20px;font:13px sans-serif'>Pratinjau tidak tersedia.</p>";
      }
    }
  }

  /* ---------------- render: antrean ---------------- */
  function renderQueue() {
    const toggle = $("#autoApprove");
    if (toggle) toggle.checked = !!DB.settings.auto_approve;
    const note = $("#autoNote");
    if (note) note.textContent = DB.settings.auto_approve
      ? "Aktif — situs dengan 4/4 syarat langsung tayang."
      : "Nonaktif — setiap situs ditinjau manual.";

    const list = DB.umkm.filter((u) => u.status === "Menunggu");
    $("#queueList").innerHTML = list.length ? list.map((u) => {
      const c = complete(u), missing = 4 - c;
      const nama = displayName(u);
      return `<div class="qcard ${missing ? "flag" : ""}">
        <div class="q-logo">${initial(nama)}</div>
        <div class="q-body">
          <b>${h(nama)}</b>
          <p>${h(u.state?.tagline || "Belum ada tagline")}</p>
          <div class="q-meta">
            <span>${h(TEMPLATE_BY_ID(u.state?.template).name)}</span>
            <span>${h(u.changed)}</span>
            <span class="${missing ? "warn" : ""}">${missing ? missing + " bagian kosong" : "Lengkap 4/4"}</span>
          </div>
        </div>
        <div class="q-actions">
          <button class="btn btn-ghost btn-sm" data-detail="${h(u.id)}" type="button">Pratinjau</button>
          <button class="btn btn-outline btn-sm" data-revise="${h(u.id)}" type="button">Minta revisi</button>
          <button class="btn btn-primary btn-sm" data-approve="${h(u.id)}" type="button">Setujui tayang</button>
        </div>
      </div>`;
    }).join("") : `<div class="q-empty"><b>Antrean kosong 🎉</b>Semua situs sudah diperiksa.</div>`;
  }

  /* ---------------- render: template ---------------- */
  function renderTplAdmin() {
    $("#tplAdminGrid").innerHTML = getTemplates().map((t) => {
      const active = !!DB.tplActive[t.id];
      return `
      <div class="tpl-card">
        <div class="tpl-top"><div><h3>${h(t.name)}</h3><small>${h(t.cat)}</small></div>
          <label class="toggle"><input type="checkbox" data-tpl="${h(t.id)}" ${active ? "checked" : ""}><span class="track"></span></label>
        </div>
        <p>${h(t.blurb)}</p>
        <div class="tpl-foot"><small>${active ? "Aktif dipakai UMKM" : "Nonaktif"}</small><small>${h(t.tag1)} · ${h(t.tag2)}</small></div>
      </div>`;
    }).join("");
  }

  /* ---------------- render: statistik ---------------- */
  function renderStats() {
    $("#statBody").innerHTML = [
      { k: "Total UMKM", v: DB.umkm.length, s: "akun terdaftar", cls: "" },
      { k: "Situs tayang", v: count("Tayang"), s: "publik", cls: "ok" },
      { k: "Antrean", v: count("Menunggu"), s: "menunggu verifikasi", cls: "hot" },
      { k: "Template aktif", v: Object.values(DB.tplActive).filter(Boolean).length, s: "dari 8 template", cls: "warn" },
    ].map((x) => `<div class="stat ${x.cls}"><div class="k">${x.k}</div><div class="v">${x.v}</div><div class="s">${x.s}</div></div>`).join("");

    const cats = {};
    DB.umkm.filter((u) => u.status === "Tayang").forEach((u) => {
      const c = TEMPLATE_BY_ID(u.state?.template).cat;
      cats[c] = (cats[c] || 0) + 1;
    });
    const keys = Object.keys(cats);
    const total = keys.reduce((a, k) => a + cats[k], 0);
    const palette = { kuliner: "#dc2626", fashion: "#7c3aed", jasa: "#2563eb", retail: "#0d9488" };
    const cap = (k) => String(k).charAt(0).toUpperCase() + String(k).slice(1);
    if (!keys.length) {
      $("#statBars").innerHTML = `<p class="muted pad" style="font-size:13px">Belum ada situs tayang.</p>`;
      return;
    }
    let acc = 0;
    const stops = keys.map((k) => {
      const from = (acc / total) * 100;
      acc += cats[k];
      const to = (acc / total) * 100;
      return `${palette[k] || "#94a3b8"} ${from.toFixed(2)}% ${to.toFixed(2)}%`;
    }).join(",");
    $("#statBars").innerHTML = `
      <div class="chart-wrap">
        <div class="donut" style="background:conic-gradient(${stops})" role="img" aria-label="Diagram kategori situs tayang">
          <div class="donut-core"><b>${total}</b><span>situs tayang</span></div>
        </div>
        <div class="chart-legend">${keys.map((k) => `
          <div class="legend-row">
            <span class="legend-dot" style="background:${palette[k] || "#94a3b8"}"></span>
            <span class="legend-name">${h(cap(k))}</span>
            <span class="legend-val">${cats[k]}</span>
            <span class="legend-pct">${Math.round((cats[k] / total) * 100)}%</span>
          </div>`).join("")}
        </div>
      </div>`;
  }

  /* ---------------- render: log aktivitas ---------------- */
  const LOG_ICON = {
    approve: "✓", auto: "✓", revise: "↩", note: "✎",
    template: "▧", settings: "⚙", general: "•",
  };
  function logRow(e, full) {
    const icon = Object.hasOwn(LOG_ICON, e.kind) ? LOG_ICON[e.kind] : LOG_ICON.general;
    return `<div class="log-row">
      <span class="log-ico k-${h(e.kind || "general")}">${icon}</span>
      <div class="log-body"><b>${h(e.title)}</b><span>${h(e.who || "Sistem")} · ${h(LOG.timeAgo(e.at))}</span>${full && e.text ? `<p>${h(e.text)}</p>` : ""}</div>
    </div>`;
  }
  function renderLogPager(p) {
    let start = Math.max(1, p.page - 3);
    const end = Math.min(p.pages, start + 6);
    start = Math.max(1, end - 6);
    const nums = [];
    for (let i = start; i <= end; i++) {
      nums.push(`<button class="pager-num ${i === p.page ? "is-active" : ""}" data-log-page="${i}" type="button" ${i === p.page ? 'aria-current="page"' : ""}>${i}</button>`);
    }
    $("#logPager").innerHTML = `
      <button class="pager-btn" data-log-page="${p.page - 1}" type="button" ${p.page <= 1 ? "disabled" : ""}>‹ Sebelumnya</button>
      <div class="pager-nums">${nums.join("")}</div>
      <button class="pager-btn" data-log-page="${p.page + 1}" type="button" ${p.page >= p.pages ? "disabled" : ""}>Berikutnya ›</button>`;
  }
  function renderLogs() {
    const recent = DB.logs.slice(0, 5);
    $("#dashboardLogs").innerHTML = recent.length
      ? recent.map((e) => logRow(e, false)).join("")
      : `<p class="pad muted" style="font-size:13px">Belum ada aktivitas tercatat.</p>`;

    const p = logPageData || LOG.pageLogs(DB.logs, logPage);
    logPage = p.page;
    $("#logCount").textContent = p.total + " entri";
    $("#logList").innerHTML = p.items.length
      ? p.items.map((e) => logRow(e, true)).join("")
      : `<p class="pad muted" style="font-size:13px">Belum ada aktivitas tercatat.</p>`;
    renderLogPager(p);
  }

  async function loadLogsPage(page) {
    const targetPage = Math.max(1, Number(page) || 1);
    const seq = ++logFetchSeq;
    try {
      const res = await apiFetch(`${ROUTES.logs}?page=${encodeURIComponent(targetPage)}`);
      if (seq !== logFetchSeq) return;
      logPageData = {
        items: Array.isArray(res.items) ? res.items : [],
        page: Number(res.page) || targetPage,
        pages: Math.max(1, Number(res.pages) || 1),
        total: Number(res.total) || 0,
      };
      logPage = logPageData.page;
      renderLogs();
    } catch (err) {
      if (seq === logFetchSeq) {
        toast(err.message || "Gagal memuat log aktivitas.");
      }
    }
  }

  /* ---------------- render: pengaturan ---------------- */
  const ADMIN_PESAN = "Halo kak, butuh bantuan INAmikro.";
  function renderSettings() {
    const a = DB.settings || {};
    const namaInput = $("#adminNama");
    const waInput = $("#adminWa");
    if (namaInput && document.activeElement !== namaInput) {
      namaInput.value = a.admin_name || "";
    }
    if (waInput && document.activeElement !== waInput) {
      waInput.value = a.admin_whatsapp || "";
    }
    $("#adminPreview").textContent = SET.waPreview(waInput ? waInput.value : a.admin_whatsapp, ADMIN_PESAN);
  }

  /* ---------------- render all ---------------- */
  function render() {
    $("#navQueue").textContent = count("Menunggu");
    renderDashboard();
    renderUmkm();
    if (currentView === "detail" && detailId != null) {
      renderDetail();
    }
    renderQueue();
    renderTplAdmin();
    renderStats();
    renderLogs();
    renderSettings();
  }

  /* ---------------- aksi API ---------------- */
  function openDetail(id) {
    detailId = id;
    go("detail");
  }

  function applyMutationLogs(logs) {
    if (Array.isArray(logs)) {
      DB.logs = logs;
    }
    logPageData = null;
    if (currentView === "log") {
      loadLogsPage(logPage);
    }
  }

  async function approve(id, triggerBtn) {
    const u = DB.umkm.find((x) => String(x.id) === String(id));
    if (!u) return;
    if (!u.has_site) {
      toast("UMKM ini belum membuat draf situs di Studio.");
      return;
    }

    const buttons = triggerBtn ? [triggerBtn] : [];
    if (currentView === "detail" && String(detailId) === String(id)) {
      buttons.push($("#btnApprove"), $("#btnRevise"), $("#btnSaveNote"));
    }
    buttons.forEach((b) => { if (b) b.disabled = true; });

    try {
      const res = await apiFetch(`${ROUTES.umkmBase}/${encodeURIComponent(id)}/approve`, {
        method: "POST",
      });
      if (res?.umkm) upsertUmkm(res.umkm);
      applyMutationLogs(res?.logs);
      buttons.forEach((b) => { if (b) b.disabled = false; });
      render();
      toast(displayName(res?.umkm || u) + " disetujui tayang ✓");
    } catch (err) {
      toast(err.message || "Gagal menyetujui situs.");
    } finally {
      buttons.forEach((b) => { if (b) b.disabled = false; });
    }
  }

  /* modal alasan revisi */
  let reasonAction = null;
  function openReason(id) {
    const u = DB.umkm.find((x) => String(x.id) === String(id));
    if (u && !u.has_site) {
      toast("UMKM ini belum membuat draf situs di Studio.");
      return;
    }
    reasonAction = { id };
    $("#reasonModal").hidden = false;
    $("#reasonTitle").textContent = "Minta revisi";
    $("#reasonFor").textContent = "Tulis bagian yang perlu diperbaiki UMKM.";
    $("#reasonText").value = "";
    $("#reasonText").placeholder = "Contoh: jam buka belum diisi, tolong lengkapi ya.";
    $("#reasonOk").textContent = "Kirim revisi";
    setTimeout(() => $("#reasonText").focus(), 30);
  }
  function closeReason() {
    $("#reasonModal").hidden = true;
    reasonAction = null;
  }
  $("#reasonCancel").addEventListener("click", closeReason);
  $("#reasonModal").addEventListener("click", (e) => { if (e.target.id === "reasonModal") closeReason(); });
  $("#reasonOk").addEventListener("click", async () => {
    const txt = $("#reasonText").value.trim();
    if (!txt) {
      toast("Isi alasannya dulu ya");
      $("#reasonText").focus();
      return;
    }
    if (!reasonAction) return;
    const targetId = reasonAction.id;
    const okBtn = $("#reasonOk");
    const cancelBtn = $("#reasonCancel");
    okBtn.disabled = true;
    cancelBtn.disabled = true;

    try {
      const res = await apiFetch(`${ROUTES.umkmBase}/${encodeURIComponent(targetId)}/revise`, {
        method: "POST",
        body: JSON.stringify({ reason: txt }),
      });
      if (res?.umkm) upsertUmkm(res.umkm);
      applyMutationLogs(res?.logs);
      closeReason();
      render();
      toast("Catatan revisi dikirim");
    } catch (err) {
      toast(err.message || "Gagal mengirim catatan revisi.");
    } finally {
      okBtn.disabled = false;
      cancelBtn.disabled = false;
    }
  });

  /* klik aksi dalam tabel / antrean / pager */
  document.addEventListener("click", (e) => {
    const d = e.target.closest("[data-detail]");
    if (d) { openDetail(d.dataset.detail); return; }
    const a = e.target.closest("[data-approve]");
    if (a) { approve(a.dataset.approve, a); return; }
    const r = e.target.closest("[data-revise]");
    if (r) { openReason(r.dataset.revise); return; }
    const pg = e.target.closest("[data-log-page]");
    if (pg && !pg.disabled) {
      loadLogsPage(Number(pg.dataset.logPage) || 1);
    }
  });

  /* tombol di halaman detail */
  $("#btnSaveNote").addEventListener("click", async () => {
    const u = DB.umkm.find((x) => String(x.id) === String(detailId));
    if (!u || !u.has_site) return;

    const btn = $("#btnSaveNote");
    btn.disabled = true;
    try {
      const res = await apiFetch(`${ROUTES.umkmBase}/${encodeURIComponent(u.id)}/note`, {
        method: "PATCH",
        body: JSON.stringify({ note: $("#detailNote").value.trim() }),
      });
      if (res?.umkm) upsertUmkm(res.umkm);
      applyMutationLogs(res?.logs);
      renderDetail();
      renderLogs();
      toast("Catatan tersimpan");
    } catch (err) {
      toast(err.message || "Gagal menyimpan catatan.");
    } finally {
      btn.disabled = !u.has_site;
    }
  });
  $("#btnApprove").addEventListener("click", () => {
    if (detailId != null) approve(detailId, $("#btnApprove"));
  });
  $("#btnRevise").addEventListener("click", () => {
    if (detailId != null) openReason(detailId);
  });

  /* filter UMKM */
  $("#umkmSearch").addEventListener("input", (e) => { umkmSearch = e.target.value; renderUmkm(); });
  $("#umkmFilters").addEventListener("click", (e) => {
    const c = e.target.closest("[data-status]");
    if (!c) return;
    umkmStatus = c.dataset.status;
    $$("#umkmFilters .chip").forEach((x) => x.classList.toggle("is-active", x === c));
    renderUmkm();
  });

  /* toggle template */
  $("#tplAdminGrid").addEventListener("change", async (e) => {
    const t = e.target.closest("[data-tpl]");
    if (!t) return;
    const tplId = t.dataset.tpl;
    const nextActive = t.checked;
    t.disabled = true;

    try {
      const res = await apiFetch(`${ROUTES.templatesBase}/${encodeURIComponent(tplId)}`, {
        method: "PATCH",
        body: JSON.stringify({ is_active: nextActive }),
      });
      if (Array.isArray(res?.templates)) {
        syncTemplates(res.templates);
      } else {
        DB.tplActive[tplId] = nextActive;
      }
      applyMutationLogs(res?.logs);
      renderTplAdmin();
      renderStats();
      renderLogs();
      toast(nextActive ? "Template diaktifkan" : "Template dinonaktifkan");
    } catch (err) {
      t.checked = !nextActive;
      toast(err.message || "Gagal memperbarui status template.");
    } finally {
      t.disabled = false;
    }
  });

  /* toggle auto setujui */
  $("#autoApprove").addEventListener("change", async (e) => {
    const toggle = e.target;
    const nextEnabled = toggle.checked;
    toggle.disabled = true;

    try {
      const res = await apiFetch(ROUTES.autoApprove, {
        method: "PATCH",
        body: JSON.stringify({ auto_approve: nextEnabled }),
      });
      applyBootstrap(res);
      render();
      const approvedCount = Number(res?.approved_count || 0);
      toast(nextEnabled
        ? (approvedCount ? `${approvedCount} situs langsung disetujui otomatis ✓` : "Auto setujui aktif")
        : "Auto setujui dimatikan");
    } catch (err) {
      toggle.checked = !nextEnabled;
      toast(err.message || "Gagal memperbarui auto setujui.");
    } finally {
      toggle.disabled = false;
    }
  });

  /* pengaturan kontak pendamping */
  ["#adminNama", "#adminWa"].forEach((sel) => {
    $(sel).addEventListener("input", () => {
      $("#adminPreview").textContent = SET.waPreview($("#adminWa").value, ADMIN_PESAN);
    });
  });
  $("#btnSaveAdmin").addEventListener("click", async () => {
    const nama = $("#adminNama").value.trim() || "Tim INAmikro";
    const waRaw = $("#adminWa").value.trim();
    if (!SET.validWa(waRaw)) {
      toast("Nomor tidak valid. Pakai format 08… atau +62…");
      $("#adminWa").focus();
      return;
    }

    const btn = $("#btnSaveAdmin");
    btn.disabled = true;
    try {
      const res = await apiFetch(ROUTES.contact, {
        method: "PATCH",
        body: JSON.stringify({ admin_name: nama, admin_whatsapp: waRaw }),
      });
      if (res?.settings) {
        DB.settings = {
          ...DB.settings,
          ...res.settings,
        };
      }
      applyMutationLogs(res?.logs);
      $("#adminNama").value = DB.settings.admin_name || "";
      $("#adminWa").value = DB.settings.admin_whatsapp || "";
      renderSettings();
      renderLogs();
      toast("Pengaturan tersimpan");
    } catch (err) {
      toast(err.message || "Gagal menyimpan pengaturan kontak.");
    } finally {
      btn.disabled = false;
    }
  });

  /* admin avatar popover + logout */
  const avatarBtn = $("#adminAvatarBtn");
  const profilePop = $("#adminProfilePop");
  const closeProfilePop = () => {
    if (!profilePop || !avatarBtn) return;
    profilePop.hidden = true;
    avatarBtn.setAttribute("aria-expanded", "false");
  };
  if (avatarBtn && profilePop) {
    avatarBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      const willOpen = profilePop.hidden;
      profilePop.hidden = !willOpen;
      avatarBtn.setAttribute("aria-expanded", String(willOpen));
    });
  }

  document.addEventListener("click", (e) => {
    if (!e.target.closest(".profile-wrap")) closeProfilePop();
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      if (!$("#reasonModal").hidden) closeReason();
      closeProfilePop();
    }
  });

  render();
})();
