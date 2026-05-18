<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

function obtenerConfiguracion(): array
{
    $db = getDB();
    $stmt = $db->query("SELECT clave, valor FROM configuracion");
    $config = [];
    while ($row = $stmt->fetch()) {
        $config[$row['clave']] = $row['valor'];
    }
    return $config;
}

function config(string $clave, $default = null)
{
    static $cache = null;
    if ($cache === null) {
        $cache = obtenerConfiguracion();
    }
    return $cache[$clave] ?? $default;
}

function moduloActivo(string $clave): bool
{
    $db = getDB();
    $stmt = $db->prepare("SELECT activo FROM modulos WHERE clave = ?");
    $stmt->execute([$clave]);
    return (bool) $stmt->fetchColumn();
}

function obtenerUsuario(int $id): ?array
{
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function obtenerProductos(): array
{
    $db = getDB();
    return $db->query("SELECT p.*, c.nombre as categoria FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id WHERE p.activo = 1 ORDER BY p.nombre")->fetchAll();
}

function obtenerStockProducto(int $idProducto, ?int $idAlmacen = null): float
{
    $db = getDB();
    if ($idAlmacen) {
        $stmt = $db->prepare("SELECT stock_actual FROM producto_almacen WHERE id_producto = ? AND id_almacen = ?");
        $stmt->execute([$idProducto, $idAlmacen]);
    } else {
        $stmt = $db->prepare("SELECT SUM(stock_actual) FROM producto_almacen WHERE id_producto = ?");
        $stmt->execute([$idProducto]);
    }
    return (float) ($stmt->fetchColumn() ?: 0);
}

function actualizarStock(int $idProducto, int $idAlmacen): void
{
    $db = getDB();
    $totalEntradas = $db->prepare("SELECT COALESCE(SUM(cantidad), 0) FROM ingreso_detalles d JOIN ingresos i ON d.id_ingreso = i.id WHERE d.id_producto = ? AND i.id_almacen = ? AND i.anulado = 0");
    $totalEntradas->execute([$idProducto, $idAlmacen]);
    $entradas = (float) $totalEntradas->fetchColumn();

    $totalSalidas = $db->prepare("SELECT COALESCE(SUM(cantidad), 0) FROM salida_detalles d JOIN salidas s ON d.id_salida = s.id WHERE d.id_producto = ? AND s.id_almacen = ? AND s.anulado = 0");
    $totalSalidas->execute([$idProducto, $idAlmacen]);
    $salidas = (float) $totalSalidas->fetchColumn();

    $ajustesEntrada = $db->prepare("SELECT COALESCE(SUM(d.cantidad), 0) FROM ajuste_detalles d JOIN ajustes a ON d.id_ajuste = a.id WHERE d.id_producto = ? AND a.id_almacen = ? AND a.tipo_ajuste = 'entrada'");
    $ajustesEntrada->execute([$idProducto, $idAlmacen]);
    $entradas += (float) $ajustesEntrada->fetchColumn();

    $ajustesSalida = $db->prepare("SELECT COALESCE(SUM(d.cantidad), 0) FROM ajuste_detalles d JOIN ajustes a ON d.id_ajuste = a.id WHERE d.id_producto = ? AND a.id_almacen = ? AND a.tipo_ajuste = 'salida'");
    $ajustesSalida->execute([$idProducto, $idAlmacen]);
    $salidas += (float) $ajustesSalida->fetchColumn();

    $transferenciasEntrada = $db->prepare("SELECT COALESCE(SUM(d.cantidad), 0) FROM transferencia_detalles d JOIN transferencias t ON d.id_transferencia = t.id WHERE d.id_producto = ? AND t.id_almacen_destino = ? AND t.estado = 'completada'");
    $transferenciasEntrada->execute([$idProducto, $idAlmacen]);
    $entradas += (float) $transferenciasEntrada->fetchColumn();

    $transferenciasSalida = $db->prepare("SELECT COALESCE(SUM(d.cantidad), 0) FROM transferencia_detalles d JOIN transferencias t ON d.id_transferencia = t.id WHERE d.id_producto = ? AND t.id_almacen_origen = ? AND t.estado = 'completada'");
    $transferenciasSalida->execute([$idProducto, $idAlmacen]);
    $salidas += (float) $transferenciasSalida->fetchColumn();

    $stock = $entradas - $salidas;

    $stmt = $db->prepare("SELECT COUNT(*) FROM producto_almacen WHERE id_producto = ? AND id_almacen = ?");
    $stmt->execute([$idProducto, $idAlmacen]);
    $exists = (int) $stmt->fetchColumn();

    if ($exists > 0) {
        $update = $db->prepare("UPDATE producto_almacen SET stock_actual = ? WHERE id_producto = ? AND id_almacen = ?");
        $update->execute([$stock, $idProducto, $idAlmacen]);
    } else {
        $insert = $db->prepare("INSERT INTO producto_almacen (id_producto, id_almacen, stock_actual) VALUES (?, ?, ?)");
        $insert->execute([$idProducto, $idAlmacen, $stock]);
    }
}

function registrarMovimiento(int $idProducto, int $idAlmacen, string $tipoMovimiento, string $tipoDocumento, int $idDocumento, float $cantidad, ?float $costoUnitario, int $idUsuario): void
{
    $db = getDB();
    $stockAnterior = obtenerStockProducto($idProducto, $idAlmacen);
    actualizarStock($idProducto, $idAlmacen);
    $stockPosterior = obtenerStockProducto($idProducto, $idAlmacen);

    $stmt = $db->prepare("INSERT INTO movimientos_inventario (id_producto, id_almacen, tipo_movimiento, tipo_documento, id_documento, cantidad, costo_unitario, stock_anterior, stock_posterior, usuario) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$idProducto, $idAlmacen, $tipoMovimiento, $tipoDocumento, $idDocumento, $cantidad, $costoUnitario, $stockAnterior, $stockPosterior, $idUsuario]);
}

function registrarAuditoria(int $idUsuario, string $accion, string $modulo, ?int $idRegistro, string $descripcion, ?string $valoresAnteriores = null, ?string $valoresNuevos = null): void
{
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $db->prepare("INSERT INTO auditoria_logs (id_usuario, usuario_nombre, accion, modulo, id_registro, descripcion, valores_anteriores, valores_nuevos, direccion_ip) VALUES (?, (SELECT username FROM usuarios WHERE id = ?), ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$idUsuario, $idUsuario, $accion, $modulo, $idRegistro, $descripcion, $valoresAnteriores, $valoresNuevos, $ip]);
}

function generarNumeroDocumento(string $prefijo): string
{
    return $prefijo . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function verificarStockSuficiente(int $idProducto, int $idAlmacen, float $cantidad): bool
{
    $stock = obtenerStockProducto($idProducto, $idAlmacen);
    return $stock >= $cantidad;
}
