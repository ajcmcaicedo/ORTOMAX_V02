<?php

namespace App\Enums;

/**
 * Tipo de ítem. Formaliza los valores que ya usan
 * equipo_detalles.tipo_contenido y nota_entrega_detalles.tipo_linea.
 */
enum TipoItem: string
{
    /** Línea cabecera de un equipo dentro de la nota. */
    case EQUIPO = 'EQUIPO';

    /** Placas, tornillos, clavos: por lote, consumible, facturable. */
    case IMPLANTE = 'IMPLANTE';

    /** Retornable; no se factura por consumo (salvo pérdida/daño autorizado). */
    case INSTRUMENTAL = 'INSTRUMENTAL';

    /** Motores, baterías: por número de serie, retornable, sin consumo. */
    case ACTIVO = 'ACTIVO';

    /**
     * Material adicional fuera de la composición estándar.
     * Se trata como consumible y facturable; se controla por lote
     * cuando el producto tiene lote.
     */
    case ADICIONAL = 'ADICIONAL';

    public function esConsumible(): bool
    {
        return $this === self::IMPLANTE || $this === self::ADICIONAL;
    }

    public function esFacturablePorConsumo(): bool
    {
        return $this->esConsumible();
    }

    public function esRetornable(): bool
    {
        return $this === self::INSTRUMENTAL || $this === self::ACTIVO;
    }

    public function controlaLote(): bool
    {
        return $this === self::IMPLANTE || $this === self::ADICIONAL;
    }

    public function controlaSerie(): bool
    {
        return $this === self::ACTIVO;
    }
}
