<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

$db = getDB();
$transferencias = $db->query("SELECT t.*, o.nombre as origen, d.nombre as destino, u.nombre_completo as usuario FROM transferencias t JOIN almacenes o ON t.id_almacen_origen = o.id JOIN almacenes d ON t.id_almacen_destino = d.id LEFT JOIN usuarios u ON t.creado_por = u.id ORDER BY t.fecha_creacion DESC")->fetchAll();

$titulo = 'Transferencias';
$subtitulo = 'Transferencias';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Lista de Transferencias</h5>
        <a href="<?= BASE_URL ?>/modules/transferencias/transferencia_nueva.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nueva Transferencia</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr><th>Guía</th><th>Fecha</th><th>Origen</th><th>Destino</th><th>Estado</th><th>Usuario</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($transferencias as $t): ?>
                    <tr>
                        <td><?= h($t['numero_guia']) ?></td>
                        <td><?= fdate($t['fecha_transferencia'], 'd/m/Y') ?></td>
                        <td><?= h($t['origen']) ?></td>
                        <td><?= h($t['destino']) ?></td>
                        <td>
                            <?php $estados = ['pendiente' => 'warning', 'en_transito' => 'info', 'completada' => 'success']; ?>
                            <span class="badge bg-<?= $estados[$t['estado']] ?? 'secondary'] ?>"><?= h(ucfirst(str_replace('_', ' ', $t['estado']))) ?></span>
                        </td>
                        <td><?= h($t['usuario']) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/modules/transferencias/transferencia_detalle.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                            <?php if ($t['estado'] === 'pendiente'): ?>
                            <a href="<?= BASE_URL ?>/modules/transferencias/transferencia_completar.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('¿Completar transferencia? Se actualizará el stock de ambos almacenes')"><i class="bi bi-check-lg"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
