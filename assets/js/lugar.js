/* =========================================================
   Conociendo Formosa · Ficha del lugar
   ========================================================= */
(() => {
  const $ = (s) => document.querySelector(s);
  const $$ = (s) => [...document.querySelectorAll(s)];
  const fotos = window.LUGAR_FOTOS || [];

  /* ---------- Galería del encabezado ---------- */
  const slides = $$('.lh-slide'), thumbs = $$('.lh-thumbs button'), cap = $('#heroCap');
  let cur = 0, timer;
  const go = (i) => {
    if (slides.length < 2) return;
    cur = (i + slides.length) % slides.length;
    slides.forEach((s, k) => s.classList.toggle('active', k === cur));
    thumbs.forEach((t, k) => t.classList.toggle('active', k === cur));
    if (cap && fotos[cur]) cap.textContent = `${fotos[cur].titulo}${fotos[cur].sub ? ' · ' + fotos[cur].sub : ''}`;
    clearInterval(timer); timer = setInterval(() => go(cur + 1), 6000);
  };
  thumbs.forEach((t) => t.addEventListener('click', () => go(+t.dataset.i)));
  go(0);
  $('#btnAmpliar')?.addEventListener('click', () => App.lightbox.open(fotos, cur));

  /* ---------- Pestañas: abrir la que indica el #hash y botones "Siguiente" ---------- */
  const tabBtns = $$('.lugar-tabs [data-bs-toggle="tab"]');
  const abrirTab = (target) => {
    const b = tabBtns.find((x) => x.dataset.bsTarget === target);
    if (b) bootstrap.Tab.getOrCreateInstance(b).show();
  };
  const porHash = { historia: '#t-que', importancia: '#t-imp', habitantes: '#t-hab', juego: '#t-juego' };
  if (porHash[location.hash.slice(1)]) abrirTab(porHash[location.hash.slice(1)]);
  tabBtns.forEach((b) => b.addEventListener('shown.bs.tab', () => history.replaceState(null, '', '#' + b.dataset.hash)));
  $$('[data-next]').forEach((b) => b.addEventListener('click', () => {
    abrirTab(b.dataset.next);
    $('.lugar-tabs').scrollIntoView({ behavior: 'smooth', block: 'start' });
  }));

  /* ---------- Dentro de la ventana flotante: acciones sobre el mapa ---------- */
  const padre = (window.parent !== window && window.parent.App) ? window.parent.App : null;
  if (padre) {
    $$('[data-padre]').forEach((a) => a.addEventListener('click', (e) => {
      e.preventDefault();
      // La acción la ejecuta la página principal (esta ventana se descarga al cerrarse)
      padre.accionDesdeFicha(a.dataset.padre, +a.dataset.id);
    }));
    // Al pasar a otro lugar del departamento, actualizar el título de la ventana
    $$('.otro[data-slug]').forEach((a) => a.addEventListener('click', (e) => {
      e.preventDefault();
      location.replace(a.href);   // sin sumar entradas al historial
      const l = padre.data.lugares.find((x) => x.slug === a.dataset.slug);
      const doc = window.parent.document;
      if (l) { doc.querySelector('#fichaWindow .fw-title').textContent = l.nombre; doc.querySelector('#fichaWindow .fw-dot').style.background = padre.cat(l.categoria).color; }
    }));
  }

  /* ---------- Compartir ---------- */
  $('#btnCompartir')?.addEventListener('click', async (e) => {
    const b = e.currentTarget, url = new URL(b.dataset.url, location.href).href;
    try {
      if (navigator.share) await navigator.share({ title: b.dataset.titulo, url });
      else { await navigator.clipboard.writeText(url); App.toast('¡Enlace copiado!'); }
    } catch {}
  });

  /* ---------- Mini mapa: resaltar el departamento ---------- */
  const mini = $('.mini-map');
  if (mini) {
    const p = mini.querySelector(`#d-${mini.dataset.depto}`);
    mini.querySelectorAll('.depto-shape').forEach((s) => s.classList.toggle('dim', s !== p));
    p?.classList.add('sel');
    mini.querySelectorAll('.map-labels text').forEach((t) => t.classList.toggle('dim', t.dataset.depto !== mini.dataset.depto));
  }

  /* ---------- Desafío del lugar ---------- */
  const quiz = $('#quizLugar');
  if (!quiz) return;
  const LET = ['a', 'b', 'c', 'd'];
  const mezclar = (a) => { a = [...a]; for (let i = a.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [a[i], a[j]] = [a[j], a[i]]; } return a; };

  async function iniciar() {
    quiz.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-success"></div></div>';
    let preguntas;
    try { ({ preguntas } = await App.api(`api/preguntas.php?lugar=${quiz.dataset.lugar}`)); }
    catch (e) { quiz.innerHTML = `<p class="text-danger">${App.esc(e.message)}</p>`; return; }
    let i = 0, ok = 0;
    const pintar = () => {
      const p = preguntas[i];
      const ops = mezclar(LET.map((k) => ({ k, t: p.opciones[k] })));
      quiz.innerHTML = `
        <div class="quiz-top"><div class="quiz-progress"><span style="width:${(i + 1) / preguntas.length * 100}%"></span></div><div class="quiz-score"><i class="fa-solid fa-star"></i> ${ok}/${preguntas.length}</div></div>
        <div class="quiz-q"><span class="where">Pregunta ${i + 1} de ${preguntas.length}</span><h4>${App.esc(p.pregunta)}</h4>
        <div class="quiz-opts">${ops.map((o, n) => `<button class="quiz-opt" data-k="${o.k}"><span class="l">${LET[n]}</span>${App.esc(o.t)}</button>`).join('')}</div></div>`;
      quiz.querySelectorAll('.quiz-opt').forEach((b) => b.addEventListener('click', () => {
        const bien = b.dataset.k === p.correcta;
        if (bien) ok++;
        quiz.querySelectorAll('.quiz-opt').forEach((x) => { x.disabled = true; if (x.dataset.k === p.correcta) x.classList.add('ok'); });
        if (!bien) b.classList.add('bad');
        quiz.querySelector('.quiz-score').innerHTML = `<i class="fa-solid fa-star"></i> ${ok}/${preguntas.length}`;
        quiz.querySelector('.quiz-q').insertAdjacentHTML('beforeend', `
          <div class="quiz-exp"><strong>${bien ? '¡Correcto! 🎉' : 'Casi… 🌱'}</strong> ${App.esc(p.explicacion || '')}</div>
          <div class="text-end mt-3"><button class="btn btn-lapacho" data-sig>${i + 1 < preguntas.length ? 'Siguiente <i class="fa-solid fa-arrow-right ms-1"></i>' : 'Ver resultado <i class="fa-solid fa-flag-checkered ms-1"></i>'}</button></div>`);
        quiz.querySelector('[data-sig]').addEventListener('click', () => { i++; i < preguntas.length ? pintar() : fin(); });
      }));
    };
    const fin = () => {
      const medalla = ok === preguntas.length ? '🏆' : ok >= preguntas.length / 2 ? '🥇' : '🌱';
      quiz.innerHTML = `<div class="quiz-result"><div class="medal">${medalla}</div><p class="fs-4 fw-semibold mb-1">${ok} de ${preguntas.length} correctas</p>
        <p class="text-muted">${ok === preguntas.length ? '¡Conocés muy bien este lugar!' : 'Volvé a leer la ficha y probá otra vez.'}</p>
        <button class="btn btn-ghost-dark btn-sm" data-otra><i class="fa-solid fa-rotate me-1"></i> Reintentar</button>
        <a href="index.php#juegos" class="btn btn-lapacho btn-sm ms-1">Ir a la trivia completa</a></div>`;
      if (ok === preguntas.length && window.confetti) confetti({ particleCount: 140, spread: 80, origin: { y: .7 } });
      quiz.querySelector('[data-otra]').addEventListener('click', iniciar);
    };
    if (!preguntas.length) { quiz.innerHTML = '<p class="text-muted">Este lugar todavía no tiene desafío.</p>'; return; }
    pintar();
  }
  let iniciado = false;
  const arrancar = () => { if (!iniciado) { iniciado = true; iniciar(); } };
  $('#tabJuego')?.addEventListener('shown.bs.tab', arrancar);
  if ($('#t-juego')?.classList.contains('active')) arrancar();
})();
