<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

$db = getDB();
$ajustes = $db->query("SELECT a.*, al.nombre as almacen, u.nombre_completo as usuario FROM ajustes a JOIN almacenes al ON a.id_almacen = al.id LEFT JOIN usuarios u ON a.creado_por = u.id ORDER BY a.fecha_creacion DESC")->fetchAll();

$titulo = 'Ajustes de Inventario';
$subtitulo = 'Ajustes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Lista de Ajustes</h5>
        <a href="<?= BASE_URL ?>/modules/ajustes/ajuste_nuevo.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo Ajuste</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr><th>ID</th><th>Fecha</th><th>Tipo</th><th>Motivo</th><th>Almacén</th><th>Usuario</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($ajustes as $a): ?>
                    <tr>
                        <td>#<?= $a['id'] ?></td>
                        <td><?= fdate($a['fecha_ajuste'], 'd/m/Y') ?></td>
                        <td><span class="badge bg-<?= $a['tipo_ajuste'] === 'entrada' ? 'success' : 'danger' ?>"><?= h(ucfirst($a['tipo_ajuste'])) ?></span></td>
                        <td><?= h($a['motivo']) ?></td>
                        <td><?= h($a['almacen']) ?></td>
                        <td><?= h($a['usuario']) ?></td>
                        <td><a href="<?= BASE_URL ?>/modules/ajustes/ajuste_detalle.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
