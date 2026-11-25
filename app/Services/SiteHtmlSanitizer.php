<?php

namespace App\Services;

use Dom\Element;
use Dom\HTMLDocument;

class SiteHtmlSanitizer
{
    /**
     * Canonical client script matching public/studio/templates.js byte-for-byte.
     */
    public const string CLIENT_JS = <<<'JS'

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

JS;

    /**
     * Elements completely stripped from uploaded HTML before appending canonical CLIENT_JS.
     *
     * @var array<int, string>
     */
    private const array BLOCKED_TAGS = [
        'script',
        'iframe',
        'object',
        'embed',
        'base',
        'template',
        'applet',
        'frame',
        'frameset',
    ];

    /**
     * Attributes whose URI values must be validated against dangerous schemes.
     *
     * @var array<int, string>
     */
    private const array URI_ATTRIBUTES = [
        'href',
        'src',
        'action',
        'formaction',
        'xlink:href',
        'data',
        'poster',
        'background',
    ];

    /**
     * Strip dangerous XSS vectors from HTML and inject the single canonical CLIENT_JS script.
     */
    public static function sanitize(string $html): string
    {
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);

        foreach (self::BLOCKED_TAGS as $tag) {
            foreach (iterator_to_array($document->getElementsByTagNameNS('*', $tag)) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        foreach (iterator_to_array($document->getElementsByTagNameNS('*', 'meta')) as $meta) {
            if ($meta instanceof Element && $meta->hasAttribute('http-equiv')) {
                $meta->parentNode?->removeChild($meta);
            }
        }

        foreach ($document->getElementsByTagNameNS('*', '*') as $element) {
            self::sanitizeElementAttributes($element);
        }

        $script = $document->createElement('script');
        $script->textContent = self::CLIENT_JS;

        $targetBody = $document->body;

        if ($targetBody === null) {
            $targetBody = $document->createElement('body');
            $document->documentElement?->appendChild($targetBody);
        }

        $targetBody->appendChild($script);

        return $document->saveHtml();
    }

    /**
     * Compute the base64-encoded SHA-256 CSP source hash for CLIENT_JS.
     */
    public static function cspScriptHash(): string
    {
        return 'sha256-'.base64_encode(hash('sha256', self::CLIENT_JS, true));
    }

    /**
     * Build the strict Content-Security-Policy header for published public microsites.
     */
    public static function cspHeader(): string
    {
        return "default-src 'self'; script-src '".self::cspScriptHash()."'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self';";
    }

    /**
     * Remove inline event handlers, srcdoc, and dangerous URI/style attributes from an element.
     */
    private static function sanitizeElementAttributes(Element $element): void
    {
        $toRemove = [];

        foreach ($element->attributes as $attribute) {
            $attrName = strtolower($attribute->name);

            if (str_starts_with($attrName, 'on') || $attrName === 'srcdoc') {
                $toRemove[] = $attribute->name;

                continue;
            }

            if ($attrName === 'style' && self::isUnsafeStyleValue($attribute->value)) {
                $toRemove[] = $attribute->name;

                continue;
            }

            if (in_array($attrName, self::URI_ATTRIBUTES, true) && self::isUnsafeUri($attribute->value, $attrName)) {
                $toRemove[] = $attribute->name;
            }
        }

        foreach ($toRemove as $attributeName) {
            $element->removeAttribute($attributeName);
        }
    }

    /**
     * Determine whether an attribute URI uses a dangerous scheme such as javascript: or vbscript:.
     */
    private static function isUnsafeUri(string $value, string $attributeName): bool
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = preg_replace('/[\x00-\x20]+/', '', strtolower($decoded)) ?? '';

        if (str_starts_with($normalized, 'javascript:') || str_starts_with($normalized, 'vbscript:')) {
            return true;
        }

        if (str_starts_with($normalized, 'data:')) {
            if (in_array($attributeName, ['src', 'poster'], true)) {
                return preg_match('/^data:image\/(png|jpeg|jpg|gif|webp|avif)[;,]/', $normalized) !== 1;
            }

            return true;
        }

        return false;
    }

    /**
     * Determine whether an inline style attribute contains script-execution constructs.
     */
    private static function isUnsafeStyleValue(string $value): bool
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = preg_replace('/[\x00-\x20]+/', '', strtolower($decoded)) ?? '';

        return str_contains($normalized, 'javascript:')
            || str_contains($normalized, 'vbscript:')
            || str_contains($normalized, 'expression(');
    }
}
