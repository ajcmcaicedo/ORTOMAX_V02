<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\InicioController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BodegaController;
use App\Http\Controllers\InstitucionController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\RegionalController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ProductoMaestroController;
use App\Http\Controllers\ProductoProveedorController;
use App\Http\Controllers\SincronizacionController;
use App\Http\Controllers\LoteController;


/*
|--------------------------------------------------------------------------
| Autenticación
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'mostrarLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth');

// Recuperación de contraseña
Route::get('/forgot-password', [PasswordResetController::class, 'mostrarFormulario'])
    ->name('password.request');

Route::post('/forgot-password', [PasswordResetController::class, 'enviarEnlace'])
    ->name('password.email');

Route::get('/reset-password/{token}', [PasswordResetController::class, 'mostrarFormularioReset'])
    ->name('password.reset');

Route::post('/reset-password', [PasswordResetController::class, 'actualizarPassword'])
    ->name('password.update');





/*
|--------------------------------------------------------------------------
| Inicio
|--------------------------------------------------------------------------
*/

Route::get('/', [InicioController::class, 'inicio'])
    ->middleware('auth');


/*
|--------------------------------------------------------------------------
| Regionales
|--------------------------------------------------------------------------
*/
Route::get('/regionales', [RegionalController::class, 'index'])
    ->middleware(['auth', 'permiso:regionales.ver'])
    ->name('regionales.index');

Route::get('/regionales/create', [RegionalController::class, 'create'])
    ->middleware(['auth', 'permiso:regionales.crear'])
    ->name('regionales.create');

Route::post('/regionales', [RegionalController::class, 'store'])
    ->middleware(['auth', 'permiso:regionales.crear'])
    ->name('regionales.store');

Route::get('/regionales/{regionale}', [RegionalController::class, 'show'])
    ->middleware(['auth', 'permiso:regionales.ver'])
    ->name('regionales.show');

Route::get('/regionales/{regionale}/edit', [RegionalController::class, 'edit'])
    ->middleware(['auth', 'permiso:regionales.editar'])
    ->name('regionales.edit');

Route::match(['put', 'patch'], '/regionales/{regionale}', [RegionalController::class, 'update'])
    ->middleware(['auth', 'permiso:regionales.editar'])
    ->name('regionales.update');

Route::delete('/regionales/{regionale}', [RegionalController::class, 'destroy'])
    ->middleware(['auth', 'permiso:regionales.eliminar'])
    ->name('regionales.destroy');


/*
|--------------------------------------------------------------------------
| Bodegas
|--------------------------------------------------------------------------
*/

Route::get('/bodegas', [BodegaController::class, 'index'])
    ->middleware(['auth', 'permiso:bodegas.ver'])
    ->name('bodegas.index');

Route::get('/bodegas/create', [BodegaController::class, 'create'])
    ->middleware(['auth', 'permiso:bodegas.crear'])
    ->name('bodegas.create');

Route::post('/bodegas', [BodegaController::class, 'store'])
    ->middleware(['auth', 'permiso:bodegas.crear'])
    ->name('bodegas.store');

Route::get('/bodegas/{bodega}/edit', [BodegaController::class, 'edit'])
    ->middleware(['auth', 'permiso:bodegas.editar'])
    ->name('bodegas.edit');

Route::match(['put', 'patch'], '/bodegas/{bodega}', [BodegaController::class, 'update'])
    ->middleware(['auth', 'permiso:bodegas.editar'])
    ->name('bodegas.update');


Route::delete('/bodegas/{bodega}', [BodegaController::class, 'destroy'])
    ->middleware(['auth', 'permiso:bodegas.eliminar'])
    ->name('bodegas.destroy');




// INSTITUCIONES

Route::get('/instituciones', [InstitucionController::class, 'index'])
    ->middleware(['auth', 'permiso:instituciones.ver'])
    ->name('instituciones.index');

Route::get('/instituciones/create', [InstitucionController::class, 'create'])
    ->middleware(['auth', 'permiso:instituciones.crear'])
    ->name('instituciones.create');

Route::post('/instituciones', [InstitucionController::class, 'store'])
    ->middleware(['auth', 'permiso:instituciones.crear'])
    ->name('instituciones.store');

Route::get('/instituciones/exportar', [InstitucionController::class, 'exportExcel'])
    ->middleware(['auth', 'permiso:instituciones.exportar'])
    ->name('instituciones.exportar');

Route::get('/instituciones/{institucion}', [InstitucionController::class, 'show'])
    ->middleware(['auth', 'permiso:instituciones.ver'])
    ->name('instituciones.show');

Route::get('/instituciones/{institucion}/edit', [InstitucionController::class, 'edit'])
    ->middleware(['auth', 'permiso:instituciones.editar'])
    ->name('instituciones.edit');

Route::put('/instituciones/{institucion}', [InstitucionController::class, 'update'])
    ->middleware(['auth', 'permiso:instituciones.editar'])
    ->name('instituciones.update');

Route::delete('/instituciones/{institucion}', [InstitucionController::class, 'destroy'])
    ->middleware(['auth', 'permiso:instituciones.eliminar'])
    ->name('instituciones.destroy');

Route::post('/instituciones/importar/preview', [InstitucionController::class, 'importPreview'])
    ->middleware(['auth', 'permiso:instituciones.importar'])
    ->name('instituciones.importar.preview');

Route::post('/instituciones/importar/confirmar', [InstitucionController::class, 'importConfirm'])
    ->middleware(['auth', 'permiso:instituciones.importar'])
    ->name('instituciones.importar.confirmar');




/*
|--------------------------------------------------------------------------
| Proveedores
|------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/proveedores', [ProveedorController::class, 'index'])
        ->middleware('permiso:proveedores.ver')
        ->name('proveedores.index');

    Route::post('/proveedores', [ProveedorController::class, 'store'])
        ->middleware('permiso:proveedores.crear')
        ->name('proveedores.store');
    
    Route::get('/proveedores/exportar', [ProveedorController::class, 'exportar'])
        ->middleware('permiso:proveedores.exportar')
        ->name('proveedores.exportar');
    
    Route::post('/proveedores/importar', [ProveedorController::class, 'importar'])
        ->middleware('permiso:proveedores.importar')
        ->name('proveedores.importar');

    Route::get('/proveedores/{proveedor}', [ProveedorController::class, 'show'])
        ->middleware('permiso:proveedores.ver')
        ->name('proveedores.show');

    Route::put('/proveedores/{proveedor}', [ProveedorController::class, 'update'])
        ->middleware('permiso:proveedores.editar')
        ->name('proveedores.update');

    Route::delete('/proveedores/{proveedor}', [ProveedorController::class, 'destroy'])
        ->middleware('permiso:proveedores.eliminar')
        ->name('proveedores.destroy');





});




/*
|--------------------------------------------------------------------------
| Productos
|--------------------------------------------------------------------------
*/

Route::get('/productos', [ProductoController::class, 'index'])
    ->middleware(['auth', 'permiso:productos.ver'])
    ->name('productos.index');

Route::post('/productos/importar/preview', [ProductoController::class, 'importPreview'])
    ->name('productos.importar.preview');

Route::post('/productos/importar/confirmar', [ProductoController::class, 'importConfirm'])
    ->name('productos.importar.confirmar');

Route::get('/productos/importar', [ProductoController::class, 'importar'])
    ->name('productos.importar');

Route::get(
        '/productos/importar/plantilla',
        [ProductoController::class, 'descargarPlantilla']
    )->name('productos.importar.plantilla');


/*
|--------------------------------------------------------------------------
| LOTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/lotes/importar', [LoteController::class, 'importar'])
        ->name('lotes.importar');

    Route::post('/lotes/importar/preview', [LoteController::class, 'importPreview'])
        ->name('lotes.importar.preview');

    Route::post('/lotes/importar/confirmar', [LoteController::class, 'importConfirm'])
        ->name('lotes.importar.confirmar');

    Route::get('/lotes/importar/plantilla', [LoteController::class, 'descargarPlantilla'])
        ->name('lotes.importar.plantilla');

    Route::get('/lotes/exportar', [LoteController::class, 'exportar'])
        ->name('lotes.exportar');

    // EXPORTACION DE LOTES CON PROGRESO
    Route::post('/lotes/exportar/iniciar', [LoteController::class, 'iniciarExportacion'])
    ->name('lotes.exportar.iniciar');

    Route::get('/lotes/exportar/progreso/{token}', [LoteController::class, 'progresoExportacion'])
    ->name('lotes.exportar.progreso');

    Route::get('/lotes/exportar/descargar/{token}', [LoteController::class, 'descargarExportacion'])
    ->name('lotes.exportar.descargar');
            

});





/*
|--------------------------------------------------------------------------
| Stock
|--------------------------------------------------------------------------
*/

Route::get('/stock/{productoId}', [StockController::class, 'mostrar'])
    ->middleware('auth');

Route::get('/stock/{productoId}/consultar', [StockController::class, 'consultar'])
    ->middleware('auth');


/*
|--------------------------------------------------------------------------
| Facturas
|--------------------------------------------------------------------------
*/

Route::get('/facturas', [FacturaController::class, 'index'])
    ->middleware('auth');

Route::get('/facturas/{id}', [FacturaController::class, 'detalle'])
    ->middleware('auth');



/*
|--------------------------------------------------------------------------
| \Producto Maestro
|------------------------------------------------------------------------
*/





// ============================================================
// PRODUCTOS MAESTROS
// ============================================================

Route::middleware(['auth', 'permiso:productos_maestros.ver'])->group(function () {

    Route::get('/productos-maestros', [ProductoMaestroController::class, 'index'])
        ->name('productos_maestros.index');

    /*
    |--------------------------------------------------------------------------
    | IMPORTANTE:
    | Esta ruta debe estar ANTES de /productos-maestros/{productosMaestro}
    |--------------------------------------------------------------------------
    */
    Route::get('/productos-maestros/exportar', [ProductoMaestroController::class, 'exportar'])
        ->name('productos_maestros.exportar');

    Route::get('/productos-maestros/{productosMaestro}', [ProductoMaestroController::class, 'show'])
        ->name('productos_maestros.show');
});


Route::middleware(['auth', 'permiso:productos_maestros.crear'])->group(function () {

    Route::post('/productos-maestros', [ProductoMaestroController::class, 'store'])
        ->name('productos_maestros.store');
});


Route::middleware(['auth', 'permiso:productos_maestros.editar'])->group(function () {

    Route::put('/productos-maestros/{productosMaestro}', [ProductoMaestroController::class, 'update'])
        ->name('productos_maestros.update');
});


Route::middleware(['auth', 'permiso:productos_maestros.eliminar'])->group(function () {

    Route::delete('/productos-maestros/{productosMaestro}', [ProductoMaestroController::class, 'destroy'])
        ->name('productos_maestros.destroy');
});


Route::middleware(['auth', 'permiso:productos_maestros.importar'])->group(function () {

    Route::post('/productos-maestros/importar', [ProductoMaestroController::class, 'importar'])
        ->name('productos_maestros.importar');
});



// ============================================================
// PRODUCTOS PROVEEDORES
// ============================================================

/*
|--------------------------------------------------------------------------
| Consulta y exportación
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'permiso:productos_proveedores.ver'])->group(function () {

    Route::get('/productos-proveedores', [ProductoProveedorController::class, 'index'])
        ->name('productos_proveedores.index');

    Route::get('/productos-proveedores/exportar', [ProductoProveedorController::class, 'exportar'])
        ->middleware('permiso:productos_proveedores.exportar')
        ->name('productos_proveedores.exportar');

    Route::get('/productos-proveedores/buscar-productos', [ProductoProveedorController::class, 'buscarProductos'])
        ->name('productos_proveedores.buscar_productos');

    Route::get('/productos-proveedores/{productoProveedor}', [ProductoProveedorController::class, 'show'])
        ->name('productos_proveedores.show');
});


/*
|--------------------------------------------------------------------------
| Importación
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'permiso:productos_proveedores.importar'])->group(function () {

    Route::post('/productos-proveedores/importar', [ProductoProveedorController::class, 'importar'])
        ->name('productos_proveedores.importar');

    Route::get('/productos-proveedores/descargar-errores/{archivo}', [ProductoProveedorController::class, 'descargarErrores'])
        ->name('productos_proveedores.descargar_errores');
});


Route::middleware(['auth', 'permiso:productos_proveedores.crear'])->group(function () {

    Route::post('/productos-proveedores', [ProductoProveedorController::class, 'store'])
        ->name('productos_proveedores.store');
});


Route::middleware(['auth', 'permiso:productos_proveedores.editar'])->group(function () {

    Route::put('/productos-proveedores/{productoProveedor}', [ProductoProveedorController::class, 'update'])
        ->name('productos_proveedores.update');
});


Route::middleware(['auth', 'permiso:productos_proveedores.eliminar'])->group(function () {

    Route::delete('/productos-proveedores/{productoProveedor}', [ProductoProveedorController::class, 'destroy'])
        ->name('productos_proveedores.destroy');
});

/*
|--------------------------------------------------------------------------
| Sincronizaciones
|--------------------------------------------------------------------------
*/

Route::get('/sincronizaciones/productos', [SincronizacionController::class, 'productos'])
    ->middleware(['auth', 'permiso:productos.ver'])
    ->name('sincronizaciones.productos');

Route::post('/sincronizaciones/productos/ejecutar', [SincronizacionController::class, 'ejecutarProductos'])
    ->middleware(['auth', 'permiso:productos.ver'])
    ->name('sincronizaciones.productos.ejecutar');

Route::get('/sincronizaciones/productos/estado', [SincronizacionController::class, 'estadoProductos'])
    ->middleware(['auth', 'permiso:productos.ver'])
    ->name('sincronizaciones.productos.estado');

    Route::get('/sincronizaciones/bodegas', [SincronizacionController::class, 'bodegas'])
    ->middleware(['auth', 'permiso:productos.ver'])
    ->name('sincronizaciones.bodegas');

Route::post('/sincronizaciones/bodegas/ejecutar', [SincronizacionController::class, 'ejecutarBodegas'])
    ->middleware(['auth', 'permiso:productos.ver'])
    ->name('sincronizaciones.bodegas.ejecutar');

Route::get('/sincronizaciones/bodegas/estado', [SincronizacionController::class, 'estadoBodegas'])
    ->middleware(['auth', 'permiso:productos.ver'])
    ->name('sincronizaciones.bodegas.estado');