/* =========================================================
   Conociendo Formosa · Script principal
   Desarrollado por Dalila Spindola Dev
   ========================================================= */
(() => {
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Preloader ---------- */
  const ocultarLoader = () => $('#preloader')?.classList.add('hide');
  window.addEventListener('load', () => setTimeout(ocultarLoader, 400));
  setTimeout(ocultarLoader, 4000); // por si alguna imagen externa tarda

  /* ---------- Navbar, progreso y botón arriba ---------- */
  const nav = $('#mainNav'), prog = $('#scrollProgress'), toTop = $('#toTop');
  const onScroll = () => {
    const y = scrollY, h = document.documentElement.scrollHeight - innerHeight;
    nav.classList.toggle('scrolled', y > 60);
    prog.style.width = `${(y / h) * 100}%`;
    toTop.classList.toggle('show', y > 700);
  };
  addEventListener('scroll', onScroll, { passive: true }); onScroll();
  toTop.addEventListener('click', () => scrollTo({ top: 0, behavior: 'smooth' }));

  // Cerrar menú móvil al elegir una opción
  $$('#navMenu .nav-link').forEach((a) => a.addEventListener('click', () => bootstrap.Collapse.getInstance($('#navMenu'))?.hide()));

  // Resaltar sección activa
  const secciones = $$('section[id], header[id]');
  const spyIO = new IntersectionObserver((ents) => ents.forEach((en) => {
    if (en.isIntersecting) $$('#navMenu .nav-link').forEach((a) => a.classList.toggle('active', a.getAttribute('href') === '#' + en.target.id));
  }), { rootMargin: '-45% 0px -50% 0px' });
  secciones.forEach((s) => spyIO.observe(s));

  /* ---------- Revelado al hacer scroll ---------- */
  const revealIO = new IntersectionObserver((ents) => ents.forEach((en) => {
    if (en.isIntersecting) { en.target.classList.add('in'); revealIO.unobserve(en.target); }
  }), { threshold: .12 });
  const observarReveal = () => $$('.reveal:not(.in)').forEach((el) => revealIO.observe(el));
  observarReveal();

  /* ---------- Contadores ---------- */
  const cntIO = new IntersectionObserver((ents) => ents.forEach((en) => {
    if (!en.isIntersecting) return;
    const el = en.target, to = +el.dataset.to, t0 = performance.now(), dur = 1600;
    const step = (t) => { const p = Math.min(1, (t - t0) / dur); el.textContent = Math.round(to * (1 - Math.pow(1 - p, 3))); if (p < 1) requestAnimationFrame(step); };
    requestAnimationFrame(step); cntIO.unobserve(el);
  }), { threshold: .5 });
  $$('.counter').forEach((c) => cntIO.observe(c));

  /* ---------- Slider del hero ---------- */
  (() => {
    const slides = $$('.hero-slide'), caps = $$('.hero-caption'), dots = $$('.hero-dots button');
    if (slides.length < 2) return;
    let i = 0, timer;
    const go = (n) => {
      i = (n + slides.length) % slides.length;
      slides.forEach((s, k) => s.classList.toggle('active', k === i));
      caps.forEach((s, k) => s.classList.toggle('active', k === i));
      dots.forEach((d, k) => { d.classList.remove('active'); if (k === i) { void d.offsetWidth; d.classList.add('active'); } });
      clearInterval(timer); timer = setInterval(() => go(i + 1), 7000);
    };
    dots.forEach((d) => d.addEventListener('click', () => go(+d.dataset.go)));
    go(0);
  })();

  // Parallax suave del hero
  if (!reduce) addEventListener('scroll', () => {
    if (scrollY < innerHeight) $$('.hero-slide.active .hero-bg').forEach((b) => b.style.translate = `0 ${scrollY * .3}px`);
  }, { passive: true });

  /* ---------- Pétalos de lapacho cayendo ---------- */
  (() => {
    const cv = $('#petalos'); if (!cv || reduce) return;
    const ctx = cv.getContext('2d');
    let W, H, petals = [];
    const resize = () => { W = cv.width = innerWidth; H = cv.height = innerHeight; };
    resize(); addEventListener('resize', resize);
    const N = innerWidth < 768 ? 12 : 24;
    const nuevo = (y) => ({ x: Math.random() * W, y: y ?? -20, r: 5 + Math.random() * 6, vy: .4 + Math.random() * .9, vx: -.3 + Math.random() * .6, a: Math.random() * Math.PI * 2, va: -.02 + Math.random() * .04, sway: Math.random() * 100, hue: Math.random() < .8 ? 335 : 40 });
    for (let k = 0; k < N; k++) petals.push(nuevo(Math.random() * H));
    let visible = true;
    new IntersectionObserver(([e]) => visible = e.isIntersecting).observe($('#inicio'));
    (function loop() {
      ctx.clearRect(0, 0, W, H);
      if (visible) {
        petals.forEach((p, k) => {
          p.sway += .02; p.y += p.vy; p.x += p.vx + Math.sin(p.sway) * .5; p.a += p.va;
          if (p.y > H + 20) petals[k] = nuevo();
          ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.a);
          ctx.fillStyle = `hsla(${p.hue}, 75%, ${p.hue === 40 ? 60 : 72}%, .85)`;
          ctx.beginPath(); ctx.ellipse(0, 0, p.r, p.r * .55, 0, 0, Math.PI * 2); ctx.fill();
          ctx.restore();
        });
      }
      requestAnimationFrame(loop);
    })();
  })();

  /* ---------- Efecto tilt + brillo ---------- */
  function tilt(el, max = 8) {
    if (reduce || matchMedia('(hover: none)').matches) return;
    el.addEventListener('mousemove', (e) => {
      const r = el.getBoundingClientRect(), x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height;
      el.style.transform = `perspective(900px) rotateY(${(x - .5) * max}deg) rotateX(${(.5 - y) * max}deg) translateY(-6px)`;
      el.style.setProperty('--mx', `${x * 100}%`); el.style.setProperty('--my', `${y * 100}%`);
    });
    el.addEventListener('mouseleave', () => el.style.transform = '');
  }
  $$('.tilt').forEach((t) => tilt(t, 6));

  // Botones magnéticos
  if (!reduce) $$('.magnetic').forEach((b) => {
    b.addEventListener('mousemove', (e) => { const r = b.getBoundingClientRect(); b.style.transform = `translate(${(e.clientX - r.left - r.width / 2) * .2}px, ${(e.clientY - r.top - r.height / 2) * .3}px)`; });
    b.addEventListener('mouseleave', () => b.style.transform = '');
  });

  /* ---------- Render de secciones dinámicas ---------- */
  function renderDeptos() {
    $('#deptoGrid').innerHTML = App.data.departamentos.map((d, i) => `
      <article class="depto-card reveal" data-depto="${d.id}" style="--c:${d.color};--d:${(i % 3) * 100}ms" tabindex="0" role="button" aria-label="Ver ${App.esc(d.nombre)} en el mapa">
        <div class="bg" style="${d.imagen ? `background-image:url('${App.esc(d.imagen)}')` : `background:linear-gradient(135deg, ${d.color}, #0E3B2E)`}"></div>
        <span class="depto-num">${String(i + 1).padStart(2, '0')}</span>
        <div class="depto-body">
          <h3>${App.esc(d.nombre)}</h3>
          <span class="cab"><i class="fa-solid fa-flag me-1"></i>Cabecera: ${App.esc(d.cabecera || '')}</span>
          <p>${App.esc(d.descripcion || '')}</p>
          <div class="depto-foot"><span><i class="fa-solid fa-location-dot me-1"></i>${d.total} lugares</span><span class="go"><i class="fa-solid fa-arrow-right"></i></span></div>
        </div>
      </article>`).join('');
    $$('.depto-card').forEach((c) => {
      const go = () => { document.getElementById('mapa').scrollIntoView({ behavior: 'smooth' }); setTimeout(() => App.mapa.filtrarDepto(+c.dataset.depto), 600); };
      c.addEventListener('click', go);
      c.addEventListener('keydown', (e) => { if (e.key === 'Enter') go(); });
    });
  }

  function renderDestacados() {
    let lista = App.data.lugares.filter((l) => +l.destacado);
    if (lista.length < 4) lista = [...lista, ...App.data.lugares.filter((l) => !+l.destacado && App.img(l))].slice(0, 8);
    $('#featuredTrack').innerHTML = lista.map((l) => {
      const bg = App.bg(l), d = App.depto(l.departamento_id);
      return `<article class="feat-card" data-id="${l.id}">
        <div class="feat-img ${bg.cls}" style="${bg.style}">${bg.inner}${App.badge(l.categoria)}<span class="feat-shine"></span></div>
        <div class="feat-body"><h3>${App.esc(l.nombre)}</h3><span class="dep"><i class="fa-solid fa-map-pin me-1"></i>${App.esc(d.nombre)}</span><p>${App.esc(l.resumen || '')}</p></div>
      </article>`;
    }).join('');
    $$('.feat-card').forEach((c) => { tilt(c); c.addEventListener('click', () => App.abrirLugar(+c.dataset.id)); });
    $$('[data-scroll]').forEach((b) => b.addEventListener('click', () => $('#featuredTrack').scrollBy({ left: +b.dataset.scroll * 360, behavior: 'smooth' })));
  }

  function renderGaleria() {
    const fotos = [];
    App.data.lugares.forEach((l) => (l.imagenes || []).forEach((im) => fotos.push({ ...im, lugar: l })));
    const unicas = [...new Map(fotos.map((f) => [f.url, f])).values()].slice(0, 16);
    const items = unicas.map((f) => ({ url: f.url, titulo: f.titulo || f.lugar.nombre, sub: `${f.lugar.nombre} · ${f.credito || ''}` }));
    $('#gallery').innerHTML = unicas.map((f, i) => `
      <figure class="gallery-item reveal" data-i="${i}" style="--d:${(i % 4) * 80}ms">
        <img src="${App.esc(f.url.replace(/width=\d+/, 'width=700'))}" alt="${App.esc(f.titulo || f.lugar.nombre)}" loading="lazy" onerror="this.closest('figure').remove()">
        <figcaption>${App.esc(f.titulo || f.lugar.nombre)}<small>${App.esc(f.lugar.nombre)}</small></figcaption>
      </figure>`).join('') || '<p class="text-center text-muted">Pronto habrá fotos aquí.</p>';
    $$('.gallery-item').forEach((g) => g.addEventListener('click', () => App.lightbox.open(items, +g.dataset.i)));
  }

  /* ---------- Buscador global ---------- */
  function initBuscador() {
    const ov = $('#searchOverlay'), inp = $('#globalSearch'), res = $('#globalResults');
    let sel = 0, actuales = [];
    const abrir = () => { ov.hidden = false; document.body.classList.add('no-scroll'); inp.value = ''; pintar(''); setTimeout(() => inp.focus(), 50); };
    const cerrar = () => { ov.hidden = true; document.body.classList.remove('no-scroll'); };
    const resaltar = (txt, q) => { if (!q) return App.esc(txt); const i = App.norm(txt).indexOf(q); return i < 0 ? App.esc(txt) : App.esc(txt.slice(0, i)) + '<mark>' + App.esc(txt.slice(i, i + q.length)) + '</mark>' + App.esc(txt.slice(i + q.length)); };
    function pintar(q) {
      q = App.norm(q.trim());
      actuales = App.data.lugares.filter((l) => !q || App.norm(`${l.nombre} ${App.depto(l.departamento_id).nombre} ${l.resumen}`).includes(q)).slice(0, 8);
      sel = 0;
      res.innerHTML = actuales.length ? actuales.map((l, k) => { const bg = App.bg(l); return `<li data-id="${l.id}" class="${k ? '' : 'sel'}"><span class="thumb ${bg.cls}" style="${bg.style}"></span><div><strong>${resaltar(l.nombre, q)}</strong><br><small>${App.esc(App.depto(l.departamento_id).nombre)} · ${App.cat(l.categoria).nombre}</small></div></li>`; }).join('') : '<li class="text-muted">Sin resultados 🌵</li>';
    }
    $('#btnBuscarGlobal').addEventListener('click', abrir);
    inp.addEventListener('input', () => pintar(inp.value));
    res.addEventListener('click', (e) => { const li = e.target.closest('[data-id]'); if (li) { cerrar(); App.abrirLugar(+li.dataset.id); } });
    ov.addEventListener('click', (e) => { if (e.target === ov) cerrar(); });
    document.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); abrir(); }
      if (ov.hidden) return;
      if (e.key === 'Escape') cerrar();
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); sel = (sel + (e.key === 'ArrowDown' ? 1 : -1) + actuales.length) % actuales.length; $$('li', res).forEach((li, k) => li.classList.toggle('sel', k === sel)); }
      if (e.key === 'Enter' && actuales[sel]) { cerrar(); App.abrirLugar(actuales[sel].id); }
    });
  }

  /* ---------- Arranque ---------- */
  async function iniciar() {
    try {
      const data = await App.api('api/lugares.php');
      App.data = { departamentos: data.departamentos, lugares: data.lugares, categorias: data.categorias };
    } catch (e) {
      App.toast('No se pudieron cargar los lugares: ' + e.message, 'error');
      return;
    }
    App.mapa.init();
    renderDeptos();
    renderDestacados();
    renderGaleria();
    App.juegos.init();
    App.resenas.init();
    initBuscador();
    observarReveal();

    $$('[data-buscar]').forEach((b) => b.addEventListener('click', () => { document.getElementById('mapa').scrollIntoView({ behavior: 'smooth' }); setTimeout(() => App.mapa.buscar(b.dataset.buscar), 600); }));

    // Enlaces que vienen desde la ficha del lugar: ?ver=ID (mapa) o ?resenar=ID (reseñas)
    const qs = new URLSearchParams(location.search);
    if (qs.get('ver')) setTimeout(() => App.mapa.irA(+qs.get('ver')), 500);
    if (qs.get('resenar')) setTimeout(() => App.resenas.preseleccionar(+qs.get('resenar')), 500);
    // Enlaces viejos con #lugar-slug → ficha nueva
    const m = location.hash.match(/^#lugar-(.+)$/);
    if (m) { const l = App.data.lugares.find((x) => x.slug === decodeURIComponent(m[1])); if (l) { history.replaceState(null, '', location.pathname); setTimeout(() => App.abrirLugar(l.id), 600); } }
  }
  iniciar();
})();
