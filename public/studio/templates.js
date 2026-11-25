/* ============================================================
   templates.js — definisi template + renderer halaman
   Setiap template adalah fungsi murni: state -> HTML utuh.
   ============================================================ */

const esc = (s = "") =>
  String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

const rupiah = (v) => {
  const n = String(v || "").replace(/[^\d]/g, "");
  if (!n) return esc(v || "");
  return "Rp" + Number(n).toLocaleString("id-ID");
};

const waLink = (wa = "") => "https://wa.me/" + String(wa).replace(/[^\d]/g, "").replace(/^0/, "62");

const slugTpl = (nama) =>
  String(nama || "")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-|-$/g, "") || "usaha-kamu";

const siteUrl = (state) => window.INAWEB_CONFIG?.publicSiteUrl || ("https://inamikro.co.id/" + slugTpl(state.nama));

const absUrl = (u) => {
  if (!u) return "";
  if (/^\/storage\//.test(u)) {
    const base = window.INAWEB_CONFIG?.publicBaseUrl || window.location.origin || "";
    return String(base).replace(/\/$/, "") + u;
  }
  return u;
};

const mapsLink = (alamat) =>
  "https://www.google.com/maps/search/?api=1&query=" + encodeURIComponent(String(alamat || ""));

const orderLink = (s, p) => {
  const pesan =
    "Halo kak, mau pesan " +
    (p.nama || "produk") +
    (p.harga ? " (" + rupiah(p.harga) + ")" : "") +
    ". Bisa dibantu?";
  return waLink(s.wa) + "?text=" + encodeURIComponent(pesan);
};

const shareBtn = (s) =>
  `<button class="btn alt" type="button" data-share data-url="${esc(siteUrl(s))}">Bagikan</button>`;

const searchBar = (ph = "Cari produk...") => `
  <div class="cari"><input type="search" data-cari placeholder="${esc(ph)}" aria-label="Cari produk"><span aria-hidden="true">⌕</span></div>
  <p class="cari-empty" data-cari-empty hidden>Produk tidak ditemukan. Coba kata kunci lain.</p>`;

const imgOr = (url, fallback) =>
  url && /^(https?:\/\/|\/storage\/|data:image\/)/.test(url)
    ? `<img src="${esc(url)}" alt="" loading="lazy" data-zoom>`
    : fallback;

const prodThumb = (p, cls = "pmini") =>
  `<div class="${cls}">${p.img && /^(https?:\/\/|\/storage\/|data:image\/)/.test(p.img) ? `<img src="${esc(p.img)}" alt="" loading="lazy" data-zoom>` : esc((p.nama || "?").charAt(0).toUpperCase())}</div>`;

/* skrip ringan yang ikut ke file ekspor: pesan WA, filter, lightbox, status buka, bagikan */
const CLIENT_JS = `
(function(){
  var body=document.body;
  function one(s,r){return (r||document).querySelector(s);}
  function all(s,r){return Array.prototype.slice.call((r||document).querySelectorAll(s));}

  function jamInfo(txt){
    if(!txt) return null;
    var t=String(txt).toLowerCase(), nums=[], cur='';
    for(var i=0;i<t.length;i++){
      var c=t.charAt(i);
      if(c>='0'&&c<='9'){cur+=c;}
      else if(cur){nums.push(Number(cur));cur='';}
    }
    if(cur) nums.push(Number(cur));
    if(nums.length<4) return null;
    var from=nums[0]*60+nums[1], to=nums[2]*60+nums[3];
    if(!from && !to) return null;
    var now=new Date(), d=now.getDay(), mins=now.getHours()*60+now.getMinutes();
    var map={ahad:0,minggu:0,senin:1,selasa:2,rabu:3,kamis:4,jumat:5,sabtu:6};
    var picked=[];
    Object.keys(map).forEach(function(k){ if(t.indexOf(k)>=0 && picked.indexOf(map[k])<0) picked.push(map[k]); });
    if(!picked.length) picked=[0,1,2,3,4,5,6];
    if(to<=from) to+=1440;
    if(mins<from) mins+=1440;
    return {open: picked.indexOf(d)>=0 && mins>=from && mins<to};
  }
  var info=jamInfo(body.getAttribute('data-jam'));
  all('[data-status]').forEach(function(el){
    if(!info){ el.hidden=true; return; }
    el.hidden=false;
    el.className='sbadge '+(info.open?'is-open':'is-closed');
    el.textContent=info.open?'● Buka sekarang':'● Tutup';
  });

  all('[data-cari]').forEach(function(inp){
    inp.addEventListener('input',function(){
      var v=inp.value.toLowerCase().trim(), any=false;
      all('[data-prod]').forEach(function(el){
        var ok=!v || (el.getAttribute('data-nama')||'').toLowerCase().indexOf(v)>=0;
        el.style.display=ok?'':'none';
        if(ok) any=true;
      });
      var empty=one('[data-cari-empty]');
      if(empty) empty.hidden=!(v && !any);
    });
  });

  var lb=null;
  function closeLb(){
    if(lb) lb.hidden=true;
    document.documentElement.style.overflow='';
  }
  function openLb(src){
    if(!lb){
      lb=document.createElement('div');
      lb.className='lb'; lb.hidden=true;
      lb.innerHTML='<button class="lb-close" type="button" aria-label="Tutup foto">×</button><img class="lb-img" alt="Foto produk">';
      document.body.appendChild(lb);
      lb.addEventListener('click',function(e){
        if(e.target===lb || (e.target.className||'').indexOf('lb-close')>=0) closeLb();
      });
    }
    one('.lb-img',lb).src=src;
    lb.hidden=false;
    document.documentElement.style.overflow='hidden';
  }
  document.addEventListener('click',function(e){
    var im=e.target && e.target.closest ? e.target.closest('img[data-zoom]') : null;
    if(im && im.src) openLb(im.src);
  });
  document.addEventListener('keydown',function(e){ if(e.key==='Escape') closeLb(); });

  function note(msg){
    var t=one('[data-toast]');
    if(!t){ t=document.createElement('div'); t.className='site-toast'; t.setAttribute('data-toast',''); document.body.appendChild(t); }
    t.textContent=msg; t.className='site-toast show';
    clearTimeout(t._t); t._t=setTimeout(function(){ t.className='site-toast'; },2200);
  }
  all('[data-share]').forEach(function(b){
    b.addEventListener('click',function(){
      var url=b.getAttribute('data-url')||location.href;
      var text=body.getAttribute('data-share-text')||'';
      if(navigator.share){
        navigator.share({title:document.title,text:text,url:url}).catch(function(){});
      } else if(navigator.clipboard && navigator.clipboard.writeText){
        navigator.clipboard.writeText(url).then(function(){ note('Tautan disalin · '+url); },function(){ note(url); });
      } else {
        note(url);
      }
    });
  });
})();
`;

/* kerangka dokumen: dipakai semua template */
function doc(state, css, body) {
  const brand = state.brand || "#dc2626";
  const url = siteUrl(state);
  const title = String(state.nama || "Usaha") + (state.tagline ? " — " + state.tagline : "");
  const desc = String(state.deskripsi || state.tagline || (state.nama + " — profil usaha")).replace(/\s+/g, " ").trim();
  const ogImage = state.heroImg && /^(https?:\/\/|\/storage\/|data:image\/)/.test(state.heroImg)
    ? `\n<meta property="og:image" content="${esc(absUrl(state.heroImg))}">`
    : "";
  return `<!doctype html>
<html lang="id"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>${esc(title)}</title>
<meta name="description" content="${esc(desc)}">
<meta property="og:title" content="${esc(title)}">
<meta property="og:description" content="${esc(desc)}">
<meta property="og:url" content="${esc(url)}">
<meta property="og:type" content="website">${ogImage}
<meta name="twitter:card" content="summary">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
<style>
:root{--b:${esc(brand)};--ink:#0f172a;--muted:#475569;--line:rgba(148,163,184,.22);--cream:#fff8f7}
*{box-sizing:border-box}html{scroll-behavior:smooth}
body{margin:0;font-family:"Plus Jakarta Sans",system-ui,sans-serif;color:var(--ink);background:var(--cream);-webkit-font-smoothing:antialiased}
body.v2{--cream:${esc(brand)}0d}
img{max-width:100%;display:block}
a{color:inherit;text-decoration:none}
h1,h2,h3,p{margin:0}
.wrap{max-width:1120px;margin:0 auto;padding:0 26px}
.btn{display:inline-flex;align-items:center;gap:8px;background:var(--b);color:#fff;padding:13px 24px;border-radius:999px;font-weight:700;font-size:14px;box-shadow:0 12px 26px color-mix(in srgb,var(--b) 30%,transparent)}
.btn.alt{background:#fff;color:var(--ink);border:1px solid var(--line);box-shadow:none}
.pmini{width:54px;height:54px;border-radius:13px;overflow:hidden;flex:0 0 auto;display:grid;place-items:center;background:linear-gradient(150deg,color-mix(in srgb,var(--b) 12%,#fff),#f8fafc);color:color-mix(in srgb,var(--b) 34%,#fff);font-weight:800;font-size:21px}
.pthumb{overflow:hidden}
.pmini img,.pthumb img{width:100%;height:100%;object-fit:cover;display:block}
.price-card .pthumb{height:126px;border-radius:14px;margin-bottom:16px;display:grid;place-items:center;background:linear-gradient(150deg,color-mix(in srgb,var(--b) 12%,#fff),#f8fafc);color:color-mix(in srgb,var(--b) 34%,#fff);font-size:42px;font-weight:800}
.prod{align-items:center}
.prod[href],.item[href],.price-card[href],.card[href]{color:inherit;text-decoration:none}
.prod[href]:hover b,.item[href]:hover b,.price-card[href]:hover h3,.card[href]:hover b{color:var(--b)}
[data-zoom]{cursor:zoom-in}
.cari{position:relative;max-width:360px;margin:0 0 22px auto}
.cari input{width:100%;border:1px solid var(--line);background:#fff;border-radius:999px;padding:11px 16px 11px 38px;font:inherit;font-size:13px;color:var(--ink);outline:none}
.cari input:focus{border-color:var(--b)}
.cari>span{position:absolute;left:14px;top:9px;color:var(--muted);font-size:17px}
.cari-empty{color:var(--muted);font-size:13px;padding:18px 0}
.cari-empty[hidden]{display:none}
.sbadge{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:800;padding:7px 12px;border-radius:999px;white-space:nowrap}
.sbadge[hidden]{display:none!important}
.sbadge.is-open{background:#dcfce7;color:#15803d}
.sbadge.is-closed{background:#f1f5f9;color:#64748b}
.lb{position:fixed;inset:0;z-index:9999;background:rgba(2,6,23,.88);backdrop-filter:blur(6px);display:grid;place-items:center;padding:26px}
.lb[hidden]{display:none!important}
.lb-img{max-width:92vw;max-height:82vh;object-fit:contain;border-radius:14px;box-shadow:0 24px 70px rgba(0,0,0,.45)}
.lb-close{position:absolute;top:20px;right:22px;width:40px;height:40px;border:0;border-radius:999px;background:#fff;color:#0f172a;font-size:24px;line-height:1;cursor:pointer}
.site-toast{position:fixed;left:50%;bottom:26px;transform:translate(-50%,12px);background:#0f172a;color:#fff;padding:11px 18px;border-radius:999px;font-size:12.5px;font-weight:700;opacity:0;pointer-events:none;transition:.22s;z-index:10000;max-width:90vw;text-align:center}
.site-toast.show{opacity:1;transform:translate(-50%,0)}
${css}
</style></head>
<body class="${state.variant ? "v2" : ""}" data-jam="${esc(state.jam || "")}" data-share-text="Lihat ${esc(state.nama || "usaha")} di INAweb Studio">
${body}
<footer class="foot"><div class="wrap fw"><span>© ${new Date().getFullYear()} ${esc(state.nama)}</span><span>Dibuat dengan <b>INAweb Studio</b></span></div></footer>
<script>${CLIENT_JS}</script>
</body></html>`;
}

const produkList = (state, cls = "") => {
  const target = state.wa ? ' target="_blank" rel="noopener"' : "";
  return state.produk
    .filter((p) => p.nama)
    .map(
      (p) => `<a class="prod ${cls}" data-prod data-nama="${esc(p.nama)}" href="${state.wa ? orderLink(state, p) : "#kontak"}"${target}>
        ${prodThumb(p)}
        <div style="flex:1"><b>${esc(p.nama)}</b>${p.ket ? `<small>${esc(p.ket)}</small>` : ""}</div>
        <span class="price">${rupiah(p.harga)}</span>
      </a>`
    )
    .join("");
};

const kontakBar = (state, extra = "") => `
  <div class="contact">
    ${state.alamat ? `<span>📍 ${esc(state.alamat)}</span>` : ""}
    ${state.jam ? `<span>🕒 ${esc(state.jam)}</span>` : ""}
    ${state.wa ? `<a class="cwa" href="${waLink(state.wa)}" target="_blank" rel="noopener">WhatsApp</a>` : ""}
    ${state.alamat ? `<a class="cmap" href="${mapsLink(state.alamat)}" target="_blank" rel="noopener">🗺 Petunjuk arah</a>` : ""}
    ${state.ig ? `<a class="cig" href="https://instagram.com/${esc(String(state.ig).replace("@", ""))}" target="_blank" rel="noopener">@${esc(String(state.ig).replace("@", ""))}</a>` : ""}
  </div>${extra}`;

/* ============================================================
   1. WARUNG — kuliner, hero terbelah + daftar menu
   ============================================================ */
function tplWarung(s) {
  const css = `
  .nav{display:flex;align-items:center;justify-content:space-between;padding:22px 0}
  .logo{font-weight:800;font-size:19px;letter-spacing:-.02em}
  .logo span{color:var(--b)}
  .nav-links{display:flex;gap:26px;font-size:13.5px;font-weight:600;color:var(--muted)}
  .nav .btn{padding:10px 18px;font-size:13px}
  .hero{display:grid;grid-template-columns:1.05fr .95fr;gap:54px;align-items:center;padding:44px 0 72px}
  .badge{display:inline-flex;align-items:center;gap:7px;background:color-mix(in srgb,var(--b) 10%,#fff);color:var(--b);font-weight:800;font-size:11px;letter-spacing:.08em;text-transform:uppercase;padding:7px 13px;border-radius:999px}
  .hero h1{font-size:54px;line-height:1.06;letter-spacing:-.035em;margin:18px 0 16px;font-weight:800}
  .hero h1 em{font-style:normal;color:var(--b)}
  .hero p.lead{color:var(--muted);font-size:15.5px;line-height:1.7;max-width:460px}
  .hero-cta{display:flex;gap:12px;margin-top:28px;flex-wrap:wrap}
  .photo{position:relative;aspect-ratio:4/4.4;border-radius:28px;overflow:hidden;background:linear-gradient(150deg,color-mix(in srgb,var(--b) 20%,#fff),color-mix(in srgb,var(--b) 6%,#fff));box-shadow:0 30px 60px rgba(15,23,42,.14)}
  .photo img{width:100%;height:100%;object-fit:cover}
  .photo .ph{position:absolute;inset:0;display:grid;place-items:center;font-size:96px;font-weight:800;color:color-mix(in srgb,var(--b) 32%,#fff)}
  .float{position:absolute;left:18px;bottom:20px;background:#fff;border-radius:18px;padding:14px 18px;box-shadow:0 20px 44px rgba(15,23,42,.14);font-size:12px}
  .float b{display:block;font-size:17px;margin-bottom:2px}
  .float small{color:var(--muted)}
  .strip{background:#fff;border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
  .strip .wrap{display:flex;gap:40px;padding:20px 26px;flex-wrap:wrap;font-size:12.5px;font-weight:700;color:var(--muted);align-items:center}
  section{padding:74px 0}
  .shead{max-width:600px;margin-bottom:38px}
  .shead h2{font-size:36px;letter-spacing:-.03em;margin-bottom:12px}
  .shead p{color:var(--muted);font-size:14.5px;line-height:1.7}
  .menu{display:grid;grid-template-columns:1fr 1fr;gap:14px 44px}
  .prod{display:flex;justify-content:space-between;gap:18px;padding:15px 0;border-bottom:1px dashed var(--line);align-items:baseline}
  .prod b{font-size:15px;display:block}
  .prod small{color:var(--muted);font-size:12.5px;display:block;margin-top:4px;line-height:1.5}
  .prod .price{font-weight:800;color:var(--b);white-space:nowrap;font-size:14px}
  .about{display:grid;grid-template-columns:1fr 1fr;gap:44px;align-items:center;background:#fff;border-radius:32px;padding:44px;border:1px solid var(--line)}
  .about h2{font-size:30px;letter-spacing:-.025em;margin-bottom:14px}
  .about p{color:var(--muted);line-height:1.75;font-size:14.5px}
  .contact{display:flex;gap:12px;flex-wrap:wrap;margin-top:26px}
  .contact span,.contact a{font-size:12.5px;font-weight:700;background:#f8fafc;border:1px solid var(--line);padding:9px 14px;border-radius:999px;color:var(--muted)}
  .contact a.cwa{background:var(--b);color:#fff;border-color:transparent}
  .foot{border-top:1px solid var(--line);padding:26px 0;font-size:12px;color:var(--muted)}
  .fw{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap}
  @media(max-width:860px){.hero{grid-template-columns:1fr;gap:32px;padding-top:24px}.hero h1{font-size:38px}.menu{grid-template-columns:1fr}.about{grid-template-columns:1fr;padding:28px}.nav-links{display:none}section{padding:52px 0}}
  .v2 .hero{grid-template-columns:1fr;text-align:center;gap:36px}
  .v2 .hero p.lead{margin:0 auto}
  .v2 .hero-cta{justify-content:center}
  .v2 .photo{grid-row:1;aspect-ratio:16/8;order:-1}
  .v2 .float{left:auto;right:18px;bottom:18px}
  .v2 .menu{gap:0 44px}
  .v2 .about{background:transparent;border:0;padding:0}`;

  const body = `
  <div class="wrap nav">
    <div class="logo">${esc(s.nama).split(" ")[0]}<span>${esc(s.nama).split(" ").slice(1).join(" ")}</span></div>
    <div class="nav-links"><a href="#menu">Menu</a><a href="#tentang">Tentang</a><a href="#kontak">Kontak</a></div>
    <span class="sbadge" data-status hidden></span>
    <a class="btn" href="#kontak">Pesan sekarang</a>
  </div>

  <div class="wrap hero">
    <div>
      <span class="badge">${esc(s.kategori || "Kuliner rumahan")}</span>
      <h1>${esc(s.tagline).replace(/(\S+)$/, "<em>$1</em>")}</h1>
      <p class="lead">${esc(s.deskripsi)}</p>
      <div class="hero-cta">
        <a class="btn" href="${s.wa ? waLink(s.wa) : "#kontak"}"${s.wa ? ' target="_blank" rel="noopener"' : ""}>Pesan via WhatsApp</a>
        <a class="btn alt" href="#menu">Lihat menu</a>
        ${shareBtn(s)}
      </div>
    </div>
    <div class="photo">
      ${imgOr(s.heroImg, `<div class="ph">${esc(s.nama).charAt(0)}</div>`)}
      <div class="float"><b>Buka hari ini</b><small>${esc(s.jam || "08.00 – 21.00")}</small></div>
    </div>
  </div>

  <div class="strip"><div class="wrap"><span>✓ Bahan segar setiap hari</span><span>✓ Pesan antar area kota</span><span>✓ Sejak 2019</span></div></div>

  <section id="menu"><div class="wrap">
    <div class="shead"><h2>Menu favorit pelanggan</h2><p>Pilihan paling sering dipesan. Semua dimasak saat dipesan.</p></div>
    ${s.produk.some((p) => p.nama) ? searchBar("Cari menu...") : ""}
    <div class="menu">${produkList(s)}</div>
  </div></section>

  <section id="tentang"><div class="wrap about">
    <div>
      <h2>Tentang ${esc(s.nama)}</h2>
      <p>${esc(s.deskripsi)}</p>
      ${kontakBar(s)}
    </div>
    <div class="photo" style="aspect-ratio:4/3">${imgOr(s.heroImg, `<div class="ph">${esc(s.nama).charAt(0)}</div>`)}</div>
  </div></section>`;

  return doc(s, css, body);
}

/* ============================================================
   2. BUTIK — fashion, hero tengah + grid katalog
   ============================================================ */
function tplButik(s) {
  const css = `
  .top{text-align:center;padding:26px 0 8px;border-bottom:1px solid var(--line)}
  .top .logo{font-family:"Playfair Display",serif;font-size:30px;letter-spacing:.02em;font-weight:600}
  .top nav{display:flex;justify-content:center;gap:30px;font-size:11.5px;letter-spacing:.16em;text-transform:uppercase;font-weight:700;color:var(--muted);padding:16px 0 20px}
  .hero{padding:64px 0 40px;text-align:center}
  .hero .eyebrow{font-size:11px;letter-spacing:.3em;text-transform:uppercase;color:var(--b);font-weight:800}
  .hero h1{font-family:"Playfair Display",serif;font-size:62px;line-height:1.08;letter-spacing:-.02em;margin:18px auto 18px;max-width:760px;font-weight:600}
  .hero p{color:var(--muted);font-size:15px;line-height:1.75;max-width:520px;margin:0 auto}
  .hero .btn{margin-top:28px}
  .cover{position:relative;height:420px;border-radius:4px;overflow:hidden;margin-top:52px;background:linear-gradient(120deg,color-mix(in srgb,var(--b) 16%,#fff),#f5f2ef)}
  .cover img{width:100%;height:100%;object-fit:cover}
  .cover .ph{position:absolute;inset:0;display:grid;place-items:center;font-family:"Playfair Display",serif;font-size:120px;color:color-mix(in srgb,var(--b) 26%,#fff)}
  section{padding:70px 0}
  .shead{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin-bottom:32px}
  .shead h2{font-family:"Playfair Display",serif;font-size:32px;font-weight:600}
  .shead small{color:var(--muted);font-size:12.5px}
  .cat{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
  .item{border:1px solid var(--line);border-radius:3px;overflow:hidden;background:#fff}
  .item .ph-box{aspect-ratio:3/3.6;background:linear-gradient(160deg,color-mix(in srgb,var(--b) 10%,#fff),#f7f4f1);display:grid;place-items:center;font-family:"Playfair Display",serif;font-size:44px;color:color-mix(in srgb,var(--b) 34%,#fff)}
  .item .ib{padding:16px}
  .item b{font-size:14.5px;display:block}
  .item small{display:block;color:var(--muted);font-size:12px;margin-top:4px;line-height:1.5}
  .item .price{display:block;margin-top:10px;font-weight:700;font-size:13.5px;color:var(--b)}
  .story{display:grid;grid-template-columns:.9fr 1.1fr;gap:52px;align-items:center;padding:60px 44px;background:#fff;border:1px solid var(--line)}
  .story h2{font-family:"Playfair Display",serif;font-size:34px;margin-bottom:16px;font-weight:600}
  .story p{color:var(--muted);line-height:1.8;font-size:14.5px}
  .contact{display:flex;gap:12px;flex-wrap:wrap;margin-top:28px}
  .contact span,.contact a{font-size:11.5px;letter-spacing:.04em;font-weight:700;padding:10px 16px;border:1px solid var(--line);color:var(--muted)}
  .contact a.cwa{background:var(--b);color:#fff;border-color:var(--b)}
  .foot{border-top:1px solid var(--line);padding:26px 0;font-size:11.5px;color:var(--muted);text-align:center}
  .fw{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap}
  @media(max-width:860px){.hero h1{font-size:38px}.cat{grid-template-columns:1fr 1fr}.story{grid-template-columns:1fr;padding:28px}.cover{height:260px}.top nav{gap:16px;font-size:10px}}
  .v2 .hero h1{font-family:"Plus Jakarta Sans",sans-serif;font-weight:800;font-size:52px;letter-spacing:-.035em}
  .v2 .cat{grid-template-columns:repeat(4,1fr)}
  .v2 .cover{height:300px;border-radius:120px 120px 6px 6px}
  .v2 .story{background:transparent;border:0;padding:40px 0}`;

  const body = `
  <div class="wrap top">
    <div class="logo">${esc(s.nama)}</div>
    <nav><a href="#koleksi">Koleksi</a><a href="#tentang">Tentang</a><a href="#kontak">Kontak</a><span class="sbadge" data-status hidden></span></nav>
  </div>

  <div class="wrap hero">
    <span class="eyebrow">${esc(s.kategori || "Koleksi terbaru")}</span>
    <h1>${esc(s.tagline)}</h1>
    <p>${esc(s.deskripsi)}</p>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:28px">
      <a class="btn" href="${s.wa ? waLink(s.wa) : "#kontak"}"${s.wa ? ' target="_blank" rel="noopener"' : ""}>Pesan koleksi</a>
      ${shareBtn(s)}
    </div>
    <div class="cover">${imgOr(s.heroImg, `<div class="ph">${esc(s.nama).charAt(0)}</div>`)}</div>
  </div>

  <section id="koleksi"><div class="wrap">
    <div class="shead"><h2>Pilihan kami</h2><small>${esc(s.jam || "Dikirim seluruh Indonesia")}</small></div>
    ${s.produk.some((p) => p.nama) ? searchBar("Cari koleksi...") : ""}
    <div class="cat">${s.produk
      .filter((p) => p.nama)
      .map(
        (p) => `<a class="item" data-prod data-nama="${esc(p.nama)}" href="${s.wa ? orderLink(s, p) : "#kontak"}"${s.wa ? ' target="_blank" rel="noopener"' : ""}>${prodThumb(p, "ph-box pthumb")}
        <div class="ib"><b>${esc(p.nama)}</b>${p.ket ? `<small>${esc(p.ket)}</small>` : ""}
        <span class="price">${rupiah(p.harga)}</span></div></a>`
      )
      .join("")}</div>
  </div></section>

  <section id="tentang"><div class="wrap story">
    <div><h2>Tentang ${esc(s.nama)}</h2></div>
    <div><p>${esc(s.deskripsi)}</p>${kontakBar(s)}</div>
  </div></section>`;

  return doc(s, css, body);
}

/* ============================================================
   3. JASA — jasa profesional, harga paket
   ============================================================ */
function tplJasa(s) {
  const css = `
  .nav{display:flex;align-items:center;gap:32px;padding:24px 0}
  .logo{display:flex;align-items:center;gap:10px;font-weight:800;font-size:17px}
  .logo i{width:32px;height:32px;border-radius:9px;background:var(--b);display:grid;place-items:center;color:#fff;font-style:normal;font-size:14px}
  .nav-links{display:flex;gap:26px;font-size:13px;font-weight:600;color:var(--muted);margin-left:auto;align-items:center}
  .hero{display:grid;grid-template-columns:1.1fr .9fr;gap:46px;padding:38px 0 66px;align-items:center}
  .hero h1{font-size:48px;line-height:1.08;letter-spacing:-.035em;font-weight:800;margin-bottom:18px}
  .hero h1 u{text-decoration:none;color:var(--b);border-bottom:4px solid color-mix(in srgb,var(--b) 30%,transparent)}
  .hero p{color:var(--muted);font-size:15px;line-height:1.75;max-width:470px}
  .cta{display:flex;gap:12px;margin-top:30px;flex-wrap:wrap}
  .trust{display:flex;gap:26px;margin-top:36px;padding-top:26px;border-top:1px solid var(--line)}
  .trust div b{display:block;font-size:22px;font-weight:800}
  .trust div small{font-size:11.5px;color:var(--muted)}
  .side{background:#fff;border:1px solid var(--line);border-radius:26px;padding:30px;box-shadow:0 26px 54px rgba(15,23,42,.10)}
  .side h3{font-size:16px;margin-bottom:18px}
  .chk{display:flex;gap:10px;align-items:flex-start;padding:11px 0;border-bottom:1px dashed var(--line);font-size:13px;color:var(--muted)}
  .chk:last-of-type{border-bottom:0}
  .chk i{width:18px;height:18px;border-radius:999px;background:color-mix(in srgb,var(--b) 12%,#fff);color:var(--b);font-style:normal;font-size:11px;display:grid;place-items:center;flex:0 0 auto;font-weight:800}
  .side .btn{width:100%;justify-content:center;margin-top:20px}
  section{padding:72px 0}
  .shead{text-align:center;max-width:580px;margin:0 auto 44px}
  .shead h2{font-size:34px;letter-spacing:-.03em;margin-bottom:12px}
  .shead p{color:var(--muted);font-size:14.5px;line-height:1.7}
  .cards{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
  .price-card{border:1px solid var(--line);border-radius:22px;padding:26px;background:#fff;display:flex;flex-direction:column}
  .price-card.hero-card{border-color:var(--b);box-shadow:0 20px 42px color-mix(in srgb,var(--b) 14%,transparent);position:relative}
  .price-card.hero-card::after{content:"Paling dipilih";position:absolute;top:-11px;left:26px;background:var(--b);color:#fff;font-size:10px;font-weight:800;letter-spacing:.08em;padding:5px 10px;border-radius:999px}
  .price-card h3{font-size:14px;color:var(--muted);font-weight:700;letter-spacing:.04em;text-transform:uppercase}
  .price-card .num{font-size:32px;font-weight:800;letter-spacing:-.03em;margin:12px 0 4px}
  .price-card .num small{font-size:12px;color:var(--muted);font-weight:600}
  .price-card ul{list-style:none;margin:18px 0 0;padding:0;display:flex;flex-direction:column;gap:9px}
  .price-card li{font-size:12.5px;color:var(--muted);display:flex;gap:8px}
  .price-card li b{color:var(--b)}
  .price-card .btn{margin-top:22px;justify-content:center}
  .contact{display:flex;gap:12px;flex-wrap:wrap;justify-content:center;margin-top:34px}
  .contact span,.contact a{font-size:12px;font-weight:700;padding:9px 15px;border-radius:999px;background:#fff;border:1px solid var(--line);color:var(--muted)}
  .contact a.cwa{background:var(--b);color:#fff;border-color:transparent}
  .foot{border-top:1px solid var(--line);padding:24px 0;font-size:12px;color:var(--muted)}
  .fw{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap}
  @media(max-width:860px){.hero{grid-template-columns:1fr}.hero h1{font-size:34px}.cards{grid-template-columns:1fr}.nav-links{display:none}section{padding:50px 0}}
  .v2 .hero{grid-template-columns:1fr;text-align:center;padding-bottom:40px}
  .v2 .hero p{margin:0 auto}
  .v2 .cta,.v2 .trust{justify-content:center}
  .v2 .side{max-width:640px;margin:8px auto 0;text-align:left}
  .v2 section#layanan{background:transparent;border-color:transparent}`;

  const list = s.produk.filter((p) => p.nama);
  const body = `
  <div class="wrap nav">
    <div class="logo"><i>${esc(s.nama).charAt(0)}</i>${esc(s.nama)}</div>
    <div class="nav-links"><a href="#layanan">Layanan</a><a href="#harga">Harga</a><span class="sbadge" data-status hidden></span><a class="btn" style="padding:10px 18px;font-size:12.5px" href="${s.wa ? waLink(s.wa) : "#harga"}"${s.wa ? ' target="_blank" rel="noopener"' : ""}>Konsultasi gratis</a></div>
  </div>

  <div class="wrap hero">
    <div>
      <h1>${esc(s.tagline).replace(/(\S+)$/, "<u>$1</u>")}</h1>
      <p>${esc(s.deskripsi)}</p>
      <div class="cta">
        <a class="btn" href="${s.wa ? waLink(s.wa) : "#harga"}"${s.wa ? ' target="_blank" rel="noopener"' : ""}>Minta penawaran</a>
        <a class="btn alt" href="#layanan">Lihat layanan</a>
        ${shareBtn(s)}
      </div>
      <div class="trust">
        <div><b>120+</b><small>klien terbantu</small></div>
        <div><b>4.9</b><small>rating layanan</small></div>
        <div><b>${esc(s.jam || "Senin–Sabtu")}</b><small>jam operasional</small></div>
      </div>
    </div>
    <div class="side">
      <h3>Yang kamu dapat</h3>
      ${list
        .map((p) => `<div class="chk"><i>✓</i><span><b>${esc(p.nama)}</b><br>${esc(p.ket || "")}</span></div>`)
        .join("")}
      <a class="btn" href="${s.wa ? waLink(s.wa) : "#harga"}"${s.wa ? ' target="_blank" rel="noopener"' : ""}>Hubungi kami</a>
    </div>
  </div>

  <section id="layanan" style="background:#fff;border-top:1px solid var(--line);border-bottom:1px solid var(--line)"><div class="wrap">
    <div class="shead"><h2>Layanan &amp; paket harga</h2><p>Pilih paket sesuai kebutuhan. Harga transparan, tanpa biaya tersembunyi.</p></div>
    ${list.length ? searchBar("Cari layanan...") : ""}
    <div class="cards">${list
      .map(
        (p, i) => `<a class="price-card ${i === 1 ? "hero-card" : ""}" data-prod data-nama="${esc(p.nama)}" href="${s.wa ? orderLink(s, p) : "#harga"}"${s.wa ? ' target="_blank" rel="noopener"' : ""}>
        ${prodThumb(p, "pthumb")}
        <h3>${esc(p.nama)}</h3>
        <div class="num">${rupiah(p.harga)} <small>/ paket</small></div>
        <ul>${(p.ket || "Pengerjaan cepat|Revisi 2x|Support 30 hari")
          .split("|")
          .map((x) => `<li><b>✓</b>${esc(x)}</li>`)
          .join("")}</ul>
        <span class="btn ${i === 1 ? "" : "alt"}">Ambil paket</span>
      </a>`
      )
      .join("")}</div>
  </div></section>

  <section id="harga"><div class="wrap">
    ${kontakBar(s)}
  </div></section>`;

  return doc(s, css, body);
}

/* ============================================================
   4. TOKO — retail, hero overlay + katalog
   ============================================================ */
function tplToko(s) {
  const css = `
  .nav{display:flex;align-items:center;gap:24px;padding:20px 0}
  .logo{font-weight:800;font-size:18px}
  .logo span{color:var(--b)}
  .nav .links{display:flex;gap:22px;font-size:13px;font-weight:600;color:var(--muted);margin-left:auto}
  .nav .btn{padding:10px 18px;font-size:12.5px}
  .hero{position:relative;border-radius:30px;overflow:hidden;min-height:420px;display:flex;align-items:flex-end;background:linear-gradient(140deg,color-mix(in srgb,var(--b) 78%,#111),color-mix(in srgb,var(--b) 40%,#111));padding:44px;color:#fff}
  .hero img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.42}
  .hero .inner{position:relative;max-width:560px}
  .tagline{display:inline-block;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.28);padding:6px 12px;border-radius:999px;font-size:10.5px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;backdrop-filter:blur(6px)}
  .hero h1{font-size:46px;line-height:1.08;letter-spacing:-.035em;margin:16px 0 14px;font-weight:800}
  .hero p{font-size:14.5px;line-height:1.7;color:rgba(255,255,255,.86);max-width:440px}
  .hero .btn{margin-top:24px;background:#fff;color:var(--ink)}
  .promo{display:flex;gap:14px;flex-wrap:wrap;margin:18px 0 0;padding:16px 22px;background:#fff;border:1px solid var(--line);border-radius:16px;font-size:12.5px;font-weight:700;color:var(--muted);align-items:center}
  .promo b{color:var(--b)}
  section{padding:64px 0}
  .shead{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:28px;gap:20px}
  .shead h2{font-size:32px;letter-spacing:-.03em}
  .shead a{font-size:12.5px;font-weight:700;color:var(--b)}
  .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
  .card{background:#fff;border:1px solid var(--line);border-radius:18px;overflow:hidden;transition:.2s}
  .card:hover{transform:translateY(-3px);box-shadow:0 18px 36px rgba(15,23,42,.09)}
  .card .cb{aspect-ratio:1/.92;background:linear-gradient(150deg,color-mix(in srgb,var(--b) 8%,#fff),#f8fafc);display:grid;place-items:center;font-size:40px;font-weight:800;color:color-mix(in srgb,var(--b) 32%,#fff)}
  .card .ci{padding:14px}
  .card b{font-size:13.5px;display:block;line-height:1.4}
  .card small{display:block;color:var(--muted);font-size:11.5px;margin-top:4px}
  .card .price{display:block;margin-top:9px;font-weight:800;color:var(--b);font-size:13.5px}
  .info{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
  .info div{background:#fff;border:1px solid var(--line);border-radius:18px;padding:22px}
  .info b{display:block;font-size:13.5px;margin-bottom:6px}
  .info p{font-size:12.5px;color:var(--muted);line-height:1.65}
  .contact{display:flex;gap:12px;flex-wrap:wrap;margin-top:26px}
  .contact span,.contact a{font-size:12px;font-weight:700;padding:9px 15px;border-radius:999px;background:#fff;border:1px solid var(--line);color:var(--muted)}
  .contact a.cwa{background:var(--b);color:#fff;border-color:transparent}
  .foot{border-top:1px solid var(--line);padding:24px 0;font-size:12px;color:var(--muted)}
  .fw{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap}
  @media(max-width:900px){.grid{grid-template-columns:1fr 1fr}.info{grid-template-columns:1fr}.hero{padding:28px;min-height:360px}.hero h1{font-size:32px}.nav .links{display:none}}
  .v2 .hero{min-height:360px;align-items:center;border-radius:18px}
  .v2 .hero h1{font-size:40px}
  .v2 .grid{grid-template-columns:repeat(3,1fr)}
  .v2 .card .cb{aspect-ratio:1/.7}`;

  const body = `
  <div class="wrap nav">
    <div class="logo">${esc(s.nama).split(" ")[0]}<span>${esc(s.nama).split(" ").slice(1).join(" ")}</span></div>
    <div class="links"><a href="#katalog">Katalog</a><a href="#info">Info toko</a><a href="#kontak">Kontak</a><span class="sbadge" data-status hidden></span></div>
    <a class="btn" href="${s.wa ? waLink(s.wa) : "#katalog"}"${s.wa ? ' target="_blank" rel="noopener"' : ""}>Pesan sekarang</a>
  </div>

  <div class="wrap">
    <div class="hero">
      ${imgOr(s.heroImg, "")}
      <div class="inner">
        <span class="tagline">${esc(s.kategori || "Toko online")}</span>
        <h1>${esc(s.tagline)}</h1>
        <p>${esc(s.deskripsi)}</p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:24px">
          <a class="btn" href="#katalog">Belanja sekarang</a>
          ${shareBtn(s)}
        </div>
      </div>
    </div>
    <div class="promo"><span>🚚 <b>Gratis ongkir</b> min. belanja Rp150.000</span><span>💳 Bayar QRIS / transfer</span><span>📦 Dikirim hari ini</span></div>
  </div>

  <section id="katalog"><div class="wrap">
    <div class="shead"><h2>Produk pilihan</h2><a href="#kontak">Lihat semua →</a></div>
    ${s.produk.some((p) => p.nama) ? searchBar("Cari produk...") : ""}
    <div class="grid">${s.produk
      .filter((p) => p.nama)
      .map(
        (p) => `<a class="card" data-prod data-nama="${esc(p.nama)}" href="${s.wa ? orderLink(s, p) : "#kontak"}"${s.wa ? ' target="_blank" rel="noopener"' : ""}>${prodThumb(p, "cb pthumb")}
        <div class="ci"><b>${esc(p.nama)}</b>${p.ket ? `<small>${esc(p.ket)}</small>` : ""}<span class="price">${rupiah(p.harga)}</span></div></a>`
      )
      .join("")}</div>
  </div></section>

  <section id="info" style="padding-top:0"><div class="wrap">
    <div class="info">
      <div><b>Jam buka</b><p>${esc(s.jam || "Senin – Sabtu, 08.00 – 20.00")}</p></div>
      <div><b>Lokasi</b><p>${esc(s.alamat || "Alamat toko belum diisi")}</p></div>
      <div><b>Cara pesan</b><p>Pilih produk, lalu hubungi kami lewat WhatsApp. Pembayaran QRIS atau transfer bank.</p></div>
    </div>
    <div id="kontak">${kontakBar(s)}</div>
  </div></section>`;

  return doc(s, css, body);
}

/* ============================================================
   DAFTAR TEMPLATE
   ============================================================ */
const TEMPLATES = [
  { id: "warung", name: "Warung Rasa", cat: "kuliner", blurb: "Untuk warung makan, katering, dan kuliner rumahan. Menu dan jam buka jadi pusat perhatian.", tag1: "Menu", tag2: "1 halaman", badge: "Populer", productLabel: "Menu", render: tplWarung },
  { id: "butik", name: "Butik Ayu", cat: "fashion", blurb: "Untuk butik, brand fashion, dan aksesoris. Tampilan editorial dengan katalog foto besar.", tag1: "Katalog", tag2: "1 halaman", badge: "Baru", productLabel: "Produk", render: tplButik },
  { id: "jasa", name: "Jasa Pro", cat: "jasa", blurb: "Untuk jasa servis, konsultan, dan kreatif. Paket harga dan daftar layanan langsung terlihat.", tag1: "Layanan", tag2: "Paket harga", badge: "", productLabel: "Layanan", render: tplJasa },
  { id: "toko", name: "Toko Kelontong", cat: "retail", blurb: "Untuk toko kelontong, sembako, dan retail kecil. Katalog produk rapi plus info ongkir.", tag1: "Katalog", tag2: "Info toko", badge: "", productLabel: "Produk", render: tplToko },
  { id: "warung-2", name: "Dapur Bunda", cat: "kuliner", blurb: "Varian kuliner dengan foto besar di atas. Cocok untuk kue rumahan dan frozen food.", tag1: "Menu", tag2: "Foto besar", badge: "", brand: "#d97706", productLabel: "Menu", variant: true, render: tplWarung },
  { id: "butik-2", name: "Galeri Loka", cat: "fashion", blurb: "Untuk produk kerajinan dan fashion lokal. Katalog empat kolom yang ringkas.", tag1: "Katalog", tag2: "Cerita brand", badge: "", brand: "#7c3aed", productLabel: "Produk", variant: true, render: tplButik },
  { id: "jasa-2", name: "Bengkel Sigap", cat: "jasa", blurb: "Untuk bengkel, laundry, dan servis harian. Fokus ke kecepatan dan kontak langsung.", tag1: "Layanan", tag2: "Paket harga", badge: "", brand: "#0d9488", productLabel: "Layanan", variant: true, render: tplJasa },
  { id: "toko-2", name: "Sembako Jaya", cat: "retail", blurb: "Untuk sembako dan kebutuhan harian. Daftar harga jelas, pesan lewat WhatsApp.", tag1: "Katalog", tag2: "Info toko", badge: "", brand: "#15803d", productLabel: "Produk", variant: true, render: tplToko },
];
