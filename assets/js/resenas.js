/* =========================================================
   Conociendo Formosa · Reseñas
   ========================================================= */
App.resenas = (() => {
  let pagina = 1;
  const COLORES = ['#E05A8A', '#1F9E89', '#F2A541', '#C2552F', '#5C7AEA', '#8C6BB1'];
  const list = document.getElementById('reviewList');
  const btnMas = document.getElementById('btnMasResenas');
  const form = document.getElementById('reviewForm');

  const fecha = (s) => new Date(s.replace(' ', 'T')).toLocaleDateString('es-AR', { day: 'numeric', month: 'short', year: 'numeric' });

  function item(r) {
    const color = COLORES[r.nombre.length % COLORES.length];
    return `<article class="review-item">
      <div class="review-head">
        <span class="avatar" style="background:${color}">${App.esc(r.nombre.trim().charAt(0).toUpperCase())}</span>
        <div><strong>${App.esc(r.nombre)}</strong><small>${r.ciudad ? App.esc(r.ciudad) + ' · ' : ''}${fecha(r.creado)}</small></div>
      </div>
      <div class="stars-static">${App.stars(+r.calificacion)}</div>
      <p>${App.esc(r.comentario)}</p>
      ${r.lugar ? `<span class="review-tag"><i class="fa-solid fa-location-dot me-1"></i>${App.esc(r.lugar)}</span>` : ''}
    </article>`;
  }

  async function cargar(reset = false) {
    if (reset) pagina = 1;
    try {
      const d = await App.api(`api/resenas.php?pagina=${pagina}`);
      const s = d.stats;
      document.getElementById('revAvg').textContent = (+s.promedio).toFixed(1);
      document.getElementById('revStars').innerHTML = App.stars(+s.promedio);
      document.getElementById('revTotal').textContent = `${s.total} ${+s.total === 1 ? 'reseña' : 'reseñas'}`;
      document.getElementById('revBars').innerHTML = [5, 4, 3, 2, 1].map((n) => {
        const pct = s.total ? Math.round((s['c' + n] || 0) / s.total * 100) : 0;
        return `<div class="bar"><span>${n}<i class="fa-solid fa-star ms-1" style="color:var(--sol);font-size:.7rem"></i></span><div class="track"><div class="fill" data-w="${pct}"></div></div><span style="width:34px;text-align:right">${pct}%</span></div>`;
      }).join('');
      requestAnimationFrame(() => setTimeout(() => document.querySelectorAll('#revBars .fill').forEach((f) => f.style.width = f.dataset.w + '%'), 60));

      const html = d.resenas.map(item).join('');
      if (pagina === 1) list.innerHTML = html || `<div class="review-empty"><i class="fa-regular fa-comments d-block"></i><p class="mb-0">Todavía no hay reseñas.<br>¡Sé la primera persona en contar su experiencia!</p></div>`;
      else list.insertAdjacentHTML('beforeend', html);
      btnMas.hidden = !d.mas;
    } catch (e) {
      list.innerHTML = `<div class="review-empty">${App.esc(e.message)}</div>`;
    }
  }

  function init() {
    // Opciones de lugares en el formulario
    const sel = document.getElementById('revLugar');
    const grupos = App.data.departamentos.map((d) => {
      const ls = App.data.lugares.filter((l) => +l.departamento_id === +d.id);
      return ls.length ? `<optgroup label="${App.esc(d.nombre)}">${ls.map((l) => `<option value="${l.id}">${App.esc(l.nombre)}</option>`).join('')}</optgroup>` : '';
    }).join('');
    sel.insertAdjacentHTML('beforeend', grupos);

    const txt = document.getElementById('revComent');
    txt.addEventListener('input', () => document.getElementById('revChars').textContent = txt.value.length);

    btnMas.addEventListener('click', () => { pagina++; cargar(); });

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const datos = Object.fromEntries(new FormData(form));
      if (!datos.calificacion) return App.toast('Elegí cuántas estrellas le das.', 'error');
      if ((datos.nombre || '').trim().length < 2) return App.toast('Escribí tu nombre.', 'error');
      if ((datos.comentario || '').trim().length < 10) return App.toast('Contanos un poco más (mínimo 10 caracteres).', 'error');
      const btn = form.querySelector('[type="submit"]');
      btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enviando…';
      try {
        const r = await App.api('api/resenas.php', { method: 'POST', body: JSON.stringify(datos) });
        App.toast(r.mensaje);
        form.reset(); document.getElementById('revChars').textContent = 0;
        if (r.estado === 'aprobada') { cargar(true); window.confetti && confetti({ particleCount: 80, spread: 60, origin: { y: .8 } }); }
      } catch (err) { App.toast(err.message, 'error'); }
      btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Publicar reseña';
    });

    cargar();
  }

  function preseleccionar(lugarId) {
    document.getElementById('revLugar').value = lugarId;
    document.getElementById('resenas').scrollIntoView({ behavior: 'smooth' });
    setTimeout(() => document.getElementById('revNombre').focus({ preventScroll: true }), 800);
  }

  return { init, preseleccionar };
})();
