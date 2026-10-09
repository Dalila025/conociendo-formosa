/* =========================================================
   Conociendo Formosa · Utilidades compartidas
   Desarrollado por Dalila Spindola Dev
   ========================================================= */
window.App = {
  data: { departamentos: [], lugares: [], categorias: {} },
  csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
};

/** Escapa HTML */
App.esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

/** Normaliza texto para búsquedas (sin tildes, minúsculas) */
App.norm = (s) => String(s ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

/** fetch JSON con manejo de errores */
App.api = async (url, opts = {}) => {
  const res = await fetch(url, {
    headers: { 'Accept': 'application/json', ...(opts.body ? { 'Content-Type': 'application/json' } : {}) },
    ...opts,
  });
  let json;
  try { json = await res.json(); } catch { throw new Error('Respuesta inválida del servidor'); }
  if (!res.ok || json.ok === false) throw new Error(json.error || 'Ocurrió un error');
  return json;
};

/** Toast simple */
App.toast = (msg, type = 'ok') => {
  const zone = document.getElementById('toastZone');
  const el = document.createElement('div');
  el.className = `toast-msg ${type}`;
  el.innerHTML = `<i class="fa-solid ${type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'}"></i><span>${App.esc(msg)}</span>`;
  zone.appendChild(el);
  setTimeout(() => { el.classList.add('out'); setTimeout(() => el.remove(), 400); }, 3800);
};

App.depto = (id) => App.data.departamentos.find((d) => +d.id === +id) || {};
App.cat = (k) => App.data.categorias[k] || { nombre: k, color: '#1F9E89', icono: 'fa-location-dot' };
App.lugar = (id) => App.data.lugares.find((l) => +l.id === +id);

/** Primera imagen de un lugar (o null) */
App.img = (l) => (l && l.imagenes && l.imagenes.length ? l.imagenes[0].url : null);

/**
 * Atributos de fondo para un elemento: imagen real o un placeholder con el color del departamento.
 * Devuelve { style, cls, inner }
 */
App.bg = (l) => {
  const url = App.img(l);
  const color = App.depto(l.departamento_id).color || '#1F9E89';
  if (url) return { style: `background-image:url('${App.esc(url)}');--c:${color}`, cls: '', inner: '' };
  const ic = App.cat(l.categoria).icono;
  return { style: `--c:${color}`, cls: 'ph', inner: `<i class="fa-solid ${ic} ph-icon"></i>` };
};

App.badge = (cat) => {
  const c = App.cat(cat);
  return `<span class="badge-cat" style="--c:${c.color}"><i class="fa-solid ${c.icono}"></i> ${c.nombre}</span>`;
};

App.stars = (n) => {
  let s = '';
  for (let i = 1; i <= 5; i++) {
    s += n >= i ? '<i class="fa-solid fa-star"></i>' : (n >= i - .5 ? '<i class="fa-solid fa-star-half-stroke"></i>' : '<i class="fa-regular fa-star"></i>');
  }
  return s;
};

/* ---------------- Lightbox ---------------- */
App.lightbox = (() => {
  const box = document.getElementById('lightbox');
  const img = box.querySelector('img');
  const cap = box.querySelector('figcaption');
  let items = [], idx = 0;
  const show = () => {
    const it = items[idx];
    img.src = it.url; img.alt = it.titulo || '';
    cap.innerHTML = `${App.esc(it.titulo || '')}<small>${App.esc(it.sub || it.credito || '')}</small>`;
  };
  const open = (list, i = 0) => { items = list; idx = i; show(); box.hidden = false; document.body.classList.add('no-scroll'); };
  const close = () => { box.hidden = true; document.body.classList.remove('no-scroll'); };
  const move = (d) => { idx = (idx + d + items.length) % items.length; show(); };
  box.querySelector('.lb-close').onclick = close;
  box.querySelector('.lb-prev').onclick = () => move(-1);
  box.querySelector('.lb-next').onclick = () => move(1);
  box.addEventListener('click', (e) => { if (e.target === box) close(); });
  document.addEventListener('keydown', (e) => {
    if (box.hidden) return;
    if (e.key === 'Escape') close();
    if (e.key === 'ArrowLeft') move(-1);
    if (e.key === 'ArrowRight') move(1);
  });
  return { open };
})();

/* ---------------- Ficha del lugar (se abre en otra pestaña) ---------------- */
App.urlLugar = (l, tab = '') => `lugar.php?l=${encodeURIComponent(l.slug)}${tab ? '#' + tab : ''}`;
/* La ficha se abre en una ventana flotante sobre el mapa (misma pestaña).
   Se cierra con la X, la tecla Esc, tocando afuera o con el botón «Atrás» del navegador. */
App._ficha = { enHistorial: false, esperandoBack: null, scrollY: 0 };

App.abrirLugar = async (id, tab = '') => {
  const l = App.lugar(id);
  if (!l) return;
  const win = document.getElementById('fichaWindow');
  if (!win) { location.href = App.urlLugar(l, tab); return; }
  const F = App._ficha;
  if (F.esperandoBack) await F.esperandoBack;          // esperar a que termine un cierre anterior
  const frame = win.querySelector('iframe');
  win.querySelector('.fw-title').textContent = l.nombre;
  win.querySelector('.fw-dot').style.background = App.cat(l.categoria).color;
  win.classList.add('loading');
  // location.replace: carga la ficha sin sumar entradas al historial del navegador
  frame.contentWindow.location.replace(`lugar.php?l=${encodeURIComponent(l.slug)}&embed=1${tab ? '#' + tab : ''}`);
  if (win.hidden) {
    F.scrollY = scrollY;
    win.hidden = false;
    requestAnimationFrame(() => win.classList.add('open'));
    document.body.classList.add('no-scroll');
  }
  if (!F.enHistorial) { history.pushState({ ficha: true }, '', location.pathname + location.search + '#lugar-' + l.slug); F.enHistorial = true; }
  else history.replaceState({ ficha: true }, '', location.pathname + location.search + '#lugar-' + l.slug);
  fetch(`api/lugares.php?id=${l.id}`).catch(() => {}); // suma visita
};

function ocultarFicha() {
  const win = document.getElementById('fichaWindow');
  if (!win || win.hidden) return;
  win.classList.remove('open');
  document.body.classList.remove('no-scroll');
  scrollTo({ top: App._ficha.scrollY, behavior: 'instant' });   // volver exactamente donde estaba
  setTimeout(() => { if (!win.classList.contains('open')) { win.hidden = true; win.querySelector('iframe').contentWindow.location.replace('about:blank'); } }, 300);
}

App.cerrarFicha = () => {
  const F = App._ficha;
  ocultarFicha();
  if (F.enHistorial) {
    F.enHistorial = false;
    // Sacamos la entrada del historial; el popstate resultante se ignora
    F.esperandoBack = new Promise((ok) => { F._resolver = ok; });
    history.back();
  }
};

/** Botones de la ficha que actúan sobre el mapa: cierran la ventana y luego hacen la acción */
App.accionDesdeFicha = (accion, id) => {
  App.cerrarFicha();
  setTimeout(() => (accion === 'ver' ? App.mapa.irA(id) : App.resenas.preseleccionar(id)), 350);
};

(() => {
  const win = document.getElementById('fichaWindow');
  if (!win) return;
  const F = App._ficha;
  win.querySelector('.fw-close').addEventListener('click', () => App.cerrarFicha());
  win.addEventListener('click', (e) => { if (e.target === win) App.cerrarFicha(); });
  win.querySelector('iframe').addEventListener('load', () => win.classList.remove('loading'));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !win.hidden) App.cerrarFicha(); });
  addEventListener('popstate', () => {
    if (F.esperandoBack) { const r = F._resolver; F.esperandoBack = null; r && r(); return; }  // fue nuestro propio back
    if (F.enHistorial) { F.enHistorial = false; ocultarFicha(); }                               // botón «Atrás» del navegador
  });
})();
