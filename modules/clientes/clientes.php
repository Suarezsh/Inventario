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
        if (empty($nombre)) {
            $error = 'El nombre es obligatorio';
        } elseif ($accion === 'crear') {
            $db->prepare("INSERT INTO clientes (tipo_documento, numero_documento, nombre, direccion, telefono, email, tipo_cliente, limite_credito, dias_credito, descuento_especial, notas, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)")->execute([
                $_POST['tipo_documento'] ?? 'DNI', trim($_POST['numero_documento'] ?? ''), $nombre,
                trim($_POST['direccion'] ?? ''), trim($_POST['telefono'] ?? ''), trim($_POST['email'] ?? ''),
                $_POST['tipo_cliente'] ?? 'minorista', (float)($_POST['limite_credito'] ?? 0), (int)($_POST['dias_credito'] ?? 0),
                (float)($_POST['descuento_especial'] ?? 0), trim($_POST['notas'] ?? '')
            ]);
            registrarAuditoria(USUARIO_ID, 'crear', 'clientes', (int)$db->lastInsertId(), "Cliente creado: $nombre");
            $success = 'Cliente creado';
        } elseif ($accion === 'editar') {
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare("UPDATE clientes SET tipo_documento = ?, numero_documento = ?, nombre = ?, direccion = ?, telefono = ?, email = ?, tipo_cliente = ?, limite_credito = ?, dias_credito = ?, descuento_especial = ?, notas = ?, activo = ? WHERE id = ?")->execute([
                $_POST['tipo_documento'] ?? 'DNI', trim($_POST['numero_documento'] ?? ''), $nombre,
                trim($_POST['direccion'] ?? ''), trim($_POST['telefono'] ?? ''), trim($_POST['email'] ?? ''),
                $_POST['tipo_cliente'] ?? 'minorista', (float)($_POST['limite_credito'] ?? 0), (int)($_POST['dias_credito'] ?? 0),
                (float)($_POST['descuento_especial'] ?? 0), trim($_POST['notas'] ?? ''), isset($_POST['activo']) ? 1 : 0, $id
            ]);
            registrarAuditoria(USUARIO_ID, 'editar', 'clientes', $id, "Cliente editado: $nombre");
            $success = 'Cliente actualizado';
        } elseif ($accion === 'eliminar') {
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare("UPDATE clientes SET activo = 0 WHERE id = ?")->execute([$id]);
            registrarAuditoria(USUARIO_ID, 'eliminar', 'clientes', $id, 'Cliente desactivado');
            $success = 'Cliente desactivado';
        }
    }
}

$clientes = $db->query("SELECT * FROM clientes ORDER BY nombre")->fetchAll();

$titulo = 'Clientes';
$subtitulo = 'Clientes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Nuevo Cliente</h5></div>
            <div class="card-body">
                <?php if (isset($error)): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
                <?php if (isset($success)): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
                <form method="POST">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="accion" value="crear">
                    <div class="mb-3">
                        <label class="form-label">Nombre / Razón Social <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4">
                            <label class="form-label">Tipo Doc.</label>
                            <select name="tipo_documento" class="form-select">
                                <option value="DNI">DNI</option>
                                <option value="RUC">RUC</option>
                                <option value="CE">CE</option>
                                <option value="Pasaporte">Pasaporte</option>
                            </select>
                        </div>
                        <div class="col-8">
                            <label class="form-label">N° Documento</label>
                            <input type="text" name="numero_documento" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" class="form-control">
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo Cliente</label>
                        <select name="tipo_cliente" class="form-select">
                            <option value="minorista">Minorista</option>
                            <option value="mayorista">Mayorista</option>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Límite Crédito</label>
                            <input type="number" name="limite_credito" class="form-control" step="0.01" value="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Días Crédito</label>
                            <input type="number" name="dias_credito" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dto. Especial (%)</label>
                        <input type="number" name="descuento_especial" class="form-control" step="0.01" value="0">
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
            <div class="card-header"><h5 class="card-title mb-0">Lista de Clientes</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr><th>Documento</th><th>Nombre</th><th>Teléfono</th><th>Email</th><th>Tipo</th><th>Crédito</th><th>Estado</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cli): ?>
                            <tr>
                                <td><?= h($cli['tipo_documento']) ?>: <?= h($cli['numero_documento']) ?></td>
                                <td><?= h($cli['nombre']) ?></td>
                                <td><?= h($cli['telefono']) ?></td>
                                <td><?= h($cli['email']) ?></td>
                                <td><span class="badge bg-<?= $cli['tipo_cliente'] === 'mayorista' ? 'warning' : 'info' ?>"><?= h(ucfirst($cli['tipo_cliente'])) ?></span></td>
                                <td><?= money($cli['limite_credito'], config('moneda_simbolo', '$')) ?></td>
                                <td><?= $cli['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editarCliente(<?= $cli['id'] ?>)"><i class="bi bi-pencil"></i></button>
                                    <?php if ($cli['activo']): ?>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarCliente(<?= $cli['id'] ?>)"><i class="bi bi-trash"></i></button>
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
function editarCliente(id) {
    window.location.href = '<?= BASE_URL ?>/modules/clientes/cliente_form.php?id=' + id;
}
function eliminarCliente(id) {
    Swal.fire({
        title: '¿Desactivar cliente?',
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
