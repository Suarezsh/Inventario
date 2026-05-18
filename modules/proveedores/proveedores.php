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
        $razon = trim($_POST['razon_social'] ?? '');
        if (empty($razon)) {
            $error = 'La razón social es obligatoria';
        } elseif ($accion === 'crear' || $accion === 'editar') {
            $data = [
                'razon_social' => $razon,
                'ruc' => trim($_POST['ruc'] ?? ''),
                'contacto' => trim($_POST['contacto'] ?? ''),
                'telefono' => trim($_POST['telefono'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'direccion' => trim($_POST['direccion'] ?? ''),
                'sitio_web' => trim($_POST['sitio_web'] ?? ''),
                'notas' => trim($_POST['notas'] ?? ''),
                'activo' => isset($_POST['activo']) ? 1 : 0,
            ];
            if ($accion === 'crear') {
                $cols = implode(', ', array_keys($data));
                $vals = implode(', ', array_fill(0, count($data), '?'));
                $db->prepare("INSERT INTO proveedores ($cols) VALUES ($vals)")->execute(array_values($data));
                registrarAuditoria(USUARIO_ID, 'crear', 'proveedores', (int)$db->lastInsertId(), "Proveedor creado: $razon");
                $success = 'Proveedor creado';
            } else {
                $id = (int)($_POST['id'] ?? 0);
                $sets = implode(' = ?, ', array_keys($data)) . ' = ?';
                $params = array_values($data);
                $params[] = $id;
                $db->prepare("UPDATE proveedores SET $sets WHERE id = ?")->execute($params);
                registrarAuditoria(USUARIO_ID, 'editar', 'proveedores', $id, "Proveedor editado: $razon");
                $success = 'Proveedor actualizado';
            }
        } elseif ($accion === 'eliminar') {
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare("UPDATE proveedores SET activo = 0 WHERE id = ?")->execute([$id]);
            registrarAuditoria(USUARIO_ID, 'eliminar', 'proveedores', $id, 'Proveedor desactivado');
            $success = 'Proveedor desactivado';
        }
    }
}

$proveedores = $db->query("SELECT p.*, (SELECT COUNT(*) FROM producto_proveedor WHERE id_proveedor = p.id) as productos_asociados FROM proveedores p ORDER BY p.razon_social")->fetchAll();

$titulo = 'Proveedores';
$subtitulo = 'Proveedores';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Nuevo Proveedor</h5></div>
            <div class="card-body">
                <?php if (isset($error)): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
                <?php if (isset($success)): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
                <form method="POST">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="accion" value="crear">
                    <div class="mb-3">
                        <label class="form-label">Razón Social <span class="text-danger">*</span></label>
                        <input type="text" name="razon_social" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">RUC / NIT</label>
                        <input type="text" name="ruc" class="form-control">
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Contacto</label>
                            <input type="text" name="contacto" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <textarea name="direccion" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sitio Web</label>
                        <input type="url" name="sitio_web" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notas</label>
                        <textarea name="notas" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Crear</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Lista de Proveedores</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr><th>RUC</th><th>Razón Social</th><th>Contacto</th><th>Teléfono</th><th>Email</th><th>Prod.</th><th>Estado</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proveedores as $prov): ?>
                            <tr>
                                <td><?= h($prov['ruc']) ?></td>
                                <td><?= h($prov['razon_social']) ?></td>
                                <td><?= h($prov['contacto']) ?></td>
                                <td><?= h($prov['telefono']) ?></td>
                                <td><?= h($prov['email']) ?></td>
                                <td><span class="badge bg-info"><?= $prov['productos_asociados'] ?></span></td>
                                <td><?= $prov['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editarProv(<?= $prov['id'] ?>)"><i class="bi bi-pencil"></i></button>
                                    <?php if ($prov['activo']): ?>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarProv(<?= $prov['id'] ?>)"><i class="bi bi-trash"></i></button>
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

<script>
function editarProv(id) {
    window.location.href = '<?= BASE_URL ?>/modules/proveedores/proveedor_form.php?id=' + id;
}
function eliminarProv(id) {
    Swal.fire({
        title: '¿Desactivar proveedor?',
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, desactivar', cancelButtonText: 'Cancelar'
    }).then(r => {
        if (r.isConfirmed) {
            $('<form method="POST"><?= campoCSRF() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="' + id + '"></form>').appendTo('body').submit();
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
