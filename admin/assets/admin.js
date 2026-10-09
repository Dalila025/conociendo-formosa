/* =========================================================
   Panel administrativo · interacciones
   ========================================================= */
(() => {
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];

  // Menú lateral en móvil
  document.getElementById('sbToggle')?.addEventListener('click', () => document.body.classList.toggle('sb-open'));
  document.getElementById('sbBackdrop')?.addEventListener('click', () => document.body.classList.remove('sb-open'));

  // Alertas que se cierran solas
  $$('.flash.alert-success').forEach((a) => setTimeout(() => bootstrap.Alert.getOrCreateInstance(a).close(), 4500));

  // Selects que envían el formulario al cambiar
  $$('[data-autosubmit]').forEach((s) => s.addEventListener('change', () => s.form.submit()));

  // Confirmación antes de eliminar
  const modalEl = document.getElementById('modalConfirm');
  if (modalEl) {
    const modal = new bootstrap.Modal(modalEl);
    let pendiente = null;
    $$('form[data-confirm]').forEach((f) => f.addEventListener('submit', (e) => {
      if (f.dataset.ok) return;
      e.preventDefault();
      pendiente = f;
      document.getElementById('confirmTexto').textContent = f.dataset.confirm;
      modal.show();
    }));
    document.getElementById('confirmOk').addEventListener('click', () => {
      if (!pendiente) return;
      pendiente.dataset.ok = '1';
      pendiente.requestSubmit ? pendiente.requestSubmit() : pendiente.submit();
    });
  }

  // Campo de imagen individual: vista previa al elegir archivo o pegar URL
  $$('[data-img-field]').forEach((box) => {
    const prev = box.querySelector('.img-preview');
    const file = box.querySelector('input[type=file]');
    const url = box.querySelector('input[type=url]');
    const zone = box.querySelector('.drop-zone');
    const show = (src) => { prev.style.backgroundImage = src ? `url('${src}')` : ''; prev.classList.toggle('has-img', !!src); };
    file.addEventListener('change', () => { if (file.files[0]) { show(URL.createObjectURL(file.files[0])); zone.querySelector('span').innerHTML = `<strong>${file.files[0].name}</strong>`; } });
    url?.addEventListener('input', () => { if (/^https?:\/\//.test(url.value)) show(url.value); });
    dragDrop(zone, file);
  });

  // Subida múltiple
  $$('.drop-zone[data-multi]').forEach((zone) => {
    const input = zone.querySelector('input[type=file]');
    const grid = zone.parentElement.querySelector('.multi-preview');
    input.addEventListener('change', () => {
      grid.innerHTML = [...input.files].map((f) => `<div style="background-image:url('${URL.createObjectURL(f)}')" title="${f.name}"></div>`).join('');
      zone.querySelector('span').innerHTML = `<strong>${input.files.length} foto(s) seleccionada(s)</strong>`;
    });
    dragDrop(zone, input);
  });

  function dragDrop(zone, input) {
    if (!zone || !input) return;
    ['dragenter', 'dragover'].forEach((ev) => zone.addEventListener(ev, (e) => { e.preventDefault(); zone.classList.add('over'); }));
    ['dragleave', 'drop'].forEach((ev) => zone.addEventListener(ev, (e) => { e.preventDefault(); zone.classList.remove('over'); }));
    zone.addEventListener('drop', (e) => {
      if (!e.dataTransfer.files.length) return;
      const dt = new DataTransfer();
      [...e.dataTransfer.files].filter((f) => f.type.startsWith('image/')).slice(0, input.multiple ? 30 : 1).forEach((f) => dt.items.add(f));
      input.files = dt.files;
      input.dispatchEvent(new Event('change'));
    });
  }

  // Ordenar con arrastrar y soltar
  function sortable(id, url) {
    const el = document.getElementById(id);
    if (!el || !window.Sortable) return;
    Sortable.create(el, {
      animation: 200, handle: '.drag-handle', ghostClass: 'sortable-ghost',
      onEnd: () => {
        const body = new FormData();
        body.append('accion', 'ordenar');
        body.append('csrf', el.dataset.csrf);
        body.append('ids', [...el.children].map((c) => c.dataset.id).filter(Boolean).join(','));
        fetch(url, { method: 'POST', body }).then((r) => r.json()).then((r) => { if (!r.ok) alert('No se pudo guardar el orden.'); });
      },
    });
  }
  const grid = document.getElementById('imgGrid');
  if (grid) sortable('imgGrid', `imagenes.php?lugar=${grid.dataset.lugar}`);
  sortable('slideList', 'slides.php');

  // Contador de caracteres
  $$('input[data-count], textarea[data-count]').forEach((i) => {
    const s = document.createElement('div');
    s.className = 'form-text text-end';
    const upd = () => s.textContent = `${i.value.length}/${i.maxLength}`;
    i.after(s); i.addEventListener('input', upd); upd();
  });
})();

/** Mapa para elegir coordenadas (formulario de lugar) */
function initPicker(mapId, latId, lngId) {
  const latI = document.getElementById(latId), lngI = document.getElementById(lngId);
  const pos = [parseFloat(latI.value) || -24.9, parseFloat(lngI.value) || -59.9];
  const map = L.map(mapId).setView(pos, latI.value && latI.value !== '-24.9' ? 11 : 7);
  L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { attribution: '&copy; OpenStreetMap &copy; CARTO', subdomains: 'abcd' }).addTo(map);
  const mk = L.marker(pos, { draggable: true }).addTo(map);
  const set = (ll) => { latI.value = ll.lat.toFixed(7); lngI.value = ll.lng.toFixed(7); mk.setLatLng(ll); };
  map.on('click', (e) => set(e.latlng));
  mk.on('dragend', () => set(mk.getLatLng()));
  [latI, lngI].forEach((i) => i.addEventListener('change', () => {
    const ll = L.latLng(parseFloat(latI.value), parseFloat(lngI.value));
    if (!isNaN(ll.lat) && !isNaN(ll.lng)) { mk.setLatLng(ll); map.panTo(ll); }
  }));
  setTimeout(() => map.invalidateSize(), 200);
}

/** Selector de posición sobre el mapa ilustrado (porcentajes del dibujo) */
function initSvgPicker() {
  const box = document.getElementById('svgPicker');
  if (!box) return;
  const pin = document.getElementById('svgPin'), x = document.getElementById('map_x'), y = document.getElementById('map_y');
  const dep = document.querySelector('select[name="departamento_id"]');
  const marcarDepto = () => {
    const nombre = dep.options[dep.selectedIndex]?.text || '';
    box.querySelectorAll('.depto-shape').forEach((p) => p.classList.toggle('sel', p.dataset.nombre === nombre));
  };
  dep.addEventListener('change', marcarDepto); marcarDepto();
  box.addEventListener('click', (e) => {
    const r = box.getBoundingClientRect();
    const px = ((e.clientX - r.left) / r.width * 100).toFixed(3), py = ((e.clientY - r.top) / r.height * 100).toFixed(3);
    x.value = px; y.value = py;
    pin.style.display = ''; pin.style.left = px + '%'; pin.style.top = py + '%';
    const hint = document.getElementById('pickHint'); if (hint) hint.textContent = '';
    const shape = e.target.closest('.depto-shape');
    if (shape && shape.dataset.nombre !== (dep.options[dep.selectedIndex]?.text || '')) {
      const opt = [...dep.options].find((o) => o.text === shape.dataset.nombre);
      if (opt && confirm(`El punto está en el departamento ${shape.dataset.nombre}. ¿Cambiar el departamento del lugar?`)) { dep.value = opt.value; marcarDepto(); }
    }
  });
}
