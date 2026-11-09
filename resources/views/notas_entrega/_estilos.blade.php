{{-- Estilos del módulo de Notas de Entrega (complementan public/css/app.css) --}}
<style>
    .ne-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 14px 18px; }
    .ne-grid .ortomax-form-group { margin: 0; }
    .ne-seccion { margin-top: 22px; }
    .ne-seccion h2 { font-size: 17px; margin: 0 0 12px; }
    .ne-acciones { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .ne-estado { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; letter-spacing: .3px; background: #EEF2F6; color: #334155; white-space: nowrap; }
    .ne-estado-BORRADOR { background: #EEF2F6; color: #475569; }
    .ne-estado-EN_ALISTAMIENTO { background: #FFF4DB; color: #9A6700; }
    .ne-estado-ALISTADA { background: #E6F4FF; color: #0B5CAD; }
    .ne-estado-DESPACHADA, .ne-estado-ENTREGADA { background: #E8F7ED; color: #198754; }
    .ne-estado-EN_CONCILIACION, .ne-estado-EN_DEVOLUCION { background: #F1E8FF; color: #6F42C1; }
    .ne-estado-CERRADA { background: #E2E8F0; color: #1E293B; }
    .ne-estado-ANULADA { background: #FDECEC; color: #C82333; }
    .ne-aviso { padding: 10px 12px; border-radius: 8px; background: #FFF8E6; color: #7A5300; font-size: 13px; margin: 6px 0; }
    .ne-error { padding: 10px 12px; border-radius: 8px; background: #FDECEC; color: #A71D2A; font-size: 13px; margin: 6px 0; }
    .ne-ok { color: #198754; font-weight: 600; }
    .ne-muted { color: #64748B; font-size: 12.5px; }
    .ne-sugerencia { border: 1px solid #E2E8F0; border-radius: 10px; padding: 12px 14px; margin-bottom: 10px; }
    .ne-sugerencia-cabecera { display: flex; justify-content: space-between; gap: 10px; align-items: baseline; flex-wrap: wrap; }
    .ne-sugerencia label { display: flex; gap: 8px; align-items: center; cursor: pointer; margin: 6px 0; }
    .ne-etiqueta { font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 6px; background: #EEF2F6; color: #475569; }
    .ne-etiqueta-obligatorio { background: #FDECEC; color: #A71D2A; }
    .ne-resultados { border: 1px solid #E2E8F0; border-radius: 8px; margin-top: 8px; max-height: 320px; overflow-y: auto; }
    .ne-resultados div { padding: 9px 12px; border-bottom: 1px solid #F1F5F9; display: flex; justify-content: space-between; gap: 10px; align-items: center; }
    .ne-historial li { margin-bottom: 6px; font-size: 13px; }
    @media (max-width: 700px) {
        .ortomax-table th, .ortomax-table td { font-size: 12.5px; padding: 8px 6px; }
    }
</style>
