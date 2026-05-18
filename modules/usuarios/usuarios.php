<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/csrf.php';

verificarPermisoO(USUARIO_ROL, 'usuarios');

$db = getDB();
$usuarios = $db->query("SELECT u.*, r.nombre as rol_nombre FROM usuarios u JOIN roles r ON u.id_rol = r.id ORDER BY u.fecha_creacion DESC")->fetchAll();

$titulo = 'Usuarios';
$subtitulo = 'Gestión de usuarios';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Lista de Usuarios</h5>
        <?php if (tienePermiso(USUARIO_ROL, 'usuarios', 'crear')): ?>
        <a href="<?= BASE_URL ?>/modules/usuarios/usuario_form.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo Usuario
        </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Nombre Completo</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Último Acceso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= h($u['username']) ?></td>
                        <td><?= h($u['nombre_completo']) ?></td>
                        <td><?= h($u['email']) ?></td>
                        <td><span class="badge bg-info"><?= h($u['rol_nombre']) ?></span></td>
                        <td>
                            <?php if ($u['bloqueado']): ?>
                            <span class="badge bg-danger">Bloqueado</span>
                            <?php elseif ($u['activo']): ?>
                            <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $u['ultimo_acceso'] ? fdatetime($u['ultimo_acceso'], 'd/m/Y H:i') : '-' ?></td>
                        <td>
                            <?php if (tienePermiso(USUARIO_ROL, 'usuarios', 'editar')): ?>
                            <a href="<?= BASE_URL ?>/modules/usuarios/usuario_form.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($u['id'] !== USUARIO_ID && tienePermiso(USUARIO_ROL, 'usuarios', 'eliminar')): ?>
                            <button onclick="eliminarUsuario(<?= $u['id'] ?>)" class="btn btn-sm btn-danger" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
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
function eliminarUsuario(id) {
    Swal.fire({
        title: '¿Eliminar usuario?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('<?= BASE_URL ?>/modules/usuarios/usuario_eliminar.php', { id: id, csrf_token: '<?= obtenerTokenCSRF() ?>' }, function(res) {
                if (res.success) {
                    toastSuccess('Usuario eliminado');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastError(res.error || 'Error al eliminar');
                }
            }, 'json');
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
