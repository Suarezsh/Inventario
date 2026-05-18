<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

$db = getDB();
$proveedores = $db->query("SELECT id, razon_social, ruc FROM proveedores WHERE activo = 1 ORDER BY razon_social")->fetchAll();
$almacenes = $db->query("SELECT id, nombre FROM almacenes WHERE activo = 1 ORDER BY nombre")->fetchAll();
$formasPago = $db->query("SELECT * FROM formas_pago WHERE activo = 1 ORDER BY nombre")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? null)) {
        $error = 'Token de seguridad inválido';
    } else {
        $productos = $_POST['producto_id'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        $costos = $_POST['costo_unitario'] ?? [];

        if (empty($productos) || count($productos) === 0) {
            $error = 'Debe agregar al menos un producto';
        } else {
            $idProveedor = $_POST['id_proveedor'] ?: null;
            $idAlmacen = (int)($_POST['id_almacen'] ?? 0);
            $fechaIngreso = $_POST['fecha_ingreso'] ?? date('Y-m-d');
            $fechaDoc = $_POST['fecha_documento'] ?? $fechaIngreso;
            $notas = trim($_POST['notas'] ?? '');
            $idFormaPago = $_POST['id_forma_pago'] ?: null;
            $tipoIngreso = $_POST['tipo_ingreso'] ?? 'compra';
            $numeroDoc = trim($_POST['numero_documento'] ?? generarNumeroDocumento('ING'));

            $subtotal = 0;
            $items = [];
            foreach ($productos as $idx => $prodId) {
                $prodId = (int)$prodId;
                $cant = (float)($cantidades[$idx] ?? 0);
                $costo = (float)($costos[$idx] ?? 0);
                if ($prodId > 0 && $cant > 0) {
                    $items[] = ['id_producto' => $prodId, 'cantidad' => $cant, 'costo_unitario' => $costo, 'subtotal' => $cant * $costo];
                    $subtotal += $cant * $costo;
                }
            }

            if (empty($items)) {
                $error = 'Complete los datos de los productos';
            } else {
                $impuestoPct = (float) config('impuesto_porcentaje', 0);
                $impuesto = $subtotal * $impuestoPct / 100;
                $total = $subtotal + $impuesto;

                try {
                    $db->beginTransaction();
                    $stmt = $db->prepare("INSERT INTO ingresos (tipo_ingreso, numero_documento, id_proveedor, fecha_ingreso, fecha_documento, id_almacen, subtotal, impuesto, total, id_forma_pago, notas, creado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$tipoIngreso, $numeroDoc, $idProveedor, $fechaIngreso, $fechaDoc, $idAlmacen, $subtotal, $impuesto, $total, $idFormaPago, $notas, USUARIO_ID]);
                    $idIngreso = (int)$db->lastInsertId();

                    $stmtDet = $db->prepare("INSERT INTO ingreso_detalles (id_ingreso, id_producto, cantidad, costo_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
                    foreach ($items as $item) {
                        $stmtDet->execute([$idIngreso, $item['id_producto'], $item['cantidad'], $item['costo_unitario'], $item['subtotal']]);
                        registrarMovimiento($item['id_producto'], $idAlmacen, 'entrada', 'Ingreso', $idIngreso, $item['cantidad'], $item['costo_unitario'], USUARIO_ID);

                        $db->prepare("UPDATE productos SET costo_promedio = CASE WHEN costo_promedio > 0 THEN (costo_promedio + ?) / 2 ELSE ? END WHERE id = ?")->execute([$item['costo_unitario'], $item['costo_unitario'], $item['id_producto']]);
                    }

                    $db->commit();
                    registrarAuditoria(USUARIO_ID, 'crear', 'ingresos', $idIngreso, "Ingreso N° $numeroDoc creado");
                    $success = 'Ingreso registrado correctamente';
                    echo '<script>setTimeout(() => location.href = "' . BASE_URL . '/modules/ingresos/ingresos.php", 1500);</script>';
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Error al registrar el ingreso: ' . $e->getMessage();
                }
            }
        }
    }
}

$titulo = 'Nuevo Ingreso';
$subtitulo = 'Ingresos';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">Registrar Ingreso / Compra</h5></div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
        <form method="POST" id="formIngreso">
            <?= campoCSRF() ?>
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select name="tipo_ingreso" class="form-select">
                        <option value="compra">Compra</option>
                        <option value="devolucion_cliente">Devolución de Cliente</option>
                        <option value="ajuste_positivo">Ajuste Positivo</option>
                        <option value="produccion">Producción / Fabricación</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">N° Documento</label>
                    <input type="text" name="numero_documento" class="form-control" value="<?= generarNumeroDocumento('ING') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Proveedor</label>
                    <select name="id_proveedor" class="form-select select2">
                        <option value="">Sin proveedor</option>
                        <?php foreach ($proveedores as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= h($p['razon_social']) ?> (<?= h($p['ruc']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Almacén</label>
                    <select name="id_almacen" class="form-select" required>
                        <?php foreach ($almacenes as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= h($a['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Ingreso</label>
                    <input type="date" name="fecha_ingreso" class="form-control flatpickr" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Documento</label>
                    <input type="date" name="fecha_documento" class="form-control flatpickr" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Forma de Pago</label>
                    <select name="id_forma_pago" class="form-select">
                        <option value="">Seleccionar</option>
                        <?php foreach ($formasPago as $fp): ?>
                        <option value="<?= $fp['id'] ?>"><?= h($fp['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Notas</label>
                    <input type="text" name="notas" class="form-control">
                </div>
            </div>

            <hr>
            <h6>Productos</h6>
            <div class="table-responsive">
                <table class="table table-bordered" id="tablaProductos">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40%">Producto</th>
                            <th style="width:20%">Cantidad</th>
                            <th style="width:20%">Costo Unitario</th>
                            <th style="width:15%">Subtotal</th>
                            <th style="width:5%"></th>
                        </tr>
                    </thead>
                    <tbody id="productosBody">
                        <tr>
                            <td>
                                <select name="producto_id[]" class="form-select select2-producto" required></select>
                            </td>
                            <td><input type="number" name="cantidad[]" class="form-control cantidad" step="0.01" min="0.01" required oninput="calcularFila(this)"></td>
                            <td><input type="number" name="costo_unitario[]" class="form-control costo" step="0.01" min="0" required oninput="calcularFila(this)"></td>
                            <td><input type="text" class="form-control subtotal" readonly></td>
                            <td><button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(this)"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">
                                <button type="button" class="btn btn-success btn-sm" onclick="agregarFila()"><i class="bi bi-plus-lg"></i> Agregar Producto</button>
                            </td>
                            <td class="text-end"><strong>Total:</strong></td>
                            <td><input type="text" id="totalGlobal" class="form-control" readonly></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <hr>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Registrar Ingreso</button>
            <a href="<?= BASE_URL ?>/modules/ingresos/ingresos.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>

<script>
let filaIdx = 0;
function agregarFila() {
    const html = `<tr>
        <td><select name="producto_id[]" class="form-select select2-producto" required></select></td>
        <td><input type="number" name="cantidad[]" class="form-control cantidad" step="0.01" min="0.01" required oninput="calcularFila(this)"></td>
        <td><input type="number" name="costo_unitario[]" class="form-control costo" step="0.01" min="0" required oninput="calcularFila(this)"></td>
        <td><input type="text" class="form-control subtotal" readonly></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(this)"><i class="bi bi-trash"></i></button></td>
    </tr>`;
    $('#productosBody').append(html);
    $('.select2-producto').select2({
        theme: 'bootstrap-5', width: '100%',
        ajax: { url: '<?= BASE_URL ?>/modules/productos/buscar.php', dataType: 'json', delay: 250,
            data: function(p) { return { q: p.term }; },
            processResults: function(d) { return { results: d }; }, cache: true },
        minimumInputLength: 1, placeholder: 'Buscar producto...'
    });
}
function eliminarFila(btn) {
    $(btn).closest('tr').remove();
    calcularTotal();
}
function calcularFila(el) {
    const tr = $(el).closest('tr');
    const cant = parseFloat(tr.find('.cantidad').val()) || 0;
    const costo = parseFloat(tr.find('.costo').val()) || 0;
    tr.find('.subtotal').val((cant * costo).toFixed(2));
    calcularTotal();
}
function calcularTotal() {
    let total = 0;
    $('.subtotal').each(function() { total += parseFloat($(this).val()) || 0; });
    $('#totalGlobal').val(total.toFixed(2));
}
$(document).ready(function() {
    $('.select2-producto').select2({
        theme: 'bootstrap-5', width: '100%',
        ajax: { url: '<?= BASE_URL ?>/modules/productos/buscar.php', dataType: 'json', delay: 250,
            data: function(p) { return { q: p.term }; },
            processResults: function(d) { return { results: d }; }, cache: true },
        minimumInputLength: 1, placeholder: 'Buscar producto...'
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
