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
        $nombre = trim($_POST['nombre'] ?? '');
        $codigo = trim($_POST['codigo'] ?? '');
        if (empty($nombre) || empty($codigo)) {
            $error = 'Nombre y código son obligatorios';
        } elseif ($accion === 'crear' || $accion === 'editar') {
            $data = [
                'codigo' => $codigo, 'nombre' => $nombre, 'ubicacion' => trim($_POST['ubicacion'] ?? ''),
                'encargado' => trim($_POST['encargado'] ?? ''), 'telefono' => trim($_POST['telefono'] ?? ''),
                'activo' => isset($_POST['activo']) ? 1 : 0,
            ];
            if ($accion === 'crear') {
                $check = $db->prepare("SELECT COUNT(*) FROM almacenes WHERE codigo = ?");
                $check->execute([$codigo]);
                if ($check->fetchColumn() > 0) {
                    $error = 'El código de almacén ya existe';
                } else {
                    $cols = implode(', ', array_keys($data));
                    $vals = implode(', ', array_fill(0, count($data), '?'));
                    $db->prepare("INSERT INTO almacenes ($cols) VALUES ($vals)")->execute(array_values($data));
                    registrarAuditoria(USUARIO_ID, 'crear', 'almacenes', (int)$db->lastInsertId(), "Almacén creado: $nombre");
                    $success = 'Almacén creado';
                }
            } else {
                $id = (int)($_POST['id'] ?? 0);
                $sets = implode(' = ?, ', array_keys($data)) . ' = ?';
                $params = array_values($data);
                $params[] = $id;
                $db->prepare("UPDATE almacenes SET $sets WHERE id = ?")->execute($params);
                registrarAuditoria(USUARIO_ID, 'editar', 'almacenes', $id, "Almacén editado: $nombre");
                $success = 'Almacén actualizado';
            }
        } elseif ($accion === 'eliminar') {
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare("UPDATE almacenes SET activo = 0 WHERE id = ?")->execute([$id]);
            registrarAuditoria(USUARIO_ID, 'eliminar', 'almacenes', $id, 'Almacén desactivado');
            $success = 'Almacén desactivado';
        }
    }
}

$almacenes = $db->query("SELECT a.*, (SELECT SUM(stock_actual) FROM producto_almacen WHERE id_almacen = a.id) as total_productos FROM almacenes a ORDER BY a.nombre")->fetchAll();

$titulo = 'Almacenes';
$subtitulo = 'Almacenes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Nuevo Almacén</h5></div>
            <div class="card-body">
                <?php if (isset($error)): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
                <?php if (isset($success)): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
                <form method="POST">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="accion" value="crear">
                    <div class="mb-3">
                        <label class="form-label">Código <span class="text-danger">*</span></label>
                        <input type="text" name="codigo" class="form-control" placeholder="ALM-001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ubicación</label>
                        <input type="text" name="ubicacion" class="form-control">
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Encargado</label>
                            <input type="text" name="encargado" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Crear</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Lista de Almacenes</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr><th>Código</th><th>Nombre</th><th>Ubicación</th><th>Encargado</th><th>Productos</th><th>Estado</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($almacenes as $a): ?>
                            <tr>
                                <td><?= h($a['codigo']) ?></td>
                                <td><a href="<?= BASE_URL ?>/modules/almacenes/stock_almacen.php?id=<?= $a['id'] ?>"><?= h($a['nombre']) ?></a></td>
                                <td><?= h($a['ubicacion']) ?></td>
                                <td><?= h($a['encargado']) ?></td>
                                <td><span class="badge bg-info"><?= (int)$a['total_productos'] ?></span></td>
                                <td><?= $a['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editarAlm(<?= $a['id'] ?>)"><i class="bi bi-pencil"></i></button>
                                    <?php if ($a['activo']): ?>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarAlm(<?= $a['id'] ?>)"><i class="bi bi-trash"></i></button>
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
function editarAlm(id) { window.location.href = '<?= BASE_URL ?>/modules/almacenes/almacen_form.php?id=' + id; }
function eliminarAlm(id) {
    Swal.fire({
        title: '¿Desactivar almacén?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, desactivar', cancelButtonText: 'Cancelar'
    }).then(r => { if (r.isConfirmed) { $('<form method="POST"><?= campoCSRF() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="' + id + '"></form>').appendTo('body').submit(); } });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
