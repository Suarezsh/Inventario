<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? null)) {
        $error = 'Token inválido';
    } else {
        $accion = $_POST['accion'];
        if ($accion === 'crear' || $accion === 'editar') {
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $activo = isset($_POST['activo']) ? 1 : 0;
            if (empty($nombre)) {
                $error = 'El nombre es obligatorio';
            } elseif ($accion === 'crear') {
                $db->prepare("INSERT INTO categorias (nombre, descripcion, activo) VALUES (?, ?, ?)")->execute([$nombre, $descripcion, $activo]);
                registrarAuditoria(USUARIO_ID, 'crear', 'categorias', (int)$db->lastInsertId(), "Categoría creada: $nombre");
                $success = 'Categoría creada';
            } else {
                $id = (int) ($_POST['id'] ?? 0);
                $db->prepare("UPDATE categorias SET nombre = ?, descripcion = ?, activo = ? WHERE id = ?")->execute([$nombre, $descripcion, $activo, $id]);
                registrarAuditoria(USUARIO_ID, 'editar', 'categorias', $id, "Categoría editada: $nombre");
                $success = 'Categoría actualizada';
            }
        } elseif ($accion === 'eliminar') {
            $id = (int) ($_POST['id'] ?? 0);
            $db->prepare("UPDATE categorias SET activo = 0 WHERE id = ?")->execute([$id]);
            registrarAuditoria(USUARIO_ID, 'eliminar', 'categorias', $id, 'Categoría desactivada');
            $success = 'Categoría desactivada';
        }
    }
}

$categorias = $db->query("SELECT c.*, (SELECT COUNT(*) FROM productos WHERE id_categoria = c.id AND activo = 1) as total_productos FROM categorias c ORDER BY c.nombre")->fetchAll();

$titulo = 'Categorías';
$subtitulo = 'Categorías';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Nueva Categoría</h5></div>
            <div class="card-body">
                <?php if (isset($error)): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
                <?php if (isset($success)): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
                <form method="POST">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="accion" value="crear">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="activo" class="form-check-input" id="activo" checked>
                        <label class="form-check-label" for="activo">Activo</label>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Crear</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Lista de Categorías</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Productos</th><th>Estado</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorias as $c): ?>
                            <tr>
                                <td><?= $c['id'] ?></td>
                                <td><?= h($c['nombre']) ?></td>
                                <td><?= h($c['descripcion']) ?></td>
                                <td><span class="badge bg-info"><?= $c['total_productos'] ?></span></td>
                                <td><?= $c['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editarCategoria(<?= $c['id'] ?>, '<?= h($c['nombre'], ENT_QUOTES) ?>', '<?= h($c['descripcion'], ENT_QUOTES) ?>', <?= $c['activo'] ?>)"><i class="bi bi-pencil"></i></button>
                                    <?php if ($c['activo']): ?>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarCategoria(<?= $c['id'] ?>)"><i class="bi bi-trash"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Editar Categoría</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" id="edit_descripcion" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="activo" class="form-check-input" id="edit_activo">
                        <label class="form-check-label" for="edit_activo">Activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarCategoria(id, nombre, descripcion, activo) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nombre').value = nombre;
    document.getElementById('edit_descripcion').value = descripcion;
    document.getElementById('edit_activo').checked = activo === 1;
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
function eliminarCategoria(id) {
    Swal.fire({
        title: '¿Desactivar categoría?',
        text: 'Los productos asociados no se verán afectados',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, desactivar',
        cancelButtonText: 'Cancelar'
    }).then(r => {
        if (r.isConfirmed) {
            $('<form method="POST"><?= campoCSRF() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="' + id + '"></form>').appendTo('body').submit();
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
