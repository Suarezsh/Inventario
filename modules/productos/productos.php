<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

$db = getDB();
$productos = $db->query("SELECT p.*, c.nombre as categoria, u.abreviatura as um FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id LEFT JOIN unidades_medida u ON p.id_unidad_medida = u.id WHERE p.activo = 1 ORDER BY p.nombre")->fetchAll();

$titulo = 'Productos';
$subtitulo = 'Productos';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Lista de Productos</h5>
        <div>
            <a href="<?= BASE_URL ?>/modules/productos/producto_form.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Nuevo Producto
            </a>
            <a href="<?= BASE_URL ?>/modules/import_export/importar.php" class="btn btn-outline-secondary">
                <i class="bi bi-upload"></i> Importar
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio Compra</th>
                        <th>Precio Venta</th>
                        <th>Stock</th>
                        <th>UM</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $p): ?>
                    <?php $stock = obtenerStockProducto($p['id']); ?>
                    <tr>
                        <td><?= h($p['codigo']) ?></td>
                        <td><a href="<?= BASE_URL ?>/modules/productos/producto_detalle.php?id=<?= $p['id'] ?>"><?= h($p['nombre']) ?></a></td>
                        <td><?= h($p['categoria']) ?></td>
                        <td><?= money($p['precio_compra'], config('moneda_simbolo', '$')) ?></td>
                        <td><?= money($p['precio_venta'], config('moneda_simbolo', '$')) ?></td>
                        <td>
                            <span class="fw-bold <?= ($p['stock_minimo'] > 0 && $stock <= $p['stock_minimo']) ? 'text-danger' : '' ?>">
                                <?= $stock ?>
                            </span>
                            <?php if ($p['stock_minimo'] > 0 && $stock <= $p['stock_minimo']): ?>
                            <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                            <?php endif; ?>
                        </td>
                        <td><?= h($p['um']) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/modules/productos/producto_form.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                            <button onclick="eliminarProducto(<?= $p['id'] ?>)" class="btn btn-sm btn-danger" title="Desactivar"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function eliminarProducto(id) {
    Swal.fire({
        title: '¿Desactivar producto?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, desactivar',
        cancelButtonText: 'Cancelar'
    }).then(r => {
        if (r.isConfirmed) {
            $.post('<?= BASE_URL ?>/modules/productos/producto_eliminar.php', { id: id, csrf_token: '<?= obtenerTokenCSRF() ?>' }, function(res) {
                if (res.success) { toastSuccess('Producto desactivado'); setTimeout(() => location.reload(), 1000); }
                else { toastError(res.error); }
            }, 'json');
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
