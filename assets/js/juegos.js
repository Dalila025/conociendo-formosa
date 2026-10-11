/* =========================================================
   Conociendo Formosa · Juegos (trivia, memotest, ¿dónde queda?)
   ========================================================= */
App.juegos = (() => {
  const LETRAS = ['a', 'b', 'c', 'd'];
  const shuffle = (a) => { a = [...a]; for (let i = a.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [a[i], a[j]] = [a[j], a[i]]; } return a; };
  const festejo = () => window.confetti && confetti({ particleCount: 140, spread: 80, origin: { y: .7 }, colors: ['#E05A8A', '#F2A541', '#1F9E89', '#ffffff'] });

  /* ---------------- Ranking ---------------- */
  async function cargarRanking(juego) {
    const ol = document.querySelector(`[data-ranking="${juego}"]`);
    try {
      const { ranking } = await App.api(`api/puntajes.php?juego=${juego}`);
      ol.innerHTML = ranking.length
        ? ranking.map((r) => `<li><span>${App.esc(r.nombre)}</span><b>${r.puntos}</b></li>`).join('')
        : '<li class="empty">Todavía no hay puntajes. ¡Sé la primera persona!</li>';
    } catch { ol.innerHTML = '<li class="empty">No se pudo cargar el ranking.</li>'; }
  }

  function formGuardar(cont, juego, datos) {
    const nombreGuardado = localStorage.getItem('cf_nombre') || '';
    cont.insertAdjacentHTML('beforeend', `
      <form class="save-score">
        <input type="text" maxlength="30" placeholder="Tu nombre para el ranking" value="${App.esc(nombreGuardado)}" required>
        <button class="btn btn-lapacho"><i class="fa-solid fa-floppy-disk"></i></button>
      </form>`);
    const f = cont.querySelector('.save-score');
    f.addEventListener('submit', async (e) => {
      e.preventDefault();
      const nombre = f.querySelector('input').value.trim();
      if (nombre.length < 2) return App.toast('Escribí tu nombre (mínimo 2 letras).', 'error');
      try {
        try { localStorage.setItem('cf_nombre', nombre); } catch {}
        await App.api('api/puntajes.php', { method: 'POST', body: JSON.stringify({ csrf: App.csrf, juego, nombre, ...datos }) });
        f.outerHTML = '<p class="mt-3 text-success fw-semibold"><i class="fa-solid fa-check"></i> ¡Puntaje guardado!</p>';
        cargarRanking(juego);
      } catch (err) { App.toast(err.message, 'error'); }
    });
  }

  /* ---------------- Motor de preguntas (sirve para trivia y quiz de lugar) ---------------- */
  function motorQuiz(cont, preguntas, { conTiempo = true, alTerminar, mostrarLugar = true } = {}) {
    let i = 0, puntos = 0, aciertos = 0, timer = null, restante = 0;
    const TIEMPO = 20;

    function pintar() {
      const p = preguntas[i];
      const ops = shuffle(LETRAS.map((k) => ({ k, t: p.opciones[k] })));
      cont.innerHTML = `
        <div class="quiz-top">
          <div class="quiz-progress"><span style="width:${(i / preguntas.length) * 100}%"></span></div>
          <div class="quiz-score"><i class="fa-solid fa-star"></i> ${puntos}</div>
          ${conTiempo ? `<div class="quiz-timer" style="--p:100"><span>${TIEMPO}</span></div>` : ''}
        </div>
        <div class="quiz-q">
          <span class="where">Pregunta ${i + 1} de ${preguntas.length}${mostrarLugar ? ` · <i class="fa-solid fa-location-dot"></i> ${App.esc(p.lugar)}` : ''}</span>
          <h4>${App.esc(p.pregunta)}</h4>
          <div class="quiz-opts">${ops.map((o, n) => `<button class="quiz-opt" data-k="${o.k}"><span class="l">${LETRAS[n]}</span>${App.esc(o.t)}</button>`).join('')}</div>
        </div>`;
      requestAnimationFrame(() => cont.querySelector('.quiz-progress span').style.width = `${((i + 1) / preguntas.length) * 100}%`);
      cont.querySelectorAll('.quiz-opt').forEach((b) => b.addEventListener('click', () => responder(b.dataset.k)));
      if (conTiempo) {
        restante = TIEMPO;
        const t = cont.querySelector('.quiz-timer');
        clearInterval(timer);
        timer = setInterval(() => {
          restante -= .1;
          t.style.setProperty('--p', Math.max(0, restante / TIEMPO * 100));
          t.querySelector('span').textContent = Math.ceil(Math.max(0, restante));
          if (restante <= 0) { clearInterval(timer); responder(null); }
        }, 100);
      }
    }

    function responder(k) {
      clearInterval(timer);
      const p = preguntas[i];
      const ok = k === p.correcta;
      cont.querySelectorAll('.quiz-opt').forEach((b) => {
        b.disabled = true;
        if (b.dataset.k === p.correcta) b.classList.add('ok');
        else if (b.dataset.k === k) b.classList.add('bad');
      });
      if (ok) { aciertos++; puntos += 100 + (conTiempo ? Math.round(Math.max(0, restante) * 5) : 0); }
      cont.querySelector('.quiz-score').innerHTML = `<i class="fa-solid fa-star"></i> ${puntos}`;
      const q = cont.querySelector('.quiz-q');
      q.insertAdjacentHTML('beforeend', `
        <div class="quiz-exp"><strong>${ok ? '¡Correcto! 🎉' : (k ? 'Casi… 🌱' : '¡Se terminó el tiempo! ⏰')}</strong> ${App.esc(p.explicacion || '')}</div>
        <div class="text-end mt-3"><button class="btn btn-lapacho" data-next>${i + 1 < preguntas.length ? 'Siguiente <i class="fa-solid fa-arrow-right ms-1"></i>' : 'Ver resultado <i class="fa-solid fa-flag-checkered ms-1"></i>'}</button></div>`);
      q.querySelector('[data-next]').addEventListener('click', () => { i++; i < preguntas.length ? pintar() : alTerminar({ puntos, aciertos, total: preguntas.length }); });
    }

    pintar();
    return { detener: () => clearInterval(timer) };
  }

  function medalla(r) {
    const p = r.aciertos / r.total;
    if (p === 1) return ['🏆', '¡Experta/o en Formosa!'];
    if (p >= .7) return ['🥇', '¡Muy bien! Conocés mucho.'];
    if (p >= .4) return ['🥈', '¡Buen intento! Seguí explorando.'];
    return ['🌱', 'Recorré el mapa y volvé a intentarlo.'];
  }

  /* ---------------- Trivia ---------------- */
  const trivia = (() => {
    const cont = document.getElementById('trivia');
    let motor;
    async function start() {
      cont.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-success"></div></div>';
      try {
        const { preguntas } = await App.api('api/preguntas.php?random=10');
        if (!preguntas.length) throw new Error('No hay preguntas cargadas todavía.');
        motor = motorQuiz(cont, preguntas, { alTerminar: fin });
      } catch (e) { cont.innerHTML = `<div class="game-start"><p>${App.esc(e.message)}</p></div>`; }
    }
    function fin(r) {
      const [m, txt] = medalla(r);
      cont.innerHTML = `<div class="quiz-result"><div class="medal">${m}</div><div class="big">${r.puntos}</div>
        <p class="fs-5 mb-1">${r.aciertos} de ${r.total} respuestas correctas</p><p class="text-muted">${txt}</p>
        <button class="btn btn-ghost-dark" data-trivia-start><i class="fa-solid fa-rotate me-1"></i> Jugar otra vez</button></div>`;
      if (r.aciertos / r.total >= .6) festejo();
      formGuardar(cont.querySelector('.quiz-result'), 'trivia', r);
    }
    cont.addEventListener('click', (e) => { if (e.target.closest('[data-trivia-start]')) start(); });
    return { detener: () => motor?.detener() };
  })();

  /* ---------------- Memotest ---------------- */
  const memoria = (() => {
    const grid = document.getElementById('memoGrid');
    const movEl = document.querySelector('[data-memo="mov"]');
    const timeEl = document.querySelector('[data-memo="time"]');
    let abiertas = [], bloqueado = false, movs = 0, pares = 0, total = 0, seg = 0, timer = null, iniciado = false;

    // Foto chica para las cartas (carga rápido en el celular)
    const fotoChica = (u) => (u ? u.replace(/width=\d+/, 'width=420') : null);
    const precargar = (url) => new Promise((ok) => {
      const im = new Image(); let listo = false;
      const fin = (v) => { if (!listo) { listo = true; ok(v); } };
      im.onload = () => fin(true); im.onerror = () => fin(false);
      setTimeout(() => fin(im.complete && im.naturalWidth > 0), 6000);
      im.src = url;
    });
    // Reparte por turnos entre cultura, naturaleza y paseo para que queden mezcladas
    function mezclarPorCategoria(lista) {
      const grupos = {};
      shuffle(lista).forEach((l) => { (grupos[l.categoria] ||= []).push(l); });
      const colas = shuffle(Object.values(grupos)), out = [];
      while (colas.some((c) => c.length)) colas.forEach((c) => { if (c.length) out.push(c.shift()); });
      return out;
    }
    let partida = 0;

    async function start() {
      const miPartida = ++partida;
      clearInterval(timer); movEl.textContent = 0; timeEl.textContent = '0:00';
      abiertas = []; bloqueado = false; movs = 0; pares = 0; seg = 0; iniciado = false;
      grid.innerHTML = '<p class="memo-cargando"><i class="fa-solid fa-shuffle fa-beat"></i> Mezclando cartas…</p>';

      // 1) Precargar fotos de lugares de las tres categorías
      const candidatos = mezclarPorCategoria(App.data.lugares.filter((l) => App.img(l))).slice(0, 14);
      const cargadas = await Promise.all(candidatos.map(async (l) => ({ l, url: fotoChica(App.img(l)), ok: await precargar(fotoChica(App.img(l))) })));
      if (miPartida !== partida) return;   // tocaron «Nuevo juego» mientras cargaba
      const conFoto = cargadas.filter((c) => c.ok);

      // 2) Elegir 8 lugares mezclados; si faltan fotos, se completan con cartas ilustradas
      let elegidos = mezclarPorCategoria(conFoto.map((c) => c.l)).slice(0, 8)
        .map((l) => ({ l, url: conFoto.find((c) => c.l.id === l.id).url }));
      if (elegidos.length < 8) {
        const usados = new Set(elegidos.map((e) => e.l.id));
        const resto = mezclarPorCategoria(App.data.lugares.filter((l) => !usados.has(l.id)));
        elegidos = elegidos.concat(resto.slice(0, 8 - elegidos.length).map((l) => ({ l, url: null })));
      }
      total = elegidos.length;
      if (total < 2) { grid.innerHTML = '<p class="text-muted">Cargá más lugares desde el panel para jugar.</p>'; return; }

      const cartas = shuffle([...elegidos, ...elegidos]);
      grid.innerHTML = cartas.map(({ l, url }, i) => {
        const c = App.cat(l.categoria);
        const frente = url
          ? `<div class="memo-face memo-front" style="background-image:url('${App.esc(url)}');--c:${c.color}">`
          : `<div class="memo-face memo-front sin-foto" style="--c:${c.color}"><i class="fa-solid ${c.icono} memo-ico"></i>`;
        return `<button class="memo-card" data-id="${l.id}" style="animation:riseIn .5s ${i * 30}ms both" aria-label="Carta">
          <div class="memo-face memo-back"><i class="fa-solid fa-sun"></i></div>
          ${frente}<em class="memo-cat"><i class="fa-solid ${c.icono}"></i> ${App.esc(c.nombre)}</em><span>${App.esc(l.nombre)}</span></div>
        </button>`;
      }).join('');
    }

    grid.addEventListener('click', (e) => {
      const c = e.target.closest('.memo-card');
      if (!c || bloqueado || c.classList.contains('flip') || c.classList.contains('match')) return;
      if (!iniciado) { iniciado = true; timer = setInterval(() => { seg++; timeEl.textContent = `${Math.floor(seg / 60)}:${String(seg % 60).padStart(2, '0')}`; }, 1000); }
      c.classList.add('flip'); abiertas.push(c);
      if (abiertas.length === 2) {
        movs++; movEl.textContent = movs;
        const [a, b] = abiertas;
        if (a.dataset.id === b.dataset.id) {
          a.classList.add('match'); b.classList.add('match'); abiertas = []; pares++;
          if (pares === total) fin();
        } else {
          bloqueado = true;
          setTimeout(() => { a.classList.remove('flip'); b.classList.remove('flip'); abiertas = []; bloqueado = false; }, 900);
        }
      }
    });

    function fin() {
      clearInterval(timer); festejo();
      const puntos = Math.max(100, 2000 - movs * 40 - seg * 5);
      const div = document.createElement('div');
      div.className = 'quiz-result mt-4';
      div.innerHTML = `<div class="medal">🌸</div><div class="big">${puntos}</div><p>¡Completaste el memotest en ${movs} movimientos y ${timeEl.textContent}!</p>`;
      grid.after(div);
      formGuardar(div, 'memoria', { puntos, aciertos: pares, total });
      grid.parentElement.querySelector('[data-memo-start]').addEventListener('click', () => div.remove(), { once: true });
    }

    document.querySelector('[data-memo-start]').addEventListener('click', start);
    return { start };
  })();

  /* ---------------- ¿Dónde queda? ---------------- */
  const ubicacion = (() => {
    const cont = document.getElementById('ubicacion');
    const RONDAS = 8;
    let rondas = [], i = 0, aciertos = 0, puntos = 0;

    function start() {
      rondas = shuffle(App.data.lugares).slice(0, RONDAS);
      i = 0; aciertos = 0; puntos = 0; ronda();
    }
    function ronda() {
      const l = rondas[i];
      const correcto = App.depto(l.departamento_id);
      const otros = shuffle(App.data.departamentos.filter((d) => +d.id !== +correcto.id)).slice(0, 3);
      const ops = shuffle([correcto, ...otros]);
      const bg = App.bg(l);
      cont.innerHTML = `
        <div class="quiz-top"><div class="quiz-progress"><span style="width:${(i / RONDAS) * 100}%"></span></div><div class="quiz-score"><i class="fa-solid fa-star"></i> ${puntos}</div></div>
        <div class="ubi-card">
          <div class="ubi-img ${bg.cls}" style="${bg.style}">${bg.inner}</div>
          <div>
            <span class="where text-muted small">Ronda ${i + 1} de ${RONDAS}</span>
            <h4 class="mt-1">${App.esc(l.nombre)}</h4>
            <p class="text-muted mb-1">${App.esc(l.resumen || '')}</p>
            <strong>¿En qué departamento está?</strong>
            <div class="ubi-opts">${ops.map((d) => `<button class="quiz-opt" data-d="${d.id}"><span class="l" style="background:${d.color};color:#fff"><i class="fa-solid fa-map-pin"></i></span>${App.esc(d.nombre)}</button>`).join('')}</div>
          </div>
        </div>`;
      requestAnimationFrame(() => cont.querySelector('.quiz-progress span').style.width = `${((i + 1) / RONDAS) * 100}%`);
      cont.querySelectorAll('[data-d]').forEach((b) => b.addEventListener('click', () => {
        const ok = +b.dataset.d === +correcto.id;
        cont.querySelectorAll('[data-d]').forEach((x) => { x.disabled = true; if (+x.dataset.d === +correcto.id) x.classList.add('ok'); });
        if (!ok) b.classList.add('bad'); else { aciertos++; puntos += 150; }
        cont.querySelector('.quiz-score').innerHTML = `<i class="fa-solid fa-star"></i> ${puntos}`;
        cont.querySelector('.ubi-card > div:last-child').insertAdjacentHTML('beforeend', `
          <div class="quiz-exp"><strong>${ok ? '¡Exacto! 📍' : 'No era ese 🧭'}</strong> ${App.esc(l.nombre)} está en el departamento <b>${App.esc(correcto.nombre)}</b> (cabecera: ${App.esc(correcto.cabecera || '')}).</div>
          <div class="text-end mt-3"><button class="btn btn-lapacho" data-next>${i + 1 < RONDAS ? 'Siguiente' : 'Ver resultado'} <i class="fa-solid fa-arrow-right ms-1"></i></button></div>`);
        cont.querySelector('[data-next]').addEventListener('click', () => { i++; i < RONDAS ? ronda() : fin(); });
      }));
    }
    function fin() {
      const r = { puntos, aciertos, total: RONDAS };
      const [m, txt] = medalla(r);
      cont.innerHTML = `<div class="quiz-result"><div class="medal">${m}</div><div class="big">${puntos}</div><p class="fs-5 mb-1">${aciertos} de ${RONDAS} ubicaciones correctas</p><p class="text-muted">${txt}</p>
        <button class="btn btn-ghost-dark" data-ubi-start><i class="fa-solid fa-rotate me-1"></i> Jugar otra vez</button></div>`;
      if (aciertos / RONDAS >= .6) festejo();
      formGuardar(cont.querySelector('.quiz-result'), 'ubicacion', r);
    }
    cont.addEventListener('click', (e) => { if (e.target.closest('[data-ubi-start]')) start(); });
    return {};
  })();

  function init() {
    ['trivia', 'memoria', 'ubicacion'].forEach(cargarRanking);
    memoria.start();
  }

  return { init };
})();
