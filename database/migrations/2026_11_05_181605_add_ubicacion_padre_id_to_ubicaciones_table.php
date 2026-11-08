
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->foreignId('ubicacion_padre_id')
                ->nullable()
                ->after('bodega_id')
                ->constrained('ubicaciones')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->index(
                'ubicacion_padre_id',
                'idx_ubicaciones_padre'
            );
        });
    }

    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->dropForeign(['ubicacion_padre_id']);
            $table->dropIndex('idx_ubicaciones_padre');
            $table->dropColumn('ubicacion_padre_id');
        });
    }
};
