<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/csrf.php';

verificarPermisoO(USUARIO_ROL, 'configuracion');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validarTokenCSRF($_POST['csrf_token'] ?? null)) {
    $stmt = $db->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
    foreach ($_POST as $clave => $valor) {
        if ($clave !== 'csrf_token' && $clave !== 'modulos') {
            $stmt->execute([$valor, $clave]);
        }
    }
    if (isset($_POST['modulos']) && is_array($_POST['modulos'])) {
        $stmtMod = $db->prepare("UPDATE modulos SET activo = ? WHERE clave = ?");
        foreach ($_POST['modulos'] as $clave => $activo) {
            $stmtMod->execute([1, $clave]);
        }
        $todos = $db->query("SELECT clave FROM modulos")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($todos as $c) {
            if (!isset($_POST['modulos'][$c])) {
                $db->prepare("UPDATE modulos SET activo = 0 WHERE clave = ?")->execute([$c]);
            }
        }
    } else {
        $db->exec("UPDATE modulos SET activo = 0");
    }
    registrarAuditoria(USUARIO_ID, 'editar', 'configuracion', null, 'Configuración general actualizada');
    $success = 'Configuración guardada correctamente';
}

$config = obtenerConfiguracion();
$modulos = $db->query("SELECT * FROM modulos ORDER BY orden")->fetchAll();

$titulo = 'Configuración';
$subtitulo = 'Configuración';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Empresa</h5></div>
            <div class="card-body">
                <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= h($success) ?></div>
                <?php endif; ?>
                <form method="POST" class="row g-3">
                    <?= campoCSRF() ?>
                    <div class="col-md-6">
                        <label class="form-label">Nombre de la Empresa</label>
                        <input type="text" name="empresa_nombre" class="form-control" value="<?= h($config['empresa_nombre'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">RUC / NIT / CUIT</label>
                        <input type="text" name="empresa_ruc" class="form-control" value="<?= h($config['empresa_ruc'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="empresa_direccion" class="form-control" value="<?= h($config['empresa_direccion'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="empresa_telefono" class="form-control" value="<?= h($config['empresa_telefono'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="empresa_email" class="form-control" value="<?= h($config['empresa_email'] ?? '') ?>">
                    </div>
                    <div class="col-12"><hr><h6>Moneda</h6></div>
                    <div class="col-md-4">
                        <label class="form-label">Símbolo</label>
                        <input type="text" name="moneda_simbolo" class="form-control" value="<?= h($config['moneda_simbolo'] ?? '$') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Código ISO</label>
                        <input type="text" name="moneda_codigo" class="form-control" value="<?= h($config['moneda_codigo'] ?? 'USD') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="moneda_nombre" class="form-control" value="<?= h($config['moneda_nombre'] ?? 'Dólar') ?>">
                    </div>
                    <div class="col-12"><hr><h6>Inventario</h6></div>
                    <div class="col-md-4">
                        <label class="form-label">Método de Costeo</label>
                        <select name="metodo_costeo" class="form-select">
                            <?php foreach (['promedio' => 'Promedio Ponderado', 'fifo' => 'FIFO', 'lifo' => 'LIFO'] as $k => $v): ?>
                            <option value="<?= $k ?>" <?= ($config['metodo_costeo'] ?? 'promedio') === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Stock Mínimo por Defecto</label>
                        <input type="number" name="stock_minimo_default" class="form-control" value="<?= h($config['stock_minimo_default'] ?? '0') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Stock Máximo por Defecto</label>
                        <input type="number" name="stock_maximo_default" class="form-control" value="<?= h($config['stock_maximo_default'] ?? '0') ?>">
                    </div>
                    <div class="col-12"><hr><h6>Impuesto</h6></div>
                    <div class="col-md-6">
                        <label class="form-label">Nombre del Impuesto</label>
                        <input type="text" name="impuesto_nombre" class="form-control" value="<?= h($config['impuesto_nombre'] ?? 'IVA') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Porcentaje (%)</label>
                        <input type="number" name="impuesto_porcentaje" class="form-control" step="0.01" value="<?= h($config['impuesto_porcentaje'] ?? '0') ?>">
                    </div>
                    <div class="col-12"><hr><h6>Formato</h6></div>
                    <div class="col-md-4">
                        <label class="form-label">Zona Horaria</label>
                        <select name="zona_horaria" class="form-select">
                            <?php $z = $config['zona_horaria'] ?? 'America/Lima'; ?>
                            <?php foreach (['America/Lima', 'America/Mexico_City', 'America/Argentina/Buenos_Aires', 'America/Santiago', 'America/Bogota', 'America/Caracas', 'America/Panama', 'America/Havana'] as $tz): ?>
                            <option value="<?= $tz ?>" <?= $z === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Formato Fecha</label>
                        <select name="formato_fecha" class="form-select">
                            <?php $f = $config['formato_fecha'] ?? 'Y-m-d'; ?>
                            <option value="Y-m-d" <?= $f === 'Y-m-d' ? 'selected' : '' ?>>2026-05-18</option>
                            <option value="d/m/Y" <?= $f === 'd/m/Y' ? 'selected' : '' ?>>18/05/2026</option>
                            <option value="d-m-Y" <?= $f === 'd-m-Y' ? 'selected' : '' ?>>18-05-2026</option>
                            <option value="m/d/Y" <?= $f === 'm/d/Y' ? 'selected' : '' ?>>05/18/2026</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Items por Página</label>
                        <input type="number" name="items_por_pagina" class="form-control" value="<?= h($config['items_por_pagina'] ?? '25') ?>">
                    </div>
                    <div class="col-12">
                        <hr>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Configuración</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Módulos del Sistema</h5></div>
            <div class="card-body">
                <p class="text-muted small">Active o desactive módulos según su necesidad</p>
                <form method="POST">
                    <?= campoCSRF() ?>
                    <?php foreach ($modulos as $m): ?>
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="modulos[<?= h($m['clave']) ?>]" value="0">
                        <input type="checkbox" name="modulos[<?= h($m['clave']) ?>]" class="form-check-input" id="mod_<?= h($m['clave']) ?>" value="1" <?= $m['activo'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="mod_<?= h($m['clave']) ?>"><?= h($m['nombre']) ?></label>
                    </div>
                    <?php endforeach; ?>
                    <hr>
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-save"></i> Guardar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
