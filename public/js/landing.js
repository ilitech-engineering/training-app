(() => {
  const $ = s => document.querySelector(s), $$ = s => [...document.querySelectorAll(s)];
  const cd = $('[data-cd]');
  if (cd) { const t = new Date(cd.dataset.cd), b = cd.querySelectorAll('b'), p = n => String(n).padStart(2, '0');
    (function k() { const d = Math.max(0, t - Date.now());
      [Math.floor(d / 864e5), Math.floor(d / 36e5) % 24, Math.floor(d / 6e4) % 60, Math.floor(d / 1e3) % 60].forEach((v, i) => b[i].textContent = p(v));
      setTimeout(k, 1000); })(); }
  const f = $('#f');
  if (f) {
    const upd = () => {
      const s = f.querySelector('[name=course_session_id]:checked'); let tot = s ? +s.dataset.price : 0; const free = tot === 0;
      f.querySelectorAll('.paid-only').forEach(e => e.hidden = free); f.querySelector('.tot').hidden = free;
      if (!free) f.querySelectorAll('[name="addons[]"]:checked').forEach(a => tot += +a.dataset.price);
      $('#total').textContent = tot.toLocaleString('fr-FR').replace(/\s/g, ' ');
    };
    f.addEventListener('change', upd); upd();
    f.addEventListener('submit', () => { const b = f.querySelector('button[type=submit]'); b.disabled = true; setTimeout(() => b.disabled = false, 8000); });
  }
  $$('.copy').forEach(b => b.onclick = () => navigator.clipboard && navigator.clipboard.writeText(document.getElementById(b.dataset.c).textContent.trim()));
})();
