<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? null)) {
        $error = 'Token inválido';
    } elseif ($_POST['accion'] === 'crear_backup') {
        $backupDir = __DIR__ . '/../../db/backups/';
        if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
        $filename = 'backup_' . date('Ymd_His') . '.db';
        $destino = $backupDir . $filename;
        if (copy(__DIR__ . '/../../db/inventario.db', $destino)) {
            $size = filesize($destino);
            $stmt = $db->prepare("INSERT INTO backups (nombre_archivo, tamano_bytes, tipo, creado_por) VALUES (?, ?, 'manual', ?)");
            $stmt->execute([$filename, $size, USUARIO_ID]);
            registrarAuditoria(USUARIO_ID, 'crear', 'backups', (int)$db->lastInsertId(), "Backup creado: $filename");
            $success = 'Backup creado correctamente';
        } else {
            $error = 'Error al crear el backup';
        }
    } elseif ($_POST['accion'] === 'restaurar') {
        $archivo = $_POST['archivo'] ?? '';
        $ruta = __DIR__ . '/../../db/backups/' . basename($archivo);
        if (file_exists($ruta)) {
            copy($ruta, __DIR__ . '/../../db/inventario.db');
            registrarAuditoria(USUARIO_ID, 'restaurar', 'backups', 0, "Base de datos restaurada desde: $archivo");
            $success = 'Base de datos restaurada. Recargue la página.';
        } else {
            $error = 'Archivo no encontrado';
        }
    }
}

$backups = $db->query("SELECT b.*, u.nombre_completo as usuario FROM backups b LEFT JOIN usuarios u ON b.creado_por = u.id ORDER BY b.fecha_creacion DESC")->fetchAll();

$titulo = 'Backups';
$subtitulo = 'Backups';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Crear Backup</h5></div>
            <div class="card-body">
                <?php if (isset($error)): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
                <?php if (isset($success)): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
                <form method="POST">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="accion" value="crear_backup">
                    <p class="text-muted">Crea una copia de seguridad de la base de datos actual.</p>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-arrow-down"></i> Crear Backup</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Restaurar</h5></div>
            <div class="card-body">
                <p class="text-muted small">Seleccione un backup para restaurar. Se reemplazará la base de datos actual.</p>
                <form method="POST" onsubmit="return confirm('¿Está seguro? Se perderán los datos actuales.')">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="accion" value="restaurar">
                    <select name="archivo" class="form-select mb-3" required>
                        <option value="">Seleccionar backup</option>
                        <?php foreach ($backups as $b): ?>
                        <option value="<?= h($b['nombre_archivo']) ?>"><?= h($b['nombre_archivo']) ?> (<?= number_format($b['tamano_bytes'] / 1024, 1) ?> KB)</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-arrow-clockwise"></i> Restaurar</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Historial de Backups</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr><th>Archivo</th><th>Tamaño</th><th>Tipo</th><th>Usuario</th><th>Fecha</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $b): ?>
                            <tr>
                                <td><?= h($b['nombre_archivo']) ?></td>
                                <td><?= number_format($b['tamano_bytes'] / 1024, 1) ?> KB</td>
                                <td><span class="badge bg-<?= $b['tipo'] === 'manual' ? 'primary' : 'info' ?>"><?= h($b['tipo']) ?></span></td>
                                <td><?= h($b['usuario']) ?></td>
                                <td><?= fdatetime($b['fecha_creacion'], 'd/m/Y H:i') ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/db/backups/<?= h($b['nombre_archivo']) ?>" class="btn btn-sm btn-success" download><i class="bi bi-download"></i></a>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
