<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

$db = getDB();
$salidas = $db->query("SELECT s.*, c.nombre as cliente, a.nombre as almacen, u.nombre_completo as usuario FROM salidas s LEFT JOIN clientes c ON s.id_cliente = c.id JOIN almacenes a ON s.id_almacen = a.id LEFT JOIN usuarios u ON s.creado_por = u.id ORDER BY s.fecha_creacion DESC")->fetchAll();

$titulo = 'Salidas';
$subtitulo = 'Salidas';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Lista de Salidas / Ventas</h5>
        <a href="<?= BASE_URL ?>/modules/salidas/salida_nueva.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nueva Salida
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr><th>N° Doc</th><th>Fecha</th><th>Cliente</th><th>Almacén</th><th>Tipo</th><th>Total</th><th>Usuario</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($salidas as $s): ?>
                    <tr class="<?= $s['anulado'] ? 'text-decoration-line-through text-muted' : '' ?>">
                        <td><?= h($s['numero_documento']) ?></td>
                        <td><?= fdate($s['fecha_salida'], 'd/m/Y') ?></td>
                        <td><?= h($s['cliente'] ?? 'N/A') ?></td>
                        <td><?= h($s['almacen']) ?></td>
                        <td><span class="badge bg-<?= $s['tipo_salida'] === 'venta' ? 'success' : 'warning' ?>"><?= h(ucfirst($s['tipo_salida'])) ?></span></td>
                        <td><?= money($s['total'], config('moneda_simbolo', '$')) ?></td>
                        <td><?= h($s['usuario']) ?></td>
                        <td><?= $s['anulado'] ? '<span class="badge bg-danger">Anulado</span>' : '<span class="badge bg-success">Activo</span>' ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/modules/salidas/salida_detalle.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                            <?php if (!$s['anulado'] && tienePermiso(USUARIO_ROL, 'salidas', 'eliminar')): ?>
                            <button class="btn btn-sm btn-danger" onclick="anularSalida(<?= $s['id'] ?>)"><i class="bi bi-x-circle"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function anularSalida(id) {
    Swal.fire({
        title: '¿Anular salida?',
        text: 'El stock se revertirá automáticamente',
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, anular', cancelButtonText: 'Cancelar'
    }).then(r => {
        if (r.isConfirmed) {
            $.post('<?= BASE_URL ?>/modules/salidas/salida_anular.php', { id: id, csrf_token: '<?= obtenerTokenCSRF() ?>' }, function(res) {
                if (res.success) { toastSuccess('Salida anulada'); setTimeout(() => location.reload(), 1000); }
                else { toastError(res.error); }
            }, 'json');
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
