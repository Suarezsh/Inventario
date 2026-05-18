<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

$db = getDB();
$almacenes = $db->query("SELECT id, nombre FROM almacenes WHERE activo = 1 ORDER BY nombre")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? null)) {
        $error = 'Token inválido';
    } else {
        $tipo = $_POST['tipo_ajuste'] ?? 'entrada';
        $motivo = trim($_POST['motivo'] ?? '');
        $idAlmacen = (int)($_POST['id_almacen'] ?? 0);
        $fecha = $_POST['fecha_ajuste'] ?? date('Y-m-d');
        $notas = trim($_POST['notas'] ?? '');
        $productos = $_POST['producto_id'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        $costos = $_POST['costo_unitario'] ?? [];

        if (empty($motivo)) {
            $error = 'El motivo es obligatorio';
        } elseif (empty($productos) || count($productos) === 0) {
            $error = 'Agregue al menos un producto';
        } else {
            $items = [];
            foreach ($productos as $idx => $prodId) {
                $prodId = (int)$prodId;
                $cant = (float)($cantidades[$idx] ?? 0);
                $costo = (float)($costos[$idx] ?? 0);
                if ($prodId > 0 && $cant > 0) {
                    if ($tipo === 'salida' && !verificarStockSuficiente($prodId, $idAlmacen, $cant)) {
                        $error = "Stock insuficiente en almacén para uno de los productos";
                        break;
                    }
                    $items[] = ['id_producto' => $prodId, 'cantidad' => $cant, 'costo_unitario' => $costo];
                }
            }

            if (!$error && !empty($items)) {
                try {
                    $db->beginTransaction();
                    $stmt = $db->prepare("INSERT INTO ajustes (tipo_ajuste, motivo, id_almacen, fecha_ajuste, notas, creado_por) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$tipo, $motivo, $idAlmacen, $fecha, $notas, USUARIO_ID]);
                    $idAjuste = (int)$db->lastInsertId();

                    $stmtDet = $db->prepare("INSERT INTO ajuste_detalles (id_ajuste, id_producto, cantidad, costo_unitario) VALUES (?, ?, ?, ?)");
                    foreach ($items as $item) {
                        $stmtDet->execute([$idAjuste, $item['id_producto'], $item['cantidad'], $item['costo_unitario']]);
                        $signo = $tipo === 'entrada' ? $item['cantidad'] : -$item['cantidad'];
                        registrarMovimiento($item['id_producto'], $idAlmacen, 'ajuste', 'Ajuste', $idAjuste, $signo, $item['costo_unitario'], USUARIO_ID);
                    }

                    $db->commit();
                    registrarAuditoria(USUARIO_ID, 'crear', 'ajustes', $idAjuste, "Ajuste $tipo creado: $motivo");
                    $success = 'Ajuste registrado';
                    echo '<script>setTimeout(() => location.href = "' . BASE_URL . '/modules/ajustes/ajustes.php", 1500);</script>';
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Error: ' . $e->getMessage();
                }
            }
        }
    }
}

$titulo = 'Nuevo Ajuste';
$subtitulo = 'Ajustes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">Registrar Ajuste de Inventario</h5></div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
        <form method="POST" id="formAjuste">
            <?= campoCSRF() ?>
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Tipo de Ajuste</label>
                    <select name="tipo_ajuste" class="form-select" id="tipoAjuste">
                        <option value="entrada">Entrada (sobrante)</option>
                        <option value="salida">Salida (faltante)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Motivo <span class="text-danger">*</span></label>
                    <select name="motivo" class="form-select" required>
                        <option value="Conteo físico">Conteo físico</option>
                        <option value="Diferencia de inventario">Diferencia de inventario</option>
                        <option value="Producto dañado">Producto dañado</option>
                        <option value="Producto vencido">Producto vencido</option>
                        <option value="Robo / pérdida">Robo / pérdida</option>
                        <option value="Corrección de sistema">Corrección de sistema</option>
                        <option value="Otro">Otro</option>
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
                    <label class="form-label">Fecha</label>
                    <input type="date" name="fecha_ajuste" class="form-control flatpickr" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Notas</label>
                    <input type="text" name="notas" class="form-control">
                </div>
            </div>

            <hr>
            <h6>Productos</h6>
            <div class="table-responsive">
                <table class="table table-bordered" id="tablaProductos">
                    <thead class="table-light">
                        <tr><th style="width:40%">Producto</th><th style="width:20%">Cantidad</th><th style="width:20%">Costo Unit.</th><th style="width:10%"></th></tr>
                    </thead>
                    <tbody id="productosBody">
                        <tr>
                            <td><select name="producto_id[]" class="form-select select2-producto" required></select></td>
                            <td><input type="number" name="cantidad[]" class="form-control" step="0.01" min="0.01" required></td>
                            <td><input type="number" name="costo_unitario[]" class="form-control" step="0.01" min="0" value="0"></td>
                            <td><button type="button" class="btn btn-danger btn-sm" onclick="$(this).closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="4"><button type="button" class="btn btn-success btn-sm" onclick="agregarFila()"><i class="bi bi-plus-lg"></i> Agregar Producto</button></td></tr>
                    </tfoot>
                </table>
            </div>

            <hr>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Registrar Ajuste</button>
            <a href="<?= BASE_URL ?>/modules/ajustes/ajustes.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>

<script>
function agregarFila() {
    $('#productosBody').append(`<tr>
        <td><select name="producto_id[]" class="form-select select2-producto" required></select></td>
        <td><input type="number" name="cantidad[]" class="form-control" step="0.01" min="0.01" required></td>
        <td><input type="number" name="costo_unitario[]" class="form-control" step="0.01" min="0" value="0"></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="$(this).closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
    </tr>`);
    initSelect2();
}
function initSelect2() {
    $('.select2-producto').select2({
        theme: 'bootstrap-5', width: '100%',
        ajax: { url: '<?= BASE_URL ?>/modules/productos/buscar.php', dataType: 'json', delay: 250,
            data: function(p) { return { q: p.term }; },
            processResults: function(d) { return { results: d }; }, cache: true },
        minimumInputLength: 1, placeholder: 'Buscar producto...'
    });
}
$(document).ready(initSelect2);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
