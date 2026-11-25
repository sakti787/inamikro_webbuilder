/* ============================================================
   Pengaturan panel pendamping — helper murni
   Dipakai di browser lewat INAWebSettings dan di tes lewat require().
   ============================================================ */
(function (root, factory) {
  const api = factory();
  if (typeof module === "object" && module.exports) module.exports = api;
  else root.INAWebSettings = api;
})(typeof globalThis !== "undefined" ? globalThis : this, function () {
  "use strict";

  function normalizeWa(input) {
    let s = String(input == null ? "" : input).replace(/[^\d]/g, "");
    if (s.charAt(0) === "0") s = "62" + s.slice(1);
    else if (s.indexOf("62") === 0) s = s;
    return s;
  }

  function validWa(input) {
    const s = normalizeWa(input);
    if (!s) return true;
    return s.indexOf("62") === 0 && s.length >= 10 && s.length <= 15;
  }

  function waPreview(wa, pesan) {
    const s = normalizeWa(wa);
    if (!s) return "Nomor belum diisi — isi nomor WhatsApp dulu untuk melihat pratinjau.";
    return "https://wa.me/" + s + "?text=" + encodeURIComponent(pesan || "Halo kak, butuh bantuan INAmikro.");
  }

  function withAdmin(db, seedAdmin) {
    const out = Object.assign({}, db || {});
    if (!out.admin || typeof out.admin !== "object" || Array.isArray(out.admin)) {
      out.admin = Object.assign({}, seedAdmin || {});
    }
    return out;
  }

  return { normalizeWa, validWa, waPreview, withAdmin };
});
