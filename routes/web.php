<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', function () {
    return view('welcome');
});

Route::get('/tetete', function () {
    return view('test3');
});

Route::get('/tete', function () {
    return view('test2');
});

Route::get('/testeando', function () {
    return view('test');
});

Route::get('/gracias', function () {
    return view('goodbye');
});

Route::get('/colaboradores', function () {
    return view('user.list',['users'=>App\User::all()]);
});

Auth::routes();

/**
* Estructura mas ordenada de aplicacion, layout...
*/
Route::get('/home', function()
{
    return View::make('pages.home');
});
Route::get('/about', function()
{
    return View::make('pages.about');
});
Route::get('projects', function()
{
    return View::make('pages.projects');
});
Route::get('/contact', function()
{
    return View::make('pages.contact');
});
Route::get('/serverinfo', function()
{
    return View::make('pages.serverinfo');
});

Route::get('/guia', function()
{
    return View::make('segmentacion.guia');
});
Route::get('/sala', 'SalaController@index')->name('sala');

Route::get('/setup', 'SetupController@index')->name('setup');
Route::get('/setup-panel', 'SetupController@panel')->name('setup.panel');

// ---------- OPERATIVOS (ABM) ----------
Route::get('operativos', 'OperativoController@index')->name('operativos.index');
Route::get('operativos/new', 'OperativoController@create')->name('operativos.create');
Route::post('operativos', 'OperativoController@store')->name('operativos.store');
Route::get('operativos/{operativo}', 'OperativoController@show')->name('operativos.show');
Route::get('operativos/{operativo}/edit', 'OperativoController@edit')->name('operativos.edit');
Route::put('operativos/{operativo}', 'OperativoController@update')->name('operativos.update');
Route::delete('operativos/{operativo}', 'OperativoController@destroy')->name('operativos.destroy');
Route::post('operativos/{operativo}/provincias', 'OperativoController@attachProvincia')->name('operativos.provincias.attach');
Route::delete('operativos/{operativo}/provincias/{provincia}', 'OperativoController@detachProvincia')->name('operativos.provincias.detach');
Route::post('operativos/{operativo}/publicar', 'OperativoController@publicar')->name('operativos.publicar');
Route::post('operativos/{operativo}/despublicar', 'OperativoController@despublicar')->name('operativos.despublicar');
Route::post('operativos/{operativo}/seleccionar', 'OperativoController@seleccionar')->name('operativos.seleccionar');
Route::post('operativo-actual/quitar', 'OperativoController@deseleccionar')->name('operativos.deseleccionar');
Route::post('objeto-actual/seleccionar', 'ObjetoActualController@seleccionar')->name('objeto-actual.seleccionar');
Route::post('objeto-actual/subir', 'ObjetoActualController@subirNivel')->name('objeto-actual.subir');
Route::post('objeto-actual/quitar', 'ObjetoActualController@deseleccionar')->name('objeto-actual.quitar');
Route::get('controles-calidad', 'ControlCalidadController@index')->name('controles-calidad');
Route::get('controles-calidad/radios-duplicados/{codigo}', 'ControlCalidadController@verDuplicado')->name('controles-calidad.duplicado');
Route::post('controles-calidad/radios-duplicados/{codigo}/historico', 'ControlCalidadController@marcarHistorico')->name('controles-calidad.marcar-historico');
Route::post('controles-calidad/radios-duplicados/{codigo}/completar-fraccion', 'ControlCalidadController@completarFraccion')->name('controles-calidad.completar-fraccion');
Route::post('controles-calidad/radios-duplicados/{codigo}/eliminar/{radioId}', 'ControlCalidadController@eliminarRadio')->name('controles-calidad.eliminar-radio');
Route::get('/setup/test', 'SetupController@testFlash')->name('setup.test');
Route::get('/setup/{esquema}', 'SetupController@permisos')->name('setup.permisos');
Route::get('/setup/fixSRID/{esquema}/{srid}',
'SetupController@setSRIDSchema')->name('setup.srid.topologia');
Route::get('/setup/topo/pais',
'SetupController@cargarTopologiasPais')->name('setup.topologia.pais');
Route::get('/setup/topo/{esquema}',
'SetupController@cargarTopologia')->name('setup.topologia');
Route::get('/setup/topo/{esquema}/{tolerancia}',
'SetupController@cargarTopologia')->name('setup.topologia');
Route::get('/setup/topo_drop/{esquema}',
'SetupController@dropTopologia')->name('setup.drop.topologia');
Route::get('/setup/index/{esquema}',
'SetupController@addIndexListado')->name('setup.index');
Route::get('/setup/index/id/{tabla}',
'SetupController@addIndexId')->name('setup.indexId');
Route::get('/setup/geo/{esquema}',
'SetupController@georeferenciarEsquema')->name('setup.geo');
Route::get('/setup/geo/{esquema}/{n}',
'SetupController@georeferenciarEsquema')->name('setup.geo.force');
Route::get('/setup/geo/{esquema}/{n}/{frac}',
'SetupController@georeferenciarEsquema')->name('setup.geo.force.frac');
Route::get('/setup/geo/{esquema}/{n}/{frac}/{radio}',
'SetupController@georeferenciarEsquema')->name('setup.geo.force.frac.radio');
Route::get('/setup/geoseg/{esquema}',
'SetupController@georeferenciarSegmentacionEsquema')->name('setup.geoseg');
Route::get('/setup/segmenta/{esquema}',
'SetupController@segmentarEsquema')->name('setup.segmenta');
Route::get('/setup/limpia/{esquema}',
'SetupController@limpiarEsquema')->name('setup.limpia');
Route::get('/setup/muestrea/{esquema}',
'SetupController@muestreaEsquema')->name('setup.muestrea');
Route::get('/setup/limpiar/Temporales',
'SetupController@limpiaEsquemasTemporales')->name('setup.limpia.temporales');
Route::get('/setup/junta/{esquema}',
'SetupController@juntarSegmentos')->name('setup.junta');
Route::get('/setup/junta/{esquema}/{frac}/{radio}',
'SetupController@juntarSegmentos')->name('setup.junta.frac.radio');
Route::get('/setup/index/{esquema}/{tabla}/{cols}',
'SetupController@createIndex')->name('setup.create.index');
Route::get('/setup/grupogeoestadistica/{usuario}',
'SetupController@grupoGeoestadistica')->name('setup.grupogeo');
Route::get('/setup/grupogeoestadistica/tabla/{tabla}',
'SetupController@grupoGeoestadisticaTabla')->name('setup.grupogeo.tabla');
Route::get('/setup/duplicadosLSV/{esquema}',
'SetupController@limpiaListado')->name('setup.limpialistado');
Route::get('/setup/updateTipoViv/{esquema}',
'SetupController@tipoVivdeDescripcion')->name('setup.tipovivdescripcion');
Route::get('/setup/update/LS',
'SetupController@juntaListadosSegmentados')->name('setup.junta.listados');
Route::get('/setup/update/R3',
'SetupController@juntaR3')->name('setup.juntaR3');
Route::get('/setup/update/Manzanas',
'SetupController@juntaManzanas')->name('setup.manzanas');
Route::get('/setup/update/Vias',
'SetupController@juntaVias')->name('setup.juntaVias');
Route::get('/setup/update/Localidades',
'SetupController@juntaLocalidades')->name('setup.juntaLocalidades');
Route::get('/setup/update/localidad_srid',
'SetupController@cargaSrids')->name('setup.cargaSrids');
Route::get('/setup/update/corrige_localidad_srid',
'SetupController@corrigeSrids')->name('setup.corrigeSrids');
Route::get('/setup/update/Cuadras',
'SetupController@juntaCuadras')->name('setup.cuadras');
Route::get('/setup/update/RadiosDeListados',
'SetupController@radiosDeListados')->name('setup.radiosDeListados');
Route::get('/setup/update/RadiosDeArcs',
'SetupController@radiosDeArcs')->name('setup.radiosDeArcs');


Route::get('/setup/adyacencias/{esquema}',
'SetupController@generarAdyacenciasEsquema')->name('setup.adyacencias');
Route::get('/setup/juntaMenores/{esquema}/{frac}/{radio}/{n}',
'SetupController@juntarSegmentosMenores')->name('setup.junta_menores');

Route::get('/inicio', 'HomeController@index')->name('inicio');
Route::resource('/listado', 'ListadoController',['only' => [
   'index', 'show', 'save'
]]);

/**
 * Segmentos
 */
Route::resource('/segmentos', 'SegmentoController',['only' => [
   'index', 'show', 'save'
]]);
Route::post('/domicilio/guardar/','DomicilioController@save');
/**
 * Segmenter
 */
Route::get('/segmentador', 'SegmenterController@index')->name('segmentador');
Route::post('/segmentador/guardar', 'SegmenterController@store');

Route::get('/', function () {
    flash(' Bienvenides !')->success();
    return view('welcome');
});

Route::post('/import', ['as'=>'import', 'uses'=>'Controller@import']);

Route::get('csv_file', 'CsvFile@index');

Route::get('csv_file/export', 'CsvFile@csv_export')->name('export');

Route::post('csv_file/import', 'CsvFile@csv_import')->name('import');

Route::get('search_provincia', 'AutoCompleteProvinciaController@index');
Route::get('autocomplete_provincia', 'AutoCompleteProvinciaController@search');
Route::get('provincia','ProvinciaController@index');

// ---------- PERFIL ----------
Route::middleware(['auth'])->group(function () {
    Route::get('perfil', 'UserController@mostrarPerfil')->name('perfil');
    Route::post('perfil/edit-username', 'UserController@editarUsername')->name('editarUsername');
    Route::post('perfil/edit-email', 'UserController@editarEmail')->name('editarEmail');
    Route::post('perfil/edit-password', 'UserController@editarContraseña')->name('editarContraseña');
    Route::post('perfil/edit-profile-pic', 'UserController@editarFoto')->name('editarFoto');
});

// ---------- USUARIOS ----------
Route::get('users', 'UserController@listarUsuarios')->name('admin.listarUsuarios');
Route::middleware(['auth'])->group(function () {
    Route::get('users/{user}/roles', 'UserController@editarRolUsuario')->name('admin.editarRolUsuario');
    Route::get('users/{user}/permission', 'UserController@editarPermisoUsuario')->name('admin.editarPermisoUsuario');
    Route::get('users/{user}/filter', 'UserController@editarFiltroUsuario')->name('admin.editarFiltroUsuario');
});

// ---------- FILTROS ----------
Route::middleware(['auth'])->group(function () {
    Route::get('filtros', 'FilterController@listarFiltros')->name('admin.listarFiltros');
    Route::get('filtros/{filter}/rename', 'FilterController@renombrarFiltro')->name('admin.renombrarFiltro');
    Route::get('filtros/new', 'FilterController@crearFiltro')->name('admin.crearFiltro');
    Route::get('filtros/provs/edit', 'FilterController@editarFiltrosProvs')->name('admin.editarFiltrosProvs');
    Route::get('filtros/provs', 'FilterController@listarFiltrosProvs')->name('admin.listarFiltrosProvs');
    Route::delete('filtros/{filter}', 'FilterController@eliminarFiltro')->name('admin.eliminarFiltro');
});

// ---------- ROLES ----------
Route::middleware(['auth'])->group(function () {
    Route::get('roles', 'RoleController@listarRoles')->name('admin.listarRoles');
    Route::get('roles/{role}/edit', 'RoleController@editarRol')->name('admin.editarRol');
    Route::get('roles/new', 'RoleController@crearRol')->name('admin.crearRol');
    Route::get('roles/{role}/detail', 'RoleController@detallesRol')->name('admin.detallesRol');
    Route::delete('roles/{role}', 'RoleController@eliminarRol')->name('admin.eliminarRol');
});

// ---------- PROVINCIAS --------
Route::get('provs-list', 'ProvinciaController@provsList');
Route::get('provs','ProvinciaController@index');
// prov/deptos y prov/list van ANTES de prov/{provincia} a propósito: son
// más específicos, pero como Laravel matchea rutas en orden de
// declaración, si el wildcard queda primero se come "deptos"/"list" como
// si fueran un id de provincia (bug real encontrado navegando: probar
// /prov/deptos tiraba "sintaxis de entrada no válida para tipo bigint:
// «deptos»" porque prov/{provincia} lo capturaba primero).
Route::get('prov/deptos/{provincia?}','DepartamentoController@index');
Route::get('prov/list/{provincia}','DepartamentoController@list');
Route::get('prov/{provincia}','ProvinciaController@show');
Route::post('prov/{provincia}','ProvinciaController@show_post');
Route::delete('provincia/{provincia}','ProvinciaController@destroy')->name('provincia.delete');

// ---------- DEPARTAMENTOS --------

Route::post('deptos-list', 'DepartamentoController@listAll')->name('deptos-list');
Route::get('vias', 'ViaController@index')->name('vias');
Route::get('vias/resumen', 'ViaController@resumen')->name('vias.resumen');
Route::post('vias/corregir-tipo', 'ViaController@corregirTipo')->name('vias.corregir-tipo');
Route::post('vias/reaplicar-correcciones', 'ViaController@reaplicarCorrecciones')->name('vias.reaplicar-correcciones');
Route::post('vias-list', 'ViaController@listAll')->name('vias-list');
Route::get('depto/{departamento}','DepartamentoController@show')->name('depto.show');
Route::post('depto/{departamento}','DepartamentoController@show_post');
Route::post('depto/{departamento}/localidad/{localidad}/vincular', 'DepartamentoController@vincularLocalidad')->name('depto.vincular-localidad');
Route::post('depto/{departamento}/vincular-localidades', 'DepartamentoController@vincularTodasLasLocalidades')->name('depto.vincular-localidades');

// ---------- LOCALIDADES --------
Route::get('locas-list', 'LocalidadController@locasList');
Route::post('locas-list', 'LocalidadController@locasList');
Route::get('localidades','LocalidadController@list');
Route::get('localidades_json','LocalidadController@index');
Route::get('localidad/{localidad}','LocalidadController@show');
Route::get('localidad/codigo/{codigo}','LocalidadController@show_codigo');
Route::post('localidad/{localidad}','LocalidadController@segmenta_post');
Route::post('localidad-segmenta/{localidad}','LocalidadController@segmenta_post');
Route::get('localidad-segmenta/{localidad}','LocalidadController@segmenta_post');
Route::post('localidad-segmenta-run/{localidad}','LocalidadController@run_segmentar');
//Route::post('localidad/{localidad}','LocalidadController@show_post');
// PxSeg Localidad
Route::get('localidad/{localidad}/pxseg','LocalidadController@ver_pxseg')->name('localidad-ver-segmentacion-pxseg');
Route::get('localidad/{localidad}/segmentacion','LocalidadController@ver_segmentacion')->name('localidad-ver-segmentacion');
Route::get('localidad/{localidad}/segmentacion-lados','LocalidadController@ver_segmentacion_lados')->name('localidad-ver-segmentacion-lados');
Route::get('localidad/{localidad}/grafico','LocalidadController@ver_segmentacion_grafico')->name('localidad-ver-segmentacion-grafico');
Route::post('localidad/{localidad}/grafico','LocalidadController@ver_segmentacion_grafico_resumen')->name('localidad-ver-segmentacion-grafico');

// ---------- AGLOMERADOS --------
Route::get('aglos-list', 'AglomeradoController@aglosList');
Route::post('aglos-list', 'AglomeradoController@aglosList');
Route::get('aglos','AglomeradoController@index');
Route::get('aglo/{aglomerado}','AglomeradoController@show')->name('aglo-ver');
Route::post('aglo/{aglomerado}','AglomeradoController@show_post');
Route::post('aglo-segmenta/{aglomerado}','AglomeradoController@segmenta_post');
Route::get('aglo-segmenta/{aglomerado}','AglomeradoController@segmenta_post');
Route::post('aglo-segmenta-run/{aglomerado}','AglomeradoController@run_segmentar');

// --------- SEGMENTACION X AGLOMERADO --------- 
Route::get('aglo/{aglomerado}/pxseg','AglomeradoController@ver_pxseg')->name('ver-segmentacion-pxseg');
Route::get('ver-segmentacion/{aglomerado}','AglomeradoController@ver_segmentacion')->name('ver-segmentacion');
Route::get('ver-segmentacion-lados/{aglomerado}','AglomeradoController@ver_segmentacion_lados')->name('ver-segmentacion-lados');
Route::get('ver-segmentacion/grafico/{aglomerado}','AglomeradoController@ver_segmentacion_grafico')->name('ver-segmentacion-grafico');
Route::post('ver-segmentacion-grafico/{aglomerado}','AglomeradoController@ver_segmentacion_grafico')->name('ver-segmentacion-grafico');

Route::get('ver-segmentacion/grafico-resumen/{aglomerado}','AglomeradoController@ver_segmentacion_grafico_resumen')->name('ver-segmentacion-grafico-resumen');
Route::post('ver-segmentacion-grafico-resumen/{aglomerado}','AglomeradoController@ver_segmentacion_grafico_resumen')->name('ver-segmentacion-grafico-resumen');
//ver_segmentacion_lados_grafico_resumen
Route::get('ver-segmentacion-lados/grafico-resumen/{aglomerado}','AglomeradoController@ver_segmentacion_lados_grafico_resumen')->name('ver-segmentacion-lados-grafico-resumen');
Route::post('ver-segmentacion-lados-grafico-resumen/{aglomerado}','AglomeradoController@ver_segmentacion_lados_grafico_resumen')->name('ver-segmentacion-lados-grafico-resumen');

// ---------- RADIOS Localidad Depto --------
// Para CABA
Route::get('radios/{localidad}/{departamento}','RadiosController@show');
Route::get('radio/{radio}','RadioController@show');
Route::get('radio/codigo/{codigo}','RadioController@show_codigo');

// ---------- GRAFOS AGLOMERADOS --------
Route::get('grafo/{aglomerado}','SegmentacionController@index')->name('index');
Route::get('grafo/{aglomerado}/{radio}/','SegmentacionController@ver_grafo_legacy')->name('ver-grafo-redirect');
Route::get('radio/{localidad}/{radio}/','SegmentacionController@ver_grafo')->name('ver-grafo');

// ---------- ARCHIVOS --------
Route::middleware(['auth'])->group(function () {
    Route::post('archivos','ArchivoController@index');
    Route::get('archivos','ArchivoController@index')->name('archivos');
    Route::get('archivo/{archivo}','ArchivoController@show');
    Route::post('archivo/{archivo}','ArchivoController@show');
    Route::delete('archivo/{archivo}','ArchivoController@destroy');
    Route::put('archivo/{archivo}/detach','ArchivoController@detach');
    Route::get('archivo/{archivo}/descargar','ArchivoController@descargar');
    Route::get('archivo/{archivo}/procesar','ArchivoController@procesar');
    Route::get('archivos/limpiar','ArchivoController@eliminar_repetidos')->name('limpiar_archivos');
    Route::get('archivos/repetidos','ArchivoController@listar_repetidos')->name('archivos_repetidos');
    Route::get('archivos/recalcular_cs/{archivo_id?}','ArchivoController@recalcular_checksums')->name('recalcular_checksums'); //el parametro es opcional
    Route::get('archivos/checksums_obsoletos','ArchivoController@listar_checksums_obsoletos')->name('checksums_obsoletos');
    Route::get('archivos/checksums_no_calculados','ArchivoController@listar_checksums_no_calculados')->name('checksums_no_calculados');
});

// ---------- TABLERO ---------

Route::get('informe/prov','TableroController@GraficoProvincias');
Route::post('informe/prov','TableroController@GraficoProvincias');
Route::get('informe/avances','TableroController@GraficoAvances');
Route::post('informe/avances','TableroController@GraficoAvances');
Route::get('informe/avance','TableroController@GraficoAvance');
Route::post('informe/avance','TableroController@GraficoAvance');

//Route::get('mail', 'MailCsvController@index');

/* Logout via GET */
Route::get('/logout', 'Auth\LoginController@logout');

Auth::routes();

Route::get('/clear-cache', function() {
    Artisan::call('cache:clear');
    return "Cache is cleared";
});

Route::get('/home', 'HomeController@index')->name('home');
