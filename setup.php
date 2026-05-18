<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';

$db = getDB();

$sql = "
CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    nombre_completo TEXT NOT NULL,
    email TEXT,
    telefono TEXT,
    id_rol INTEGER NOT NULL DEFAULT 1,
    activo INTEGER DEFAULT 1,
    ultimo_acceso TEXT,
    intentos_fallidos INTEGER DEFAULT 0,
    bloqueado INTEGER DEFAULT 0,
    creado_por INTEGER,
    fecha_creacion TEXT DEFAULT (datetime('now','localtime')),
    fecha_actualizacion TEXT
);

CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT UNIQUE NOT NULL,
    descripcion TEXT,
    activo INTEGER DEFAULT 1
);

CREATE TABLE IF NOT EXISTS permisos_roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_rol INTEGER NOT NULL,
    modulo TEXT NOT NULL,
    permiso_crear INTEGER DEFAULT 0,
    permiso_leer INTEGER DEFAULT 1,
    permiso_editar INTEGER DEFAULT 0,
    permiso_eliminar INTEGER DEFAULT 0,
    permiso_exportar INTEGER DEFAULT 0,
    FOREIGN KEY (id_rol) REFERENCES roles(id)
);

CREATE TABLE IF NOT EXISTS configuracion (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    clave TEXT UNIQUE NOT NULL,
    valor TEXT NOT NULL,
    tipo TEXT DEFAULT 'texto',
    descripcion TEXT
);

CREATE TABLE IF NOT EXISTS modulos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT UNIQUE NOT NULL,
    clave TEXT UNIQUE NOT NULL,
    activo INTEGER DEFAULT 1,
    orden INTEGER DEFAULT 0,
    descripcion TEXT
);

CREATE TABLE IF NOT EXISTS categorias (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    descripcion TEXT,
    activo INTEGER DEFAULT 1,
    fecha_creacion TEXT DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS subcategorias (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_categoria INTEGER NOT NULL,
    nombre TEXT NOT NULL,
    descripcion TEXT,
    id_padre INTEGER DEFAULT NULL,
    activo INTEGER DEFAULT 1,
    fecha_creacion TEXT DEFAULT (datetime('now','localtime')),
    FOREIGN KEY (id_categoria) REFERENCES categorias(id),
    FOREIGN KEY (id_padre) REFERENCES subcategorias(id)
);

CREATE TABLE IF NOT EXISTS almacenes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo TEXT UNIQUE NOT NULL,
    nombre TEXT NOT NULL,
    ubicacion TEXT,
    encargado TEXT,
    telefono TEXT,
    activo INTEGER DEFAULT 1,
    fecha_creacion TEXT DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS proveedores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    razon_social TEXT NOT NULL,
    ruc TEXT,
    contacto TEXT,
    telefono TEXT,
    email TEXT,
    direccion TEXT,
    sitio_web TEXT,
    notas TEXT,
    activo INTEGER DEFAULT 1,
    fecha_creacion TEXT DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS clientes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tipo_documento TEXT DEFAULT 'DNI',
    numero_documento TEXT,
    nombre TEXT NOT NULL,
    direccion TEXT,
    telefono TEXT,
    email TEXT,
    tipo_cliente TEXT DEFAULT 'minorista',
    limite_credito REAL DEFAULT 0,
    dias_credito INTEGER DEFAULT 0,
    descuento_especial REAL DEFAULT 0,
    notas TEXT,
    activo INTEGER DEFAULT 1,
    fecha_creacion TEXT DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS productos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo TEXT UNIQUE NOT NULL,
    codigo_barras TEXT,
    nombre TEXT NOT NULL,
    descripcion TEXT,
    id_categoria INTEGER,
    id_subcategoria INTEGER,
    id_unidad_medida INTEGER,
    costo_promedio REAL DEFAULT 0,
    precio_compra REAL DEFAULT 0,
    precio_venta REAL DEFAULT 0,
    precio_mayorista REAL DEFAULT 0,
    id_impuesto INTEGER DEFAULT 1,
    stock_minimo REAL DEFAULT 0,
    stock_maximo REAL DEFAULT 0,
    stock_seguridad REAL DEFAULT 0,
    lead_time_dias INTEGER DEFAULT 0,
    ubicacion_almacen TEXT,
    imagen TEXT,
    notas TEXT,
    activo INTEGER DEFAULT 1,
    fecha_creacion TEXT DEFAULT (datetime('now','localtime')),
    FOREIGN KEY (id_categoria) REFERENCES categorias(id),
    FOREIGN KEY (id_subcategoria) REFERENCES subcategorias(id),
    FOREIGN KEY (id_unidad_medida) REFERENCES unidades_medida(id),
    FOREIGN KEY (id_impuesto) REFERENCES impuestos(id)
);

CREATE TABLE IF NOT EXISTS producto_proveedor (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_producto INTEGER NOT NULL,
    id_proveedor INTEGER NOT NULL,
    codigo_proveedor TEXT,
    precio_compra REAL,
    tiempo_entrega INTEGER,
    FOREIGN KEY (id_producto) REFERENCES productos(id),
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id)
);

CREATE TABLE IF NOT EXISTS producto_almacen (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_producto INTEGER NOT NULL,
    id_almacen INTEGER NOT NULL,
    stock_actual REAL DEFAULT 0,
    FOREIGN KEY (id_producto) REFERENCES productos(id),
    FOREIGN KEY (id_almacen) REFERENCES almacenes(id)
);

CREATE TABLE IF NOT EXISTS ingresos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tipo_ingreso TEXT DEFAULT 'compra',
    numero_documento TEXT NOT NULL,
    id_proveedor INTEGER,
    fecha_ingreso TEXT NOT NULL,
    fecha_documento TEXT,
    id_almacen INTEGER NOT NULL,
    subtotal REAL DEFAULT 0,
    impuesto REAL DEFAULT 0,
    total REAL DEFAULT 0,
    id_forma_pago INTEGER,
    notas TEXT,
    anulado INTEGER DEFAULT 0,
    fecha_anulacion TEXT,
    usuario_anulacion INTEGER,
    creado_por INTEGER,
    fecha_creacion TEXT DEFAULT (datetime('now','localtime')),
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id),
    FOREIGN KEY (id_almacen) REFERENCES almacenes(id),
    FOREIGN KEY (id_forma_pago) REFERENCES formas_pago(id),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS ingreso_detalles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_ingreso INTEGER NOT NULL,
    id_producto INTEGER NOT NULL,
    cantidad REAL NOT NULL,
    costo_unitario REAL NOT NULL,
    subtotal REAL NOT NULL,
    lote TEXT,
    fecha_vencimiento TEXT,
    FOREIGN KEY (id_ingreso) REFERENCES ingresos(id),
    FOREIGN KEY (id_producto) REFERENCES productos(id)
);

CREATE TABLE IF NOT EXISTS salidas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tipo_salida TEXT DEFAULT 'venta',
    numero_documento TEXT NOT NULL,
    id_cliente INTEGER,
    fecha_salida TEXT NOT NULL,
    fecha_documento TEXT,
    id_almacen INTEGER NOT NULL,
    subtotal REAL DEFAULT 0,
    descuento REAL DEFAULT 0,
    impuesto REAL DEFAULT 0,
    total REAL DEFAULT 0,
    id_forma_pago INTEGER,
    notas TEXT,
    anulado INTEGER DEFAULT 0,
    fecha_anulacion TEXT,
    usuario_anulacion INTEGER,
    creado_por INTEGER,
    fecha_creacion TEXT DEFAULT (datetime('now','localtime')),
    FOREIGN KEY (id_cliente) REFERENCES clientes(id),
    FOREIGN KEY (id_almacen) REFERENCES almacenes(id),
    FOREIGN KEY (id_forma_pago) REFERENCES formas_pago(id),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS salida_detalles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_salida INTEGER NOT NULL,
    id_producto INTEGER NOT NULL,
    cantidad REAL NOT NULL,
    precio_unitario REAL NOT NULL,
    descuento REAL DEFAULT 0,
    subtotal REAL NOT NULL,
    lote TEXT,
    FOREIGN KEY (id_salida) REFERENCES salidas(id),
    FOREIGN KEY (id_producto) REFERENCES productos(id)
);

CREATE TABLE IF NOT EXISTS transferencias (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    numero_guia TEXT NOT NULL,
    id_almacen_origen INTEGER NOT NULL,
    id_almacen_destino INTEGER NOT NULL,
    fecha_transferencia TEXT NOT NULL,
    estado TEXT DEFAULT 'pendiente',
    notas TEXT,
    creado_por INTEGER,
    fecha_creacion TEXT DEFAULT (datetime('now','localtime')),
    FOREIGN KEY (id_almacen_origen) REFERENCES almacenes(id),
    FOREIGN KEY (id_almacen_destino) REFERENCES almacenes(id),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS transferencia_detalles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_transferencia INTEGER NOT NULL,
    id_producto INTEGER NOT NULL,
    cantidad REAL NOT NULL,
    lote TEXT,
    FOREIGN KEY (id_transferencia) REFERENCES transferencias(id),
    FOREIGN KEY (id_producto) REFERENCES productos(id)
);

CREATE TABLE IF NOT EXISTS ajustes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tipo_ajuste TEXT NOT NULL,
    motivo TEXT NOT NULL,
    id_almacen INTEGER NOT NULL,
    fecha_ajuste TEXT NOT NULL,
    autorizado_por INTEGER,
    notas TEXT,
    creado_por INTEGER,
    fecha_creacion TEXT DEFAULT (datetime('now','localtime')),
    FOREIGN KEY (id_almacen) REFERENCES almacenes(id),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS ajuste_detalles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_ajuste INTEGER NOT NULL,
    id_producto INTEGER NOT NULL,
    cantidad REAL NOT NULL,
    costo_unitario REAL DEFAULT 0,
    motivo_detalle TEXT,
    FOREIGN KEY (id_ajuste) REFERENCES ajustes(id),
    FOREIGN KEY (id_producto) REFERENCES productos(id)
);

CREATE TABLE IF NOT EXISTS movimientos_inventario (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_producto INTEGER NOT NULL,
    id_almacen INTEGER NOT NULL,
    tipo_movimiento TEXT NOT NULL,
    tipo_documento TEXT NOT NULL,
    id_documento INTEGER NOT NULL,
    cantidad REAL NOT NULL,
    costo_unitario REAL,
    stock_anterior REAL,
    stock_posterior REAL,
    fecha_movimiento TEXT DEFAULT (datetime('now','localtime')),
    usuario INTEGER,
    FOREIGN KEY (id_producto) REFERENCES productos(id),
    FOREIGN KEY (id_almacen) REFERENCES almacenes(id),
    FOREIGN KEY (usuario) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS auditoria_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_usuario INTEGER,
    usuario_nombre TEXT,
    accion TEXT NOT NULL,
    modulo TEXT NOT NULL,
    id_registro INTEGER,
    descripcion TEXT,
    valores_anteriores TEXT,
    valores_nuevos TEXT,
    direccion_ip TEXT,
    fecha_hora TEXT DEFAULT (datetime('now','localtime')),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS notificaciones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tipo TEXT NOT NULL,
    titulo TEXT NOT NULL,
    mensaje TEXT NOT NULL,
    id_referencia INTEGER,
    modulo_referencia TEXT,
    leido INTEGER DEFAULT 0,
    fecha_creacion TEXT DEFAULT (datetime('now','localtime')),
    fecha_lectura TEXT
);

CREATE TABLE IF NOT EXISTS backups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre_archivo TEXT NOT NULL,
    tamano_bytes INTEGER NOT NULL,
    tipo TEXT DEFAULT 'manual',
    creado_por INTEGER,
    fecha_creacion TEXT DEFAULT (datetime('now','localtime')),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS impuestos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    porcentaje REAL NOT NULL,
    activo INTEGER DEFAULT 1
);

CREATE TABLE IF NOT EXISTS formas_pago (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    activo INTEGER DEFAULT 1
);

CREATE TABLE IF NOT EXISTS unidades_medida (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    abreviatura TEXT NOT NULL,
    activo INTEGER DEFAULT 1
);
";

$db->exec($sql);

$defaults = [
    "INSERT OR IGNORE INTO roles (id, nombre, descripcion, activo) VALUES (1, 'Administrador', 'Acceso total al sistema', 1)",
    "INSERT OR IGNORE INTO roles (id, nombre, descripcion, activo) VALUES (2, 'Supervisor', 'Acceso a gestión y reportes', 1)",
    "INSERT OR IGNORE INTO roles (id, nombre, descripcion, activo) VALUES (3, 'Operador', 'Registro de movimientos básicos', 1)",
    "INSERT OR IGNORE INTO roles (id, nombre, descripcion, activo) VALUES (4, 'Consulta', 'Solo lectura de reportes', 1)",
];

$modulos = [
    ['Usuarios y Roles', 'usuarios', 1, 1],
    ['Configuración', 'configuracion', 1, 2],
    ['Categorías', 'categorias', 1, 3],
    ['Productos', 'productos', 1, 4],
    ['Proveedores', 'proveedores', 1, 5],
    ['Clientes', 'clientes', 1, 6],
    ['Ingresos', 'ingresos', 1, 7],
    ['Salidas', 'salidas', 1, 8],
    ['Ajustes', 'ajustes', 1, 9],
    ['Transferencias', 'transferencias', 1, 10],
    ['Almacenes', 'almacenes', 1, 11],
    ['Reportes', 'reportes', 1, 12],
    ['PDF', 'pdf', 1, 13],
    ['Alertas', 'alertas', 1, 14],
    ['Auditoría', 'auditoria', 1, 15],
    ['Importar/Exportar', 'import_export', 1, 16],
    ['Backups', 'backups', 1, 17],
];

$configDefaults = [
    ['empresa_nombre', 'Mi Empresa', 'texto'],
    ['empresa_ruc', '00000000000', 'texto'],
    ['empresa_direccion', '', 'texto'],
    ['empresa_telefono', '', 'texto'],
    ['empresa_email', '', 'texto'],
    ['empresa_logo', '', 'imagen'],
    ['moneda_simbolo', '$', 'texto'],
    ['moneda_codigo', 'USD', 'texto'],
    ['moneda_nombre', 'Dólar', 'texto'],
    ['zona_horaria', 'America/Lima', 'texto'],
    ['formato_fecha', 'Y-m-d', 'texto'],
    ['formato_hora', 'H:i:s', 'texto'],
    ['impuesto_nombre', 'IVA', 'texto'],
    ['impuesto_porcentaje', '0', 'numero'],
    ['metodo_costeo', 'promedio', 'texto'],
    ['stock_minimo_default', '0', 'numero'],
    ['stock_maximo_default', '0', 'numero'],
    ['unidad_medida_default', 'Unidad', 'texto'],
    ['control_lotes', '0', 'booleano'],
    ['control_vencimiento', '0', 'booleano'],
    ['multi_almacen', '0', 'booleano'],
    ['tema_color', 'light', 'texto'],
    ['items_por_pagina', '25', 'numero'],
];

foreach ($defaults as $sql) {
    $db->exec($sql);
}

$permisos = ['usuarios', 'configuracion', 'categorias', 'productos', 'proveedores', 'clientes', 'ingresos', 'salidas', 'ajustes', 'transferencias', 'almacenes', 'reportes', 'pdf', 'alertas', 'auditoria', 'import_export', 'backups'];
$rolesPermisos = [
    2 => ['leer' => 1, 'crear' => 1, 'editar' => 1, 'eliminar' => 0, 'exportar' => 1],
    3 => ['leer' => 1, 'crear' => 1, 'editar' => 0, 'eliminar' => 0, 'exportar' => 0],
    4 => ['leer' => 1, 'crear' => 0, 'editar' => 0, 'eliminar' => 0, 'exportar' => 1],
];

$stmtPermiso = $db->prepare("INSERT OR IGNORE INTO permisos_roles (id_rol, modulo, permiso_crear, permiso_leer, permiso_editar, permiso_eliminar, permiso_exportar) VALUES (?, ?, ?, ?, ?, ?, ?)");
foreach ($rolesPermisos as $rolId => $p) {
    foreach ($permisos as $mod) {
        $stmtPermiso->execute([$rolId, $mod, $p['crear'], $p['leer'], $p['editar'], $p['eliminar'], $p['exportar']]);
    }
}

$stmtMod = $db->prepare("INSERT OR IGNORE INTO modulos (nombre, clave, activo, orden) VALUES (?, ?, ?, ?)");
foreach ($modulos as $m) {
    $stmtMod->execute($m);
}

$stmtConf = $db->prepare("INSERT OR IGNORE INTO configuracion (clave, valor, tipo) VALUES (?, ?, ?)");
foreach ($configDefaults as $c) {
    $stmtConf->execute($c);
}

$impuestos = [
    ['Exento', 0],
    ['IVA 0%', 0],
    ['IVA 5%', 5],
    ['IVA 10%', 10],
    ['IVA 19%', 19],
];
$stmtImp = $db->prepare("INSERT OR IGNORE INTO impuestos (nombre, porcentaje) VALUES (?, ?)");
foreach ($impuestos as $i) {
    $stmtImp->execute($i);
}

$formasPago = ['Efectivo', 'Tarjeta de crédito', 'Tarjeta de débito', 'Transferencia bancaria', 'Cheque', 'Crédito', 'Otro'];
$stmtFP = $db->prepare("INSERT OR IGNORE INTO formas_pago (nombre) VALUES (?)");
foreach ($formasPago as $fp) {
    $stmtFP->execute([$fp]);
}

$unidades = [
    ['Unidad', 'Und'], ['Kilogramo', 'Kg'], ['Gramo', 'g'], ['Libra', 'lb'], ['Litro', 'L'],
    ['Mililitro', 'mL'], ['Metro', 'm'], ['Centímetro', 'cm'], ['Pulgada', 'in'], ['Caja', 'Cja'],
    ['Paquete', 'Pqte'], ['Docena', 'Doc'], ['Par', 'Par'], ['Rollo', 'Rollo'], ['M2', 'm²'],
    ['Galón', 'Gal'], ['Botella', 'Bot'], ['Sobre', 'Sobre'], ['Bolsa', 'Bolsa'], ['Tarima', 'Tar'],
];
$stmtUM = $db->prepare("INSERT OR IGNORE INTO unidades_medida (nombre, abreviatura) VALUES (?, ?)");
foreach ($unidades as $u) {
    $stmtUM->execute($u);
}

$stmtAlmacen = $db->prepare("INSERT OR IGNORE INTO almacenes (id, codigo, nombre, ubicacion) VALUES (1, 'ALM-001', 'Almacén Principal', 'Sede central')");
$stmtAlmacen->execute();

$hash = password_hash('admin', PASSWORD_BCRYPT);
$stmtAdmin = $db->prepare("INSERT OR IGNORE INTO usuarios (id, username, password_hash, nombre_completo, email, id_rol) VALUES (1, 'admin', ?, 'Administrador', 'admin@empresa.com', 1)");
$stmtAdmin->execute([$hash]);

$db->exec("INSERT OR IGNORE INTO clientes (id, tipo_documento, numero_documento, nombre, tipo_cliente) VALUES (1, 'DNI', '00000000', 'Cliente General', 'minorista')");

echo '<h2>Instalación completada</h2>';
echo '<p>Base de datos creada exitosamente.</p>';
echo '<p><strong>Usuario:</strong> admin<br><strong>Contraseña:</strong> admin</p>';
echo '<p><a href="login.php">Ir al login</a></p>';
