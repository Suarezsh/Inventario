<?php

require_once __DIR__ . '/../config/database.php';

function tienePermiso(int $idRol, string $modulo, string $accion = 'leer'): bool
{
    if ($idRol === 1) return true;
    $db = getDB();
    $columna = match ($accion) {
        'crear' => 'permiso_crear',
        'editar' => 'permiso_editar',
        'eliminar' => 'permiso_eliminar',
        'exportar' => 'permiso_exportar',
        default => 'permiso_leer',
    };
    $stmt = $db->prepare("SELECT $columna FROM permisos_roles WHERE id_rol = ? AND modulo = ?");
    $stmt->execute([$idRol, $modulo]);
    return (bool) $stmt->fetchColumn();
}

function verificarPermisoO(int $idRol, string $modulo, string $accion = 'leer'): void
{
    if (!tienePermiso($idRol, $modulo, $accion)) {
        http_response_code(403);
        die('Acceso denegado');
    }
}
