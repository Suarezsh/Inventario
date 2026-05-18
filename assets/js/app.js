function generarPDF(titulo, selectorTabla, orientacion) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF(orientacion || 'portrait');
    const empresa = document.querySelector('meta[name="empresa"]')?.content || 'Sistema de Inventario';
    doc.setFontSize(16);
    doc.text(empresa, 14, 15);
    doc.setFontSize(12);
    doc.text(titulo, 14, 25);
    doc.setFontSize(10);
    doc.text('Fecha: ' + new Date().toLocaleDateString('es-PE'), 14, 32);
    doc.autoTable({
        html: selectorTabla,
        startY: 38,
        theme: 'grid',
        headStyles: {
            fillColor: [26, 26, 46],
            textColor: 255,
            fontStyle: 'bold'
        },
        alternateRowStyles: {
            fillColor: [245, 246, 250]
        },
        styles: {
            fontSize: 9,
            cellPadding: 4
        }
    });
    doc.save(titulo.replace(/\s+/g, '_') + '.pdf');
}

function exportarTablaExcel(titulo, selectorTabla) {
    const table = document.querySelector(selectorTabla);
    const wb = XLSX.utils.table_to_book(table, { sheet: titulo });
    XLSX.writeFile(wb, titulo.replace(/\s+/g, '_') + '.xlsx');
}

function formatearMoneda(valor) {
    return numeral(valor).format('$0,0.00');
}

function soloNumeros(e) {
    const charCode = e.which ? e.which : e.keyCode;
    if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode !== 46 && charCode !== 44) {
        e.preventDefault();
    }
}
