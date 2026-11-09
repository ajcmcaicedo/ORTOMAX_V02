<?php

namespace App\Services\Documentos;

use Illuminate\Support\Facades\DB;

/**
 * Numeración consecutiva por prefijo y año.
 *
 * Debe llamarse dentro de la transacción que crea el documento: el
 * contador queda bloqueado hasta el commit y, si la creación falla, el
 * número se libera (no quedan huecos).
 */
class NumeradorDocumentos
{
    /**
     * Notas de Entrega: "NE" + año (2) + consecutivo (8) = 12 caracteres,
     * que es el ancho de notas_entrega.numero (char 12).
     */
    public function siguienteNotaEntrega(): string
    {
        $anio = now()->format('Y');
        $n = $this->siguiente("NE-{$anio}");

        return 'NE' . substr($anio, -2) . str_pad((string) $n, 8, '0', STR_PAD_LEFT);
    }

    public function siguiente(string $clave): int
    {
        return DB::transaction(function () use ($clave) {
            DB::table('secuencias_documentos')->insertOrIgnore([
                'clave' => $clave,
                'ultimo_numero' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $actual = DB::table('secuencias_documentos')
                ->where('clave', $clave)
                ->lockForUpdate()
                ->value('ultimo_numero');

            $siguiente = $actual + 1;

            DB::table('secuencias_documentos')
                ->where('clave', $clave)
                ->update(['ultimo_numero' => $siguiente, 'updated_at' => now()]);

            return $siguiente;
        });
    }
}
