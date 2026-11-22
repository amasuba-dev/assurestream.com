/* Qextec language menu: a globe button that opens a list of languages, each in its own name.
 * Markup: <div class="qxg" data-langs="en,pt,fr" [data-theme="light"] [data-item-act="lang"]></div>
 * Each item is <button data-l="xx">; clicks bubble to the host page, which changes the language.
 * The button label follows <html lang>, so the host only needs to set document.documentElement.lang. */
(function () {
  const NAMES = { en: 'English', pt: 'Português', fr: 'Français', es: 'Español', ar: 'العربية', zh: '中文', sw: 'Kiswahili', de: 'Deutsch', hi: 'हिन्दी', ru: 'Русский', ja: '日本語', it: 'Italiano', tr: 'Türkçe', am: 'አማርኛ', ha: 'Hausa', yo: 'Yorùbá', zu: 'isiZulu' };
  const GLOBE = '<svg width="20" height="20" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true"><path d="M16 2a14 14 0 1 0 14 14A14 14 0 0 0 16 2Zm12 13h-6a24.3 24.3 0 0 0-2.2-10.1A12 12 0 0 1 28 15ZM16 28a5 5 0 0 1-.7 0A21.9 21.9 0 0 1 12 17h8a21.9 21.9 0 0 1-3.3 11 5 5 0 0 1-.7 0Zm-4-13a21.9 21.9 0 0 1 3.3-11 5 5 0 0 1 1.4 0A21.9 21.9 0 0 1 20 15Zm.2-10.1A24.3 24.3 0 0 0 10 15H4a12 12 0 0 1 8.2-10.1ZM4 17h6a24.3 24.3 0 0 0 2.2 10.1A12 12 0 0 1 4 17Zm15.8 10.1A24.3 24.3 0 0 0 22 17h6a12 12 0 0 1-8.2 10.1Z"/></svg>';
  const CHEV = '<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 11 3 6l.7-.7L8 9.6l4.3-4.3.7.7z"/></svg>';
  const CHECK = '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="m6.5 12-4-4 .7-.7 3.3 3.3 6.3-6.3.7.7z"/></svg>';
  const css = `.qxg{position:relative;display:inline-flex;margin-right:8px;font-family:inherit}
.qxg>.qxg-b{display:inline-flex;align-items:center;gap:8px;height:48px;padding:0 12px;background:transparent;border:0;color:#c6c6c6;cursor:pointer;font:inherit;font-size:14px}
.qxg>.qxg-b:hover,.qxg.open>.qxg-b{background:#262626;color:#fff}
.qxg>.qxg-b:focus-visible{outline:2px solid #fff;outline-offset:-2px}
.qxg[data-size=sm]>.qxg-b{height:32px}
.qxg[data-theme=light]>.qxg-b{color:#525252;border:1px solid #c6c6c6}.qxg[data-theme=light]>.qxg-b:hover,.qxg[data-theme=light].open>.qxg-b{background:#e8e8e8;color:#161616}
.qxg[data-theme=light]>.qxg-b:focus-visible{outline-color:#0f62fe}
.qxg-m{position:absolute;top:100%;right:0;min-width:208px;max-height:70vh;overflow:auto;background:#262626;border:1px solid #393939;box-shadow:0 2px 6px rgba(0,0,0,.3);padding:4px 0;z-index:1000;display:none}
.qxg.open .qxg-m{display:block}
.qxg-m button{display:flex;align-items:center;justify-content:space-between;gap:16px;width:100%;padding:10px 16px;background:none;border:0;color:#c6c6c6;font:inherit;font-size:14px;text-align:start;cursor:pointer}
.qxg-m button:hover,.qxg-m button:focus-visible{background:#393939;color:#fff;outline:none}
.qxg-m button[aria-checked=true]{color:#fff}.qxg-m button svg{visibility:hidden}.qxg-m button[aria-checked=true] svg{visibility:visible}
.qxg-m button small{color:#8d8d8d;font-size:12px;margin-inline-start:auto}
.qxg[data-theme=light] .qxg-m{background:#fff;border-color:#e0e0e0}.qxg[data-theme=light] .qxg-m button{color:#525252}.qxg[data-theme=light] .qxg-m button:hover,.qxg[data-theme=light] .qxg-m button:focus-visible{background:#e8e8e8;color:#161616}.qxg[data-theme=light] .qxg-m button[aria-checked=true]{color:#161616}
@media (max-width:672px){.qxg .qxg-n{display:none}}`;
  const st = document.createElement('style'); st.textContent = css; document.head.appendChild(st);
  const cur = () => (document.documentElement.lang || 'en').slice(0, 2);
  let n = 0;
  function mount(el) {
    if (el.dataset.qxgMounted) return; el.dataset.qxgMounted = '1';
    const langs = (el.dataset.langs || 'en').split(',').map(s => s.trim()).filter(Boolean), id = 'qxg-m' + (++n), act = el.dataset.itemAct;
    el.innerHTML = `<button type="button" class="qxg-b" aria-haspopup="menu" aria-expanded="false" aria-controls="${id}" aria-label="Language">${GLOBE}<span class="qxg-n"></span>${CHEV}</button><div class="qxg-m" role="menu" id="${id}">${langs.map(l => `<button type="button" role="menuitemradio" data-l="${l}" lang="${l}" dir="${l === 'ar' ? 'rtl' : 'ltr'}"${act ? ` data-act="${act}"` : ''}>${NAMES[l] || l}${CHECK}</button>`).join('')}</div>`;
    const b = el.firstChild, m = el.lastChild, items = [...m.children];
    const sync = () => { const c = cur(); b.querySelector('.qxg-n').textContent = NAMES[c] || c.toUpperCase(); items.forEach(i => i.setAttribute('aria-checked', i.dataset.l === c)); };
    const close = f => { el.classList.remove('open'); b.setAttribute('aria-expanded', 'false'); if (f) b.focus(); };
    const open = () => { el.classList.add('open'); b.setAttribute('aria-expanded', 'true'); (items.find(i => i.getAttribute('aria-checked') === 'true') || items[0]).focus(); };
    b.addEventListener('click', e => { e.stopPropagation(); el.classList.contains('open') ? close() : open(); });
    b.addEventListener('keydown', e => { if (e.key === 'ArrowDown') { e.preventDefault(); open(); } });
    m.addEventListener('click', e => { const i = e.target.closest('[data-l]'); if (!i) return; document.documentElement.lang = i.dataset.l; sync(); close(true); });
    m.addEventListener('keydown', e => { const k = items.indexOf(document.activeElement); if (e.key === 'Escape') { e.preventDefault(); close(true); } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); items[(k + (e.key === 'ArrowDown' ? 1 : items.length - 1)) % items.length].focus(); } else if (e.key === 'Tab') close(); });
    document.addEventListener('click', e => { if (!el.contains(e.target)) close(); });
    new MutationObserver(sync).observe(document.documentElement, { attributes: true, attributeFilter: ['lang'] });
    sync();
  }
  const scan = () => document.querySelectorAll('.qxg:not([data-qxg-mounted])').forEach(mount);
  window.QXGlobe = { mount, scan, names: NAMES };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan); else scan();
  new MutationObserver(scan).observe(document.documentElement, { childList: true, subtree: true });
})();
