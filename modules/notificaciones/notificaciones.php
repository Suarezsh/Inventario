<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'marcar_leido') {
    $id = (int)($_POST['id'] ?? 0);
    $db->prepare("UPDATE notificaciones SET leido = 1, fecha_lectura = datetime('now','localtime') WHERE id = ?")->execute([$id]);
    jsonResponse(['success' => true]);
}

$notificaciones = $db->query("SELECT * FROM notificaciones ORDER BY fecha_creacion DESC")->fetchAll();

$db->exec("INSERT OR IGNORE INTO notificaciones (tipo, titulo, mensaje, modulo_referencia) SELECT 'info', 'Sistema instalado', 'El sistema de inventario se ha instalado correctamente. Configure los módulos en Configuración.', 'configuracion' WHERE (SELECT COUNT(*) FROM notificaciones) = 0");

$titulo = 'Notificaciones';
$subtitulo = 'Notificaciones';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Centro de Notificaciones</h5>
        <span class="badge bg-danger"><?= $db->query("SELECT COUNT(*) FROM notificaciones WHERE leido = 0")->fetchColumn() ?> sin leer</span>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            <?php $notificaciones = $db->query("SELECT * FROM notificaciones ORDER BY fecha_creacion DESC LIMIT 50")->fetchAll(); ?>
            <?php if (empty($notificaciones)): ?>
            <div class="list-group-item text-center text-muted py-4">No hay notificaciones</div>
            <?php else: ?>
            <?php foreach ($notificaciones as $n): ?>
            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-start <?= !$n['leido'] ? 'list-group-item-warning' : '' ?>">
                <div class="ms-2 me-auto">
                    <div class="fw-bold">
                        <i class="bi bi-<?= $n['tipo'] === 'danger' ? 'exclamation-triangle-fill text-danger' : ($n['tipo'] === 'warning' ? 'exclamation-circle-fill text-warning' : 'info-circle-fill text-info') ?> me-2"></i>
                        <?= h($n['titulo']) ?>
                        <?php if (!$n['leido']): ?><span class="badge bg-danger ms-2">Nuevo</span><?php endif; ?>
                    </div>
                    <p class="mb-0 text-muted small"><?= h($n['mensaje']) ?></p>
                    <small class="text-muted"><?= fdatetime($n['fecha_creacion'], 'd/m/Y H:i') ?></small>
                </div>
                <?php if (!$n['leido']): ?>
                <button class="btn btn-sm btn-outline-secondary" onclick="marcarLeido(<?= $n['id'] ?>)">
                    <i class="bi bi-check-lg"></i>
                </button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function marcarLeido(id) {
    $.post('<?= BASE_URL ?>/modules/notificaciones/notificaciones.php', { accion: 'marcar_leido', id: id }, function(res) {
        if (res.success) location.reload();
    }, 'json');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
