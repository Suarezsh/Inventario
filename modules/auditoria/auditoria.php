<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';

$db = getDB();

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$logs = $db->prepare("SELECT * FROM auditoria_logs WHERE date(fecha_hora) BETWEEN ? AND ? ORDER BY fecha_hora DESC");
$logs->execute([$desde, $hasta]);
$items = $logs->fetchAll();

$titulo = 'Auditoría del Sistema';
$subtitulo = 'Auditoría';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">Registro de Auditoría</h5></div>
    <div class="card-body">
        <form method="GET" class="row g-3 mb-4">
            <div class="col-auto">
                <label class="form-label">Desde</label>
                <input type="date" name="desde" class="form-control" value="<?= $desde ?>">
            </div>
            <div class="col-auto">
                <label class="form-label">Hasta</label>
                <input type="date" name="hasta" class="form-control" value="<?= $hasta ?>">
            </div>
            <div class="col-auto">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary form-control"><i class="bi bi-filter"></i> Filtrar</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover table-sm datatable">
                <thead>
                    <tr><th>Fecha/Hora</th><th>Usuario</th><th>Acción</th><th>Módulo</th><th>ID</th><th>Descripción</th><th>IP</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $l): ?>
                    <tr>
                        <td><?= fdatetime($l['fecha_hora'], 'd/m/Y H:i:s') ?></td>
                        <td><?= h($l['usuario_nombre']) ?></td>
                        <td><span class="badge bg-<?= $l['accion'] === 'crear' ? 'success' : ($l['accion'] === 'eliminar' || $l['accion'] === 'anular' ? 'danger' : 'warning') ?>"><?= h($l['accion']) ?></span></td>
                        <td><?= h($l['modulo']) ?></td>
                        <td><?= $l['id_registro'] ?? '-' ?></td>
                        <td><?= h($l['descripcion']) ?></td>
                        <td><code><?= h($l['direccion_ip']) ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
