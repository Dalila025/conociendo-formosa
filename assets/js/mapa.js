/* =========================================================
   Conociendo Formosa · Mapa interactivo
   Dos vistas: «Mi mapa» (ilustrado, SVG) y «Mapa real» (Leaflet: satélite, relieve, calles).
   Cada departamento es una forma del SVG; los lugares son marcadores
   posicionados en % sobre el mismo lienzo (map_x / map_y).
   ========================================================= */
App.mapa = (() => {
  const state = { cat: 'todo', depto: 0, q: '', activo: null };
  const view = { z: 1, tx: 0, ty: 0, fit: 1 };
  let stage, canvas, svg, VB, markersBox, tip, drag = null, wheelOn = false;
  let modo = 'ilustrado';           // 'ilustrado' (mi mapa) | 'real' (satélite / relieve)
  let R = null; const rMk = {};      // mapa Leaflet y sus marcadores
  const bySlug = {};

  const $ = (s) => document.querySelector(s);

  /* ---------- Vista (zoom / desplazamiento) ---------- */
  function aplicar(animar = true) {
    canvas.style.transition = animar ? 'transform .8s cubic-bezier(.22,1,.36,1)' : 'none';
    canvas.style.transform = `translate(${view.tx}px, ${view.ty}px) scale(${view.z})`;
    canvas.style.setProperty('--z', view.z);
    stage.classList.toggle('is-zoomed', view.z > view.fit * 1.05);
  }
  function encuadrar(bx, by, bw, bh, margen = 0.78, animar = true) {
    const SW = stage.clientWidth, SH = stage.clientHeight;
    view.z = Math.min(SW * margen / bw, SH * margen / bh, view.fit * 5);
    view.tx = SW / 2 - (bx + bw / 2) * view.z;
    view.ty = SH / 2 - (by + bh / 2) * view.z;
    aplicar(animar);
  }
  function verTodo(animar = true) {
    const SW = stage.clientWidth, SH = stage.clientHeight;
    view.fit = Math.min(SW / VB.w, SH / VB.h) * 0.94;
    encuadrar(0, 0, VB.w, VB.h, 0.94, animar);
  }
  function encuadreGeneral(animar = true) { modo === 'real' ? realVerTodo() : verTodo(animar); }
  function zoomEn(factor, cx, cy) {
    const SW = stage.clientWidth, SH = stage.clientHeight;
    cx = cx ?? SW / 2; cy = cy ?? SH / 2;
    const z = Math.max(view.fit * 0.9, Math.min(view.fit * 6, view.z * factor));
    view.tx = cx - (cx - view.tx) * (z / view.z);
    view.ty = cy - (cy - view.ty) * (z / view.z);
    view.z = z; aplicar();
  }
  function bboxDepto(slug) {
    const b = svg.querySelector(`#d-${slug}`).getBBox();
    return [b.x - VB.x, b.y - VB.y, b.width, b.height];
  }

  /* ---------- Marcadores ---------- */
  function posicion(l) {
    if (l.map_x != null && l.map_y != null) return [l.map_x, l.map_y];
    // Sin posición cargada: centro del departamento
    const d = App.depto(l.departamento_id);
    const p = svg.querySelector(`#d-${d.slug}`);
    if (!p) return [50, 50];
    const b = p.getBBox();
    return [((b.x + b.width / 2) - VB.x) / VB.w * 100, ((b.y + b.height / 2) - VB.y) / VB.h * 100];
  }

  function crearMarcadores() {
    markersBox.innerHTML = App.data.lugares.map((l) => {
      const c = App.cat(l.categoria); const [x, y] = posicion(l);
      return `<button class="mk" data-id="${l.id}" style="left:${x}%;top:${y}%;--c:${c.color}" aria-label="${App.esc(l.nombre)}">
        <span class="mk-pulse"></span><span class="mk-pin"></span><i class="fa-solid ${c.icono}"></i></button>`;
    }).join('');
    markersBox.querySelectorAll('.mk').forEach((m) => {
      const l = App.lugar(m.dataset.id);
      m.addEventListener('click', (e) => { e.stopPropagation(); seleccionar(l.id, false); });
      m.addEventListener('mouseenter', () => mostrarTip(`<strong>${App.esc(l.nombre)}</strong><small>${App.esc(App.depto(l.departamento_id).nombre)} · ${App.cat(l.categoria).nombre}</small>`, m));
      m.addEventListener('mouseleave', ocultarTip);
    });
  }

  function mostrarTip(html, el) {
    tip.innerHTML = html; tip.hidden = false;
    const r = el.getBoundingClientRect(), s = stage.getBoundingClientRect();
    tip.style.left = `${r.left + r.width / 2 - s.left}px`;
    tip.style.top = `${r.top - s.top - 8}px`;
  }
  function ocultarTip() { tip.hidden = true; }

  /* ---------- Inicio ---------- */
  function init() {
    stage = $('#mapStage'); canvas = $('#mapCanvas'); markersBox = $('#mapMarkers'); tip = $('#mapTip');
    svg = canvas.querySelector('svg');
    const vb = svg.viewBox.baseVal; VB = { x: vb.x, y: vb.y, w: vb.width, h: vb.height };
    canvas.style.width = `${VB.w}px`; canvas.style.height = `${VB.h}px`;
    App.data.departamentos.forEach((d) => { bySlug[d.slug] = d; });

    // Departamentos del SVG
    svg.querySelectorAll('.depto-shape').forEach((p) => {
      const d = bySlug[p.dataset.depto];
      if (!d) return;
      p.addEventListener('mouseenter', () => {
        p.classList.add('hover');
        svg.querySelector(`.map-labels text[data-depto="${d.slug}"]`)?.classList.add('hover');
        mostrarTip(`<strong>${App.esc(d.nombre)}</strong><small>${d.total} lugares · Cabecera: ${App.esc(d.cabecera || '')}</small>`, p);
      });
      p.addEventListener('mousemove', (e) => {
        const s = stage.getBoundingClientRect();
        tip.style.left = `${e.clientX - s.left}px`; tip.style.top = `${e.clientY - s.top - 14}px`;
      });
      p.addEventListener('mouseleave', () => {
        p.classList.remove('hover');
        svg.querySelector(`.map-labels text[data-depto="${d.slug}"]`)?.classList.remove('hover');
        ocultarTip();
      });
      p.addEventListener('click', (e) => {
        if (drag && drag.movido) return;
        e.stopPropagation();
        filtrarDepto(+d.id === state.depto ? 0 : +d.id);
      });
    });

    crearMarcadores();
    verTodo(false);
    addEventListener('resize', () => { if (modo === 'ilustrado') state.depto ? filtrarDepto(state.depto, true) : verTodo(false); });

    // Controles
    $('#mapZoomIn').addEventListener('click', () => zoomEn(1.4));
    $('#mapZoomOut').addEventListener('click', () => zoomEn(1 / 1.4));
    $('#mapZoomReset').addEventListener('click', () => filtrarDepto(0));

    // Arrastrar para mover
    stage.addEventListener('pointerdown', (e) => {
      if (e.target.closest('.mk')) return;
      drag = { x: e.clientX, y: e.clientY, tx: view.tx, ty: view.ty, movido: false };
    });
    addEventListener('pointermove', (e) => {
      if (!drag) return;
      const dx = e.clientX - drag.x, dy = e.clientY - drag.y;
      if (!drag.movido && Math.hypot(dx, dy) < 5) return;
      drag.movido = true; stage.classList.add('dragging');
      view.tx = drag.tx + dx; view.ty = drag.ty + dy; aplicar(false);
    });
    addEventListener('pointerup', () => {
      if (!drag) return;
      stage.classList.remove('dragging');
      const fue = drag.movido;
      setTimeout(() => { drag = null; }, 0);
      if (!fue) return;
    });

    // Zoom con la rueda solo después de un clic en el mapa (para no trabar el scroll de la página)
    stage.addEventListener('click', (e) => {
      wheelOn = true; stage.classList.add('wheel-on');
      if (!e.target.closest('.depto-shape, .mk') && !(drag && drag.movido)) cerrarPreview();
    });
    stage.addEventListener('mouseleave', () => { wheelOn = false; stage.classList.remove('wheel-on'); });
    stage.addEventListener('wheel', (e) => {
      if (!wheelOn) return;
      e.preventDefault();
      const s = stage.getBoundingClientRect();
      zoomEn(e.deltaY < 0 ? 1.15 : 1 / 1.15, e.clientX - s.left, e.clientY - s.top);
    }, { passive: false });

    // Chips de departamentos
    const chips = $('#deptoChips');
    chips.insertAdjacentHTML('beforeend', App.data.departamentos.map((d) =>
      `<button class="chip chip-depto" data-depto="${d.id}" style="--c:${d.color}"><span class="dot"></span>${App.esc(d.nombre)}</button>`).join(''));
    chips.addEventListener('click', (e) => { const b = e.target.closest('[data-depto]'); if (b) filtrarDepto(+b.dataset.depto); });

    // Selector «¿Qué querés conocer?»
    document.querySelectorAll('.interest-card').forEach((b) => b.addEventListener('click', () => elegirCategoria(b.dataset.cat)));

    const search = $('#mapSearch');
    let t; search.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => { state.q = App.norm(search.value.trim()); render(); }, 180); });

    $('#btnResetMapa').addEventListener('click', reset);
    $('#btnTogglePanel').addEventListener('click', () => $('#mapPanel').classList.toggle('open'));
    $('#mapList').addEventListener('click', (e) => {
      const li = e.target.closest('[data-id]'); if (!li) return;
      seleccionar(+li.dataset.id, true);
      $('#mapPanel').classList.remove('open');
    });

    const prev = $('#mapPreview');
    prev.querySelector('.map-preview-close').addEventListener('click', cerrarPreview);
    prev.querySelector('[data-action="ver"]').addEventListener('click', () => App.abrirLugar(state.activo));
    prev.querySelector('[data-action="jugar"]').addEventListener('click', () => App.abrirLugar(state.activo, 'juego'));

    // Selector «Mi mapa / Mapa real»
    document.querySelectorAll('.map-mode [data-modo]').forEach((b) => b.addEventListener('click', () => cambiarModo(b.dataset.modo)));

    render();
    let guardado = null; try { guardado = localStorage.getItem('mapaModo'); } catch (e) { /* sin almacenamiento */ }
    if (guardado === 'real') cambiarModo('real', false);
  }

  /* ---------- Mapa real (Leaflet: satélite, relieve y calles) ---------- */
  function cargarLeaflet() {
    if (window.L) return Promise.resolve();
    return new Promise((ok, mal) => {
      const css = document.createElement('link');
      css.rel = 'stylesheet'; css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
      document.head.appendChild(css);
      const js = document.createElement('script');
      js.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
      js.onload = ok; js.onerror = mal;
      document.head.appendChild(js);
    });
  }

  function iniciarReal() {
    R = L.map('mapReal', { scrollWheelZoom: false, zoomSnap: 0.5, minZoom: 5 });
    const esri = 'https://server.arcgisonline.com/ArcGIS/rest/services/';
    const satelite = L.layerGroup([
      L.tileLayer(esri + 'World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18, attribution: 'Imágenes © Esri, Maxar, Earthstar Geographics' }),
      L.tileLayer(esri + 'Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18 }),
    ]);
    const relieve = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', { maxZoom: 17, attribution: '© OpenStreetMap · SRTM · estilo © OpenTopoMap (CC-BY-SA)' });
    const calles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' });
    satelite.addTo(R);
    L.control.layers({ '<i class="fa-solid fa-satellite"></i> Satélite': satelite, '<i class="fa-solid fa-mountain"></i> Relieve': relieve, '<i class="fa-solid fa-road"></i> Calles': calles },
      null, { position: 'bottomright', collapsed: innerWidth < 768 }).addTo(R);
    L.control.scale({ imperial: false, position: 'bottomright' }).addTo(R);

    // Rueda del mouse solo después de un clic (igual que en «Mi mapa»)
    R.on('click', () => { R.scrollWheelZoom.enable(); cerrarPreview(); });
    R.getContainer().addEventListener('mouseleave', () => R.scrollWheelZoom.disable());

    App.data.lugares.forEach((l) => {
      if (!l.lat || !l.lng) return;
      const c = App.cat(l.categoria);
      const icon = L.divIcon({
        className: 'rmk', iconSize: [34, 34], iconAnchor: [17, 34],
        html: `<span class="mk" style="--c:${c.color}"><span class="mk-pulse"></span><span class="mk-pin"></span><i class="fa-solid ${c.icono}"></i></span>`,
      });
      const m = L.marker([l.lat, l.lng], { icon, riseOnHover: true })
        .bindTooltip(`<strong>${App.esc(l.nombre)}</strong><br><small>${App.esc(App.depto(l.departamento_id).nombre)} · ${c.nombre}</small>`,
          { className: 'mk-tip', direction: 'top', offset: [0, -34] })
        .on('click', () => seleccionar(l.id, false));
      m.addTo(R); rMk[l.id] = m;
    });
  }

  function realBounds(lista) {
    const pts = lista.filter((l) => l.lat && l.lng).map((l) => [l.lat, l.lng]);
    return pts.length ? L.latLngBounds(pts) : L.latLngBounds([[-27.0, -62.4], [-22.3, -57.6]]);
  }
  function realVerTodo() {
    if (!R) return;
    R.flyToBounds(realBounds(App.data.lugares), { padding: [40, 40], duration: .8 });
  }
  function realDepto(id) {
    if (!R) return;
    R.flyToBounds(realBounds(App.data.lugares.filter((l) => +l.departamento_id === id)), { padding: [70, 70], maxZoom: 11, duration: .8 });
  }
  function realActualizar(ids, base) {
    if (!R) return;
    Object.entries(rMk).forEach(([id, m]) => {
      id = +id;
      if (!base.has(id)) { if (R.hasLayer(m)) m.remove(); return; }
      if (!R.hasLayer(m)) m.addTo(R);
      const el = m.getElement()?.querySelector('.mk');
      if (!el) return;
      el.classList.toggle('faded', !ids.has(id));
      el.classList.toggle('active', state.activo === id);
      m.setZIndexOffset(state.activo === id ? 1000 : (ids.has(id) ? 100 : 0));
    });
  }

  async function cambiarModo(nuevo, avisar = true) {
    const wrap = document.querySelector('.map-wrap'), real = $('#mapReal');
    const marcar = (m) => document.querySelectorAll('.map-mode [data-modo]').forEach((b) => {
      const on = b.dataset.modo === m; b.classList.toggle('active', on); b.setAttribute('aria-pressed', on);
    });
    if (nuevo === 'real') {
      marcar('real'); wrap.classList.add('cargando-real');
      try { await cargarLeaflet(); } catch (e) {
        wrap.classList.remove('cargando-real'); marcar('ilustrado');
        App.toast('No se pudo cargar el mapa real. Revisá tu conexión.', 'error'); return;
      }
      modo = 'real'; wrap.classList.remove('cargando-real'); wrap.classList.add('modo-real');
      stage.hidden = true; real.hidden = false; ocultarTip();
      if (!R) iniciarReal();
      R.invalidateSize();
      render();
      if (state.depto) realDepto(state.depto); else R.fitBounds(realBounds(App.data.lugares), { padding: [40, 40] });
      if (state.activo) { const l = App.lugar(state.activo); if (l && l.lat) R.setView([l.lat, l.lng], Math.max(R.getZoom(), 11)); }
      if (avisar) App.toast('Mapa real: elegí Satélite, Relieve o Calles abajo a la derecha');
    } else {
      modo = 'ilustrado'; marcar('ilustrado');
      wrap.classList.remove('modo-real'); real.hidden = true; stage.hidden = false;
      render();
      state.depto ? filtrarDepto(state.depto, true) : verTodo(false);
    }
    try { localStorage.setItem('mapaModo', modo); } catch (e) { /* sin almacenamiento */ }
  }

  /* ---------- Filtros y lista ---------- */
  function visibles(ignorarCategoria = false) {
    return App.data.lugares.filter((l) => {
      if (!ignorarCategoria && state.cat !== 'todo' && l.categoria !== state.cat) return false;
      if (state.depto && +l.departamento_id !== state.depto) return false;
      if (state.q) {
        const d = App.depto(l.departamento_id);
        const hay = App.norm(`${l.nombre} ${l.resumen} ${l.descripcion} ${l.habitantes || ''} ${l.importancia || ''} ${d.nombre} ${d.cabecera} ${App.cat(l.categoria).nombre}`);
        if (!state.q.split(/\s+/).every((w) => hay.includes(w))) return false;
      }
      return true;
    });
  }

  function render() {
    const vis = visibles(), ids = new Set(vis.map((l) => l.id));
    const base = new Set(visibles(true).map((l) => l.id));
    // Fuera del depto/búsqueda: se ocultan. De otro color: quedan atenuados como referencia.
    markersBox.querySelectorAll('.mk').forEach((m) => {
      const id = +m.dataset.id;
      m.classList.toggle('off', !base.has(id));
      m.classList.toggle('faded', base.has(id) && !ids.has(id));
    });
    realActualizar(ids, base);
    const deptoSlug = state.depto ? App.depto(state.depto).slug : null;
    svg.querySelectorAll('.depto-shape').forEach((p) => {
      p.classList.toggle('dim', !!deptoSlug && p.dataset.depto !== deptoSlug);
      p.classList.toggle('sel', p.dataset.depto === deptoSlug);
    });
    svg.querySelectorAll('.map-labels text').forEach((t) => t.classList.toggle('dim', !!deptoSlug && t.dataset.depto !== deptoSlug));

    $('#mapCount').textContent = `${vis.length} ${vis.length === 1 ? 'lugar' : 'lugares'}`;
    $('#mapList').innerHTML = vis.length ? vis.map((l, i) => {
      const bg = App.bg(l), d = App.depto(l.departamento_id);
      return `<li data-id="${l.id}" class="${state.activo === l.id ? 'active' : ''}" style="animation-delay:${Math.min(i * 25, 400)}ms">
        <span class="thumb ${bg.cls}" style="${bg.style};--c:${App.cat(l.categoria).color}"></span>
        <div><strong>${App.esc(l.nombre)}</strong><small>${App.esc(d.nombre)} · ${App.cat(l.categoria).nombre}</small></div></li>`;
    }).join('') : '<li class="empty"><div><i class="fa-regular fa-face-frown-open fa-2x mb-2"></i><br>No hay lugares con esos filtros.</div></li>';
    marcarActivo();
  }

  function marcarCategoria(cat) {
    state.cat = cat;
    stage.dataset.cat = cat;
    document.querySelectorAll('.interest-card').forEach((c) => {
      const on = c.dataset.cat === cat;
      c.classList.toggle('active', on); c.setAttribute('aria-pressed', on);
    });
  }
  function elegirCategoria(cat) {
    // Elegir «¿Qué querés conocer?» empieza de cero: se borra una búsqueda anterior (ej. «Nivaclé»)
    if (state.q) { state.q = ''; $('#mapSearch').value = ''; }
    marcarCategoria(cat);
    cerrarPreview(); render();
    const n = visibles().length;
    document.querySelectorAll('#toastZone .toast-msg').forEach((t) => t.remove());
    if (cat !== 'todo') App.toast(`${App.cat(cat).nombre}: ${n} ${n === 1 ? 'lugar marcado' : 'lugares marcados'} en el mapa`);
    if (!state.depto) encuadreGeneral();
    // En pantallas chicas el mapa queda debajo del selector: lo mostramos
    if (innerWidth < 992) setTimeout(() => document.querySelector('.map-shell').scrollIntoView({ behavior: 'smooth', block: 'start' }), 150);
  }

  function filtrarDepto(id, sinAnimar = false) {
    state.depto = id;
    document.querySelectorAll('.chip-depto').forEach((c) => c.classList.toggle('active', +c.dataset.depto === id));
    cerrarPreview(); render();
    if (modo === 'real') { id ? realDepto(id) : realVerTodo(); return; }
    if (id) encuadrar(...bboxDepto(App.depto(id).slug), 0.7, !sinAnimar);
    else verTodo(!sinAnimar);
  }

  function seleccionar(id, volar = true) {
    const l = App.lugar(id); if (!l) return;
    // Si el lugar está oculto por los filtros, los limpiamos
    if (!visibles().some((x) => x.id === l.id)) {
      state.q = ''; $('#mapSearch').value = '';
      if (state.cat !== 'todo' && state.cat !== l.categoria) marcarCategoria('todo');
      if (state.depto && state.depto !== +l.departamento_id) state.depto = 0;
      document.querySelectorAll('.chip-depto').forEach((c) => c.classList.toggle('active', +c.dataset.depto === state.depto));
      render();
    }
    state.activo = l.id;
    if (volar && modo === 'real' && R && l.lat) {
      R.flyTo([l.lat, l.lng], Math.max(R.getZoom(), 11), { duration: .9 });
    } else if (volar && modo === 'ilustrado') {
      const [x, y] = posicion(l), px = x / 100 * VB.w, py = y / 100 * VB.h;
      const z = Math.max(view.z, view.fit * 2.6);
      view.z = z; view.tx = stage.clientWidth / 2 - px * z; view.ty = stage.clientHeight / 2 - py * z; aplicar();
    }
    marcarActivo();
    const prev = $('#mapPreview'), bg = App.bg(l), img = prev.querySelector('.map-preview-img');
    img.className = `map-preview-img ${bg.cls}`; img.setAttribute('style', bg.style); img.innerHTML = bg.inner;
    prev.querySelector('.badge-cat').outerHTML = App.badge(l.categoria);
    prev.querySelector('h4').textContent = l.nombre;
    prev.querySelector('p').textContent = l.resumen || '';
    prev.querySelector('[data-action="jugar"]').hidden = !l.preguntas;
    prev.hidden = false;
    prev.style.animation = 'none'; void prev.offsetWidth; prev.style.animation = '';
    document.querySelectorAll('#mapList li').forEach((li) => li.classList.toggle('active', +li.dataset.id === l.id));
  }

  function marcarActivo() {
    markersBox.querySelectorAll('.mk').forEach((m) => m.classList.toggle('active', +m.dataset.id === state.activo));
    Object.entries(rMk).forEach(([id, m]) => {
      m.getElement()?.querySelector('.mk')?.classList.toggle('active', +id === state.activo);
      if (R && R.hasLayer(m)) m.setZIndexOffset(+id === state.activo ? 1000 : 100);
    });
  }
  function cerrarPreview() {
    $('#mapPreview').hidden = true; state.activo = null; marcarActivo();
    document.querySelectorAll('#mapList li').forEach((li) => li.classList.remove('active'));
  }
  function reset() {
    state.q = ''; $('#mapSearch').value = '';
    marcarCategoria('todo');
    filtrarDepto(0);
  }
  function buscar(texto) {
    $('#mapSearch').value = texto; state.q = App.norm(texto); state.depto = 0;
    document.querySelectorAll('.chip-depto').forEach((c) => c.classList.toggle('active', c.dataset.depto === '0'));
    render(); encuadreGeneral();
  }

  return {
    init, filtrarDepto, buscar, elegirCategoria,
    irA(id) { document.getElementById('mapa').scrollIntoView({ behavior: 'smooth' }); setTimeout(() => seleccionar(id, true), 650); },
    invalidar() {
      if (modo === 'real') { R && R.invalidateSize(); return; }
      stage && (state.depto ? filtrarDepto(state.depto, true) : verTodo(false));
    },
  };
})();
