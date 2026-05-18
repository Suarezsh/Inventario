<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="empresa" content="<?= h(config('empresa_nombre', APP_NAME)) ?>">
    <title><?= h($titulo ?? 'Panel') ?> - <?= h(APP_NAME) ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📦</text></svg>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta2/dist/css/adminlte.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fontsource/nunito@5.0.8/index.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="bi bi-list"></i>
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?= BASE_URL ?>/index.php" class="nav-link">Inicio</a>
            </li>
        </ul>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle fs-5 me-1"></i>
                    <span><?= h(USUARIO_NOMBRE) ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/usuarios/perfil.php"><i class="bi bi-person me-2"></i>Mi Perfil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                </ul>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="<?= BASE_URL ?>/index.php" class="brand-link text-decoration-none d-flex align-items-center py-3 px-3">
            <span class="brand-text fw-bold fs-5">📦 <?= h(APP_NAME) ?></span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/index.php" class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-speedometer2"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <?php if (moduloActivo('usuarios') && tienePermiso(USUARIO_ROL, 'usuarios')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/usuarios/usuarios.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'modules/usuarios/') !== false ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-people"></i>
                            <p>Usuarios</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (moduloActivo('configuracion')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/configuracion.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'modules/configuracion.php') !== false ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-gear"></i>
                            <p>Configuración</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (moduloActivo('categorias')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/categorias/categorias.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'modules/categorias/') !== false ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-tags"></i>
                            <p>Categorías</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (moduloActivo('productos')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/productos/productos.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'modules/productos/') !== false ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-box-seam"></i>
                            <p>Productos</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (moduloActivo('proveedores')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/proveedores/proveedores.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'modules/proveedores/') !== false ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-truck"></i>
                            <p>Proveedores</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (moduloActivo('clientes')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/clientes/clientes.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'modules/clientes/') !== false ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-person-badge"></i>
                            <p>Clientes</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <li class="nav-header mt-3"><small>MOVIMIENTOS</small></li>

                    <?php if (moduloActivo('ingresos')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/ingresos/ingresos.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'modules/ingresos/') !== false ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-arrow-down-circle text-success"></i>
                            <p>Ingresos</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (moduloActivo('salidas')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/salidas/salidas.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'modules/salidas/') !== false ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-arrow-up-circle text-danger"></i>
                            <p>Salidas</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (moduloActivo('almacenes')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/almacenes/almacenes.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'modules/almacenes/') !== false ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-building"></i>
                            <p>Almacenes</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (moduloActivo('transferencias')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/transferencias/transferencias.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'modules/transferencias/') !== false ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-arrow-left-right text-info"></i>
                            <p>Transferencias</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (moduloActivo('ajustes')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/ajustes/ajustes.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'modules/ajustes/') !== false ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-tools text-warning"></i>
                            <p>Ajustes</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <li class="nav-header mt-3"><small>REPORTES</small></li>

                    <?php if (moduloActivo('reportes')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/reportes/existencias.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'modules/reportes/') !== false ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-boxes"></i>
                            <p>Existencias</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/reportes/movimientos.php" class="nav-link">
                            <i class="nav-icon bi bi-arrow-left-right"></i>
                            <p>Movimientos</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/reportes/ventas.php" class="nav-link">
                            <i class="nav-icon bi bi-cash text-success"></i>
                            <p>Ventas</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/reportes/compras.php" class="nav-link">
                            <i class="nav-icon bi bi-cart text-primary"></i>
                            <p>Compras</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/reportes/kardex.php" class="nav-link">
                            <i class="nav-icon bi bi-journal-text"></i>
                            <p>Kárdex</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/reportes/stock_bajo.php" class="nav-link">
                            <i class="nav-icon bi bi-exclamation-triangle text-warning"></i>
                            <p>Stock Bajo</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <li class="nav-header mt-3"><small>SISTEMA</small></li>

                    <?php if (moduloActivo('auditoria')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/auditoria/auditoria.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'modules/auditoria/') !== false ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-journal-check"></i>
                            <p>Auditoría</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (moduloActivo('alertas')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/notificaciones/notificaciones.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'modules/notificaciones/') !== false ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-bell"></i>
                            <p>Notificaciones</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (moduloActivo('import_export')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/import_export/importar.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'modules/import_export/') !== false ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-upload"></i>
                            <p>Importar / Exportar</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (moduloActivo('backups')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/modules/backups/backups.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'modules/backups/') !== false ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-cloud-arrow-down"></i>
                            <p>Backups</p>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content-header py-3 px-4 bg-light border-bottom">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h1 class="m-0 fs-4 fw-bold"><?= h($titulo ?? 'Panel') ?></h1>
                    </div>
                    <?php if (isset($subtitulo)): ?>
                    <div class="col-sm-6">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb float-sm-end mb-0">
                                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Inicio</a></li>
                                <li class="breadcrumb-item active" aria-current="page"><?= h($subtitulo) ?></li>
                            </ol>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <section class="content px-4 py-3">
