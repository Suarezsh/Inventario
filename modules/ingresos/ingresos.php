<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

$db = getDB();
$ingresos = $db->query("SELECT i.*, p.razon_social as proveedor, a.nombre as almacen, u.nombre_completo as usuario FROM ingresos i LEFT JOIN proveedores p ON i.id_proveedor = p.id JOIN almacenes a ON i.id_almacen = a.id LEFT JOIN usuarios u ON i.creado_por = u.id ORDER BY i.fecha_creacion DESC")->fetchAll();

$titulo = 'Ingresos';
$subtitulo = 'Ingresos';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Lista de Ingresos / Compras</h5>
        <a href="<?= BASE_URL ?>/modules/ingresos/ingreso_nuevo.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo Ingreso
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr><th>N° Doc</th><th>Fecha</th><th>Proveedor</th><th>Almacén</th><th>Tipo</th><th>Total</th><th>Usuario</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($ingresos as $i): ?>
                    <tr class="<?= $i['anulado'] ? 'text-decoration-line-through text-muted' : '' ?>">
                        <td><?= h($i['numero_documento']) ?></td>
                        <td><?= fdate($i['fecha_ingreso'], 'd/m/Y') ?></td>
                        <td><?= h($i['proveedor'] ?? 'N/A') ?></td>
                        <td><?= h($i['almacen']) ?></td>
                        <td><span class="badge bg-<?= $i['tipo_ingreso'] === 'compra' ? 'primary' : 'info' ?>"><?= h(ucfirst($i['tipo_ingreso'])) ?></span></td>
                        <td><?= money($i['total'], config('moneda_simbolo', '$')) ?></td>
                        <td><?= h($i['usuario']) ?></td>
                        <td><?= $i['anulado'] ? '<span class="badge bg-danger">Anulado</span>' : '<span class="badge bg-success">Activo</span>' ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/modules/ingresos/ingreso_detalle.php?id=<?= $i['id'] ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                            <?php if (!$i['anulado'] && tienePermiso(USUARIO_ROL, 'ingresos', 'eliminar')): ?>
                            <button class="btn btn-sm btn-danger" onclick="anularIngreso(<?= $i['id'] ?>)"><i class="bi bi-x-circle"></i></button>
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
function anularIngreso(id) {
    Swal.fire({
        title: '¿Anular ingreso?',
        text: 'El stock se revertirá automáticamente',
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, anular', cancelButtonText: 'Cancelar'
    }).then(r => {
        if (r.isConfirmed) {
            $.post('<?= BASE_URL ?>/modules/ingresos/ingreso_anular.php', { id: id, csrf_token: '<?= obtenerTokenCSRF() ?>' }, function(res) {
                if (res.success) { toastSuccess('Ingreso anulado'); setTimeout(() => location.reload(), 1000); }
                else { toastError(res.error); }
            }, 'json');
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
