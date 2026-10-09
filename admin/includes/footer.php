  </div>
  <footer class="admin-footer">Panel de administración · Desarrollado con <i class="fa-solid fa-heart"></i> por <strong>Dalila Spindola Dev</strong></footer>
</main>

<!-- Modal de confirmación genérico -->
<div class="modal fade" id="modalConfirm" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content confirm-modal">
      <div class="modal-body text-center p-4">
        <div class="confirm-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h5 class="mt-3" id="confirmTitulo">¿Estás segura/o?</h5>
        <p class="text-muted small mb-4" id="confirmTexto">Esta acción no se puede deshacer.</p>
        <div class="d-flex gap-2 justify-content-center">
          <button class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-danger" id="confirmOk">Sí, eliminar</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($usarMapa)): ?><script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script><?php endif; ?>
<?php if (!empty($usarSortable)): ?><script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script><?php endif; ?>
<script src="<?= asset('assets/admin.js', __DIR__ . '/..') ?>"></script>
<?= $scriptsExtra ?? '' ?>
</body>
</html>
