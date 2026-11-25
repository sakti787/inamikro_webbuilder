/* ============================================================
   Log aktivitas panel pendamping — helper murni
   Dipakai di browser lewat INAWebLog dan di tes lewat require().
   ============================================================ */
(function (root, factory) {
  const api = factory();
  if (typeof module === "object" && module.exports) module.exports = api;
  else root.INAWebLog = api;
})(typeof globalThis !== "undefined" ? globalThis : this, function () {
  "use strict";

  const PAGE_SIZE = 8;
  const MAX_LOGS = 200;
  let seq = 0;

  function makeLog(opt) {
    const o = opt || {};
    seq += 1;
    return {
      id: "l" + Date.now() + "-" + seq,
      at: o.at || new Date().toISOString(),
      title: o.title || "",
      text: o.text || "",
      who: o.who || "",
      kind: o.kind || "general",
    };
  }

  function addLog(logs, entry) {
    return [entry].concat(Array.isArray(logs) ? logs : []).slice(0, MAX_LOGS);
  }

  function timeAgo(at, now) {
    const t = new Date(at).getTime();
    if (!isFinite(t)) return "Waktu tidak diketahui";
    const menit = Math.max(0, Math.floor(((now == null ? Date.now() : now) - t) / 60000));
    if (menit < 1) return "Baru saja";
    if (menit < 60) return menit + " menit lalu";
    const jam = Math.floor(menit / 60);
    if (jam < 24) return jam + " jam lalu";
    const hari = Math.floor(jam / 24);
    if (hari < 7) return hari + " hari lalu";
    return new Intl.DateTimeFormat("id-ID", { day: "numeric", month: "short", year: "numeric" }).format(new Date(t));
  }

  function pageLogs(logs, page) {
    const all = Array.isArray(logs) ? logs : [];
    const total = all.length;
    const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
    const p = Math.min(pages, Math.max(1, Number(page) || 1));
    return {
      items: all.slice((p - 1) * PAGE_SIZE, p * PAGE_SIZE),
      page: p,
      pages: pages,
      total: total,
    };
  }

  function withLogs(db, seedLogs) {
    const out = Object.assign({}, db || {});
    if (!Array.isArray(out.logs)) out.logs = (seedLogs || []).slice();
    return out;
  }

  return { PAGE_SIZE, MAX_LOGS, makeLog, addLog, timeAgo, pageLogs, withLogs };
});
