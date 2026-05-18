<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

$db = getDB();
$editar = false;
$p = [
    'id' => '', 'codigo' => '', 'codigo_barras' => '', 'nombre' => '', 'descripcion' => '',
    'id_categoria' => '', 'id_subcategoria' => '', 'id_unidad_medida' => '', 'precio_compra' => 0,
    'precio_venta' => 0, 'precio_mayorista' => 0, 'id_impuesto' => 1, 'stock_minimo' => config('stock_minimo_default', 0),
    'stock_maximo' => config('stock_maximo_default', 0), 'stock_seguridad' => 0, 'lead_time_dias' => 0,
    'ubicacion_almacen' => '', 'notas' => '', 'activo' => 1
];

if (isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $p = $stmt->fetch();
    if (!$p) redirect(BASE_URL . '/modules/productos/productos.php');
    $editar = true;
}

if (!$editar) {
    $last = $db->query("SELECT codigo FROM productos ORDER BY id DESC LIMIT 1")->fetchColumn();
    $num = $last ? (int)substr($last, -4) + 1 : 1;
    $p['codigo'] = 'PROD-' . str_pad($num, 4, '0', STR_PAD_LEFT);
}

$categorias = $db->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre")->fetchAll();
$unidades = $db->query("SELECT * FROM unidades_medida WHERE activo = 1 ORDER BY nombre")->fetchAll();
$impuestos = $db->query("SELECT * FROM impuestos WHERE activo = 1 ORDER BY nombre")->fetchAll();
$proveedores = $db->query("SELECT * FROM proveedores WHERE activo = 1 ORDER BY razon_social")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? null)) {
        $error = 'Token inválido';
    } else {
        $data = [
            'codigo' => trim($_POST['codigo'] ?? ''),
            'codigo_barras' => trim($_POST['codigo_barras'] ?? ''),
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'id_categoria' => $_POST['id_categoria'] ?: null,
            'id_subcategoria' => $_POST['id_subcategoria'] ?: null,
            'id_unidad_medida' => $_POST['id_unidad_medida'] ?: null,
            'precio_compra' => (float)($_POST['precio_compra'] ?? 0),
            'precio_venta' => (float)($_POST['precio_venta'] ?? 0),
            'precio_mayorista' => (float)($_POST['precio_mayorista'] ?? 0),
            'id_impuesto' => (int)($_POST['id_impuesto'] ?? 1),
            'stock_minimo' => (float)($_POST['stock_minimo'] ?? 0),
            'stock_maximo' => (float)($_POST['stock_maximo'] ?? 0),
            'stock_seguridad' => (float)($_POST['stock_seguridad'] ?? 0),
            'lead_time_dias' => (int)($_POST['lead_time_dias'] ?? 0),
            'ubicacion_almacen' => trim($_POST['ubicacion_almacen'] ?? ''),
            'notas' => trim($_POST['notas'] ?? ''),
            'activo' => isset($_POST['activo']) ? 1 : 0,
        ];

        if (empty($data['codigo']) || empty($data['nombre'])) {
            $error = 'Código y nombre son obligatorios';
        } else {
            $check = $db->prepare("SELECT COUNT(*) FROM productos WHERE codigo = ? AND id != ?");
            $check->execute([$data['codigo'], $editar ? $p['id'] : 0]);
            if ($check->fetchColumn() > 0) {
                $error = 'El código ya existe';
            } else {
                if ($editar) {
                    $sets = [];
                    $params = [];
                    foreach ($data as $k => $v) {
                        $sets[] = "$k = ?";
                        $params[] = $v;
                    }
                    $params[] = $p['id'];
                    $db->prepare("UPDATE productos SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
                    registrarAuditoria(USUARIO_ID, 'editar', 'productos', $p['id'], "Producto editado: {$data['nombre']}");
                    $success = 'Producto actualizado';
                } else {
                    $cols = implode(', ', array_keys($data));
                    $vals = implode(', ', array_fill(0, count($data), '?'));
                    $stmt = $db->prepare("INSERT INTO productos ($cols) VALUES ($vals)");
                    $stmt->execute(array_values($data));
                    $idProd = (int)$db->lastInsertId();
                    $almacenes = $db->query("SELECT id FROM almacenes WHERE activo = 1")->fetchAll();
                    $stmtPa = $db->prepare("INSERT INTO producto_almacen (id_producto, id_almacen, stock_actual) VALUES (?, ?, 0)");
                    foreach ($almacenes as $a) {
                        $stmtPa->execute([$idProd, $a['id']]);
                    }
                    registrarAuditoria(USUARIO_ID, 'crear', 'productos', $idProd, "Producto creado: {$data['nombre']}");
                    $success = 'Producto creado';
                }
            }
        }
    }
}

$titulo = $editar ? 'Editar Producto' : 'Nuevo Producto';
$subtitulo = 'Productos';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0"><?= $titulo ?></h5></div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><script>setTimeout(() => location.href = '<?= BASE_URL ?>/modules/productos/productos.php', 1500);</script><?php endif; ?>
        <form method="POST" class="row g-3">
            <?= campoCSRF() ?>
            <div class="col-md-3">
                <label class="form-label">Código <span class="text-danger">*</span></label>
                <input type="text" name="codigo" class="form-control" value="<?= h($p['codigo']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Código de Barras</label>
                <input type="text" name="codigo_barras" class="form-control" value="<?= h($p['codigo_barras']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" name="nombre" class="form-control" value="<?= h($p['nombre']) ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="2"><?= h($p['descripcion']) ?></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Categoría</label>
                <select name="id_categoria" class="form-select">
                    <option value="">Sin categoría</option>
                    <?php foreach ($categorias as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $p['id_categoria'] ? 'selected' : '' ?>><?= h($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Unidad de Medida</label>
                <select name="id_unidad_medida" class="form-select">
                    <option value="">Seleccionar</option>
                    <?php foreach ($unidades as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $u['id'] == $p['id_unidad_medida'] ? 'selected' : '' ?>><?= h($u['nombre']) ?> (<?= h($u['abreviatura']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Impuesto</label>
                <select name="id_impuesto" class="form-select">
                    <?php foreach ($impuestos as $i): ?>
                    <option value="<?= $i['id'] ?>" <?= $i['id'] == $p['id_impuesto'] ? 'selected' : '' ?>><?= h($i['nombre']) ?> (<?= $i['porcentaje'] ?>%)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Precio de Compra</label>
                <input type="number" name="precio_compra" class="form-control" step="0.01" value="<?= $p['precio_compra'] ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Precio de Venta</label>
                <input type="number" name="precio_venta" class="form-control" step="0.01" value="<?= $p['precio_venta'] ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Precio Mayorista</label>
                <input type="number" name="precio_mayorista" class="form-control" step="0.01" value="<?= $p['precio_mayorista'] ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Stock Mínimo</label>
                <input type="number" name="stock_minimo" class="form-control" step="0.01" value="<?= $p['stock_minimo'] ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Stock Máximo</label>
                <input type="number" name="stock_maximo" class="form-control" step="0.01" value="<?= $p['stock_maximo'] ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Stock de Seguridad</label>
                <input type="number" name="stock_seguridad" class="form-control" step="0.01" value="<?= $p['stock_seguridad'] ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Lead Time (días)</label>
                <input type="number" name="lead_time_dias" class="form-control" value="<?= $p['lead_time_dias'] ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Ubicación en Almacén</label>
                <input type="text" name="ubicacion_almacen" class="form-control" value="<?= h($p['ubicacion_almacen']) ?>" placeholder="Pasillo-Estante-Fila">
            </div>
            <div class="col-md-6">
                <label class="form-label">Notas</label>
                <textarea name="notas" class="form-control" rows="2"><?= h($p['notas']) ?></textarea>
            </div>
            <div class="col-md-6">
                <div class="form-check form-switch mt-4">
                    <input type="checkbox" name="activo" class="form-check-input" id="activo" <?= $p['activo'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="activo">Activo</label>
                </div>
            </div>
            <div class="col-12">
                <hr>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> <?= $editar ? 'Actualizar' : 'Crear' ?> Producto</button>
                <a href="<?= BASE_URL ?>/modules/productos/productos.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
