        </section>
    </div>

    <footer class="main-footer">
        <div class="float-right d-none d-sm-block">
            <b>Versión</b> <?= APP_VERSION ?>
        </div>
        <strong>&copy; <?= date('Y') ?> <?= h(APP_NAME) ?>.</strong> Todos los derechos reservados.
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.1/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/numeral@2.0.6/numeral.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>

<script>
    flatpickr.setDefaults({
        locale: 'es',
        dateFormat: 'Y-m-d',
        allowInput: true
    });

    $(document).ready(function() {
        $('.datatable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            pageLength: <?= (int)(config('items_por_pagina', 25)) ?>,
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                { extend: 'copy', text: '<i class="bi bi-copy"></i> Copiar' },
                { extend: 'excel', text: '<i class="bi bi-file-earmark-excel"></i> Excel' },
                { extend: 'pdf', text: '<i class="bi bi-file-earmark-pdf"></i> PDF' },
                { extend: 'print', text: '<i class="bi bi-printer"></i> Imprimir' }
            ]
        });

        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });

        $('.select2-producto').select2({
            theme: 'bootstrap-5',
            width: '100%',
            ajax: {
                url: '<?= BASE_URL ?>/modules/productos/buscar.php',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return { results: data };
                },
                cache: true
            },
            minimumInputLength: 1,
            placeholder: 'Buscar producto...'
        });
    });

    function confirmarEliminar(mensaje) {
        return Swal.fire({
            title: '¿Estás seguro?',
            text: mensaje || 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });
    }

    function toastSuccess(mensaje) {
        Swal.fire({ icon: 'success', title: 'Éxito', text: mensaje, timer: 3000, showConfirmButton: false });
    }

    function toastError(mensaje) {
        Swal.fire({ icon: 'error', title: 'Error', text: mensaje, timer: 5000, showConfirmButton: false });
    }

    function toastWarning(mensaje) {
        Swal.fire({ icon: 'warning', title: 'Atención', text: mensaje, timer: 4000, showConfirmButton: false });
    }
</script>
</body>
</html>
