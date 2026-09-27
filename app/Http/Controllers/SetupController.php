<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use App\MyDB;

class SetupController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:run-setup');
    }

    /**
     * Panel de desarrollo: lista los endpoints de /setup/* agrupados, con un
     * input por parámetro de la URL y un botón que arma el link y lo abre -
     * hoy la única forma de dispararlos es escribiendo la URL a mano
     * (relevamiento técnico, riesgo de "batch manual sin scheduler ni cola").
     * No agrega ninguna ruta ni lógica nueva de negocio, solo un índice
     * clickeable de lo que ya existe.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function panel()
    {
        $grupos = [
            'Topología' => [
                ['label' => 'Cargar topología (país)', 'path' => '/setup/topo/pais'],
                ['label' => 'Cargar topología (esquema)', 'path' => '/setup/topo/{esquema}'],
                ['label' => 'Cargar topología (esquema, tolerancia)', 'path' => '/setup/topo/{esquema}/{tolerancia}'],
                ['label' => 'Borrar topología', 'path' => '/setup/topo_drop/{esquema}'],
                ['label' => 'Generar adyacencias', 'path' => '/setup/adyacencias/{esquema}'],
            ],
            'Georreferenciación / SRID' => [
                ['label' => 'Fijar SRID + regenerar topología', 'path' => '/setup/fixSRID/{esquema}/{srid}'],
                ['label' => 'Georreferenciar esquema', 'path' => '/setup/geo/{esquema}'],
                ['label' => 'Georreferenciar (forzado)', 'path' => '/setup/geo/{esquema}/{n}'],
                ['label' => 'Georreferenciar (forzado, fracción)', 'path' => '/setup/geo/{esquema}/{n}/{frac}'],
                ['label' => 'Georreferenciar (forzado, fracción, radio)', 'path' => '/setup/geo/{esquema}/{n}/{frac}/{radio}'],
                ['label' => 'Georreferenciar segmentación', 'path' => '/setup/geoseg/{esquema}'],
                ['label' => 'Cargar SRID de localidades', 'path' => '/setup/update/localidad_srid'],
                ['label' => 'Corregir SRID de localidades', 'path' => '/setup/update/corrige_localidad_srid'],
            ],
            'Segmentación' => [
                ['label' => 'Segmentar esquema', 'path' => '/setup/segmenta/{esquema}'],
                ['label' => 'Muestrear esquema', 'path' => '/setup/muestrea/{esquema}'],
                ['label' => 'Limpiar esquema', 'path' => '/setup/limpia/{esquema}'],
                ['label' => 'Limpiar esquemas temporales', 'path' => '/setup/limpiar/Temporales'],
                ['label' => 'Juntar segmentos', 'path' => '/setup/junta/{esquema}'],
                ['label' => 'Juntar segmentos (fracción, radio)', 'path' => '/setup/junta/{esquema}/{frac}/{radio}'],
                ['label' => 'Juntar segmentos menores', 'path' => '/setup/juntaMenores/{esquema}/{frac}/{radio}/{n}'],
            ],
            'Consolidación (public.*)' => [
                ['label' => 'Juntar listados segmentados', 'path' => '/setup/update/LS'],
                ['label' => 'Juntar R3', 'path' => '/setup/update/R3'],
                ['label' => 'Juntar Manzanas', 'path' => '/setup/update/Manzanas'],
                ['label' => 'Juntar Vías', 'path' => '/setup/update/Vias'],
                ['label' => 'Juntar Localidades', 'path' => '/setup/update/Localidades'],
                ['label' => 'Juntar Cuadras', 'path' => '/setup/update/Cuadras'],
                ['label' => 'Radios de listados', 'path' => '/setup/update/RadiosDeListados'],
                ['label' => 'Radios de arcs', 'path' => '/setup/update/RadiosDeArcs'],
            ],
            'Índices' => [
                ['label' => 'Índices de listado', 'path' => '/setup/index/{esquema}'],
                ['label' => 'Índice de id por tabla', 'path' => '/setup/index/id/{tabla}'],
                ['label' => 'Crear índice (tabla, columnas)', 'path' => '/setup/index/{esquema}/{tabla}/{cols}'],
            ],
            'Permisos / usuarios' => [
                ['label' => 'Dar permisos de esquema', 'path' => '/setup/{esquema}'],
                ['label' => 'Agregar usuario a grupo geoestadística', 'path' => '/setup/grupogeoestadistica/{usuario}'],
                ['label' => 'Permisos de grupo sobre tabla', 'path' => '/setup/grupogeoestadistica/tabla/{tabla}'],
            ],
            'Limpieza de listado' => [
                ['label' => 'Duplicados LSV', 'path' => '/setup/duplicadosLSV/{esquema}'],
                ['label' => 'Actualizar tipo de vivienda', 'path' => '/setup/updateTipoViv/{esquema}'],
            ],

            // Resto de routes/web.php - accesos directos, no son endpoints de acción como /setup/*.
            // Deliberadamente afuera: los bloques "Auto-generated admin routes" (guard admin,
            // namespace Admin\*) - confirmado roto hoy (Admin\UsersController no existe, ver
            // SIGeo-relevamiento-tecnico.md / falla real de `php artisan route:list`).
            'Home / páginas estáticas' => [
                ['label' => 'Welcome', 'path' => '/'],
                ['label' => 'Home', 'path' => '/home'],
                ['label' => 'About', 'path' => '/about'],
                ['label' => 'Projects', 'path' => '/projects'],
                ['label' => 'Contact', 'path' => '/contact'],
                ['label' => 'Server info', 'path' => '/serverinfo'],
                ['label' => 'Guía', 'path' => '/guia'],
                ['label' => 'Sala', 'path' => '/sala'],
                ['label' => 'Gracias', 'path' => '/gracias'],
                ['label' => 'Colaboradores', 'path' => '/colaboradores'],
                ['label' => 'Inicio', 'path' => '/inicio'],
            ],
            'Provincias' => [
                ['label' => 'Listado (provs-list)', 'path' => '/provs-list'],
                ['label' => 'Índice', 'path' => '/provs'],
                ['label' => 'Ver una', 'path' => '/prov/{provincia}'],
            ],
            'Departamentos' => [
                ['label' => 'Por provincia', 'path' => '/prov/deptos/{provincia}'],
                ['label' => 'Listar (json)', 'path' => '/prov/list/{provincia}'],
                ['label' => 'Ver uno', 'path' => '/depto/{departamento}'],
            ],
            'Localidades' => [
                ['label' => 'Listado (locas-list)', 'path' => '/locas-list'],
                ['label' => 'Índice', 'path' => '/localidades'],
                ['label' => 'Índice (json)', 'path' => '/localidades_json'],
                ['label' => 'Ver una', 'path' => '/localidad/{localidad}'],
                ['label' => 'Ver por código', 'path' => '/localidad/codigo/{codigo}'],
                ['label' => 'PxSeg', 'path' => '/localidad/{localidad}/pxseg'],
                ['label' => 'Ver segmentación', 'path' => '/localidad/{localidad}/segmentacion'],
                ['label' => 'Ver segmentación (lados)', 'path' => '/localidad/{localidad}/segmentacion-lados'],
                ['label' => 'Ver gráfico', 'path' => '/localidad/{localidad}/grafico'],
                ['label' => 'Ejecutar segmentación', 'path' => '/localidad-segmenta/{localidad}'],
            ],
            'Aglomerados' => [
                ['label' => 'Listado (aglos-list)', 'path' => '/aglos-list'],
                ['label' => 'Índice', 'path' => '/aglos'],
                ['label' => 'Ver uno', 'path' => '/aglo/{aglomerado}'],
                ['label' => 'Ejecutar segmentación', 'path' => '/aglo-segmenta/{aglomerado}'],
                ['label' => 'PxSeg', 'path' => '/aglo/{aglomerado}/pxseg'],
                ['label' => 'Ver segmentación', 'path' => '/ver-segmentacion/{aglomerado}'],
                ['label' => 'Ver segmentación (lados)', 'path' => '/ver-segmentacion-lados/{aglomerado}'],
                ['label' => 'Ver gráfico', 'path' => '/ver-segmentacion/grafico/{aglomerado}'],
                ['label' => 'Ver gráfico resumen', 'path' => '/ver-segmentacion/grafico-resumen/{aglomerado}'],
                ['label' => 'Ver gráfico resumen (lados)', 'path' => '/ver-segmentacion-lados/grafico-resumen/{aglomerado}'],
            ],
            'Radios / grafos' => [
                ['label' => 'Radios de localidad+depto (CABA)', 'path' => '/radios/{localidad}/{departamento}'],
                ['label' => 'Ver radio', 'path' => '/radio/{radio}'],
                ['label' => 'Ver radio por código', 'path' => '/radio/codigo/{codigo}'],
                ['label' => 'Grafo de aglomerado', 'path' => '/grafo/{aglomerado}'],
                ['label' => 'Grafo (legacy, aglo+radio)', 'path' => '/grafo/{aglomerado}/{radio}'],
                ['label' => 'Ver grafo (localidad+radio)', 'path' => '/radio/{localidad}/{radio}'],
            ],
            'Archivos' => [
                ['label' => 'Índice', 'path' => '/archivos'],
                ['label' => 'Ver uno', 'path' => '/archivo/{archivo}'],
                ['label' => 'Descargar', 'path' => '/archivo/{archivo}/descargar'],
                ['label' => 'Procesar', 'path' => '/archivo/{archivo}/procesar'],
                ['label' => 'Limpiar repetidos', 'path' => '/archivos/limpiar'],
                ['label' => 'Listar repetidos', 'path' => '/archivos/repetidos'],
                ['label' => 'Recalcular checksums', 'path' => '/archivos/recalcular_cs/{archivo_id}'],
                ['label' => 'Checksums obsoletos', 'path' => '/archivos/checksums_obsoletos'],
                ['label' => 'Checksums no calculados', 'path' => '/archivos/checksums_no_calculados'],
            ],
            'Tablero / informes' => [
                ['label' => 'Gráfico provincias', 'path' => '/informe/prov'],
                ['label' => 'Gráfico avances', 'path' => '/informe/avances'],
                ['label' => 'Gráfico avance', 'path' => '/informe/avance'],
            ],
            'Usuarios / roles / filtros / perfil' => [
                ['label' => 'Mi perfil', 'path' => '/perfil'],
                ['label' => 'Listar usuarios', 'path' => '/users'],
                ['label' => 'Roles de usuario', 'path' => '/users/{user}/roles'],
                ['label' => 'Permisos de usuario', 'path' => '/users/{user}/permission'],
                ['label' => 'Filtro de usuario', 'path' => '/users/{user}/filter'],
                ['label' => 'Listar filtros', 'path' => '/filtros'],
                ['label' => 'Renombrar filtro', 'path' => '/filtros/{filter}/rename'],
                ['label' => 'Nuevo filtro', 'path' => '/filtros/new'],
                ['label' => 'Editar filtros de provincias', 'path' => '/filtros/provs/edit'],
                ['label' => 'Listar filtros de provincias', 'path' => '/filtros/provs'],
                ['label' => 'Listar roles', 'path' => '/roles'],
                ['label' => 'Editar rol', 'path' => '/roles/{role}/edit'],
                ['label' => 'Nuevo rol', 'path' => '/roles/new'],
                ['label' => 'Detalle de rol', 'path' => '/roles/{role}/detail'],
            ],
            'Segmentador / segmentos / listado' => [
                ['label' => 'Segmentador', 'path' => '/segmentador'],
                ['label' => 'Listado (índice)', 'path' => '/listado'],
                ['label' => 'Segmentos (índice)', 'path' => '/segmentos'],
            ],
            'Utilidades' => [
                ['label' => 'CSV file', 'path' => '/csv_file'],
                ['label' => 'CSV export', 'path' => '/csv_file/export'],
                ['label' => 'Buscar provincia', 'path' => '/search_provincia'],
                ['label' => 'Autocomplete provincia', 'path' => '/autocomplete_provincia'],
                ['label' => 'Limpiar caché', 'path' => '/clear-cache'],
                ['label' => 'Logout', 'path' => '/logout'],
            ],
        ];

        return view('setup.panel', compact('grupos'));
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {

        MyDB::addUser('mretamozo');
        MyDB::addUser('halperin');
        MyDB::addUser('vheredia');
        MyDB::addUser('mretamozo','geoestadistica_test');


        MyDB::addUser('adibiase');
        MyDB::addUser('alitichever');
        MyDB::addUser('atoca');
        MyDB::addUser('efilgueira');
        MyDB::addUser('cdiaz');
        MyDB::addUser('fhaddad');
        MyDB::addUser('sfarace');
        MyDB::addUser('malves');
        MyDB::addUser('nallendes');
        MyDB::addUser('pdelsere');
        MyDB::addUser('sbouzas');
        MyDB::addUser('sfailde');
        MyDB::darPermisos('e0002');

        return view('home');
    }


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function permisos($schema)
    {
        MyDB::darPermisos($schema);
        return view('home');
    }

    /**
     * Setear SRID y regenerar topología para el esquema.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function setSRIDSchema($schema,$srid)
    {
        MyDB::setSRID($schema,$srid);
        self::cargarTopologia($schema);
        self::georeferenciarEsquema($schema);
        return view('home');
    }

    public function grupoGeoestadistica($usuario)
    {
        MyDB::addUser($usuario);
        flash('Se agrego al grupo geoestadistica al usuario '.$usuario);
        return view('home');
    }

    public function grupoGeoestadisticaTabla($tabla)
    {
        MyDB::darPermisosTabla($tabla);
        return view('home');
    }

    public function cargarTopologia($schema, $tolerancia = null)
    {
        if (is_numeric($tolerancia)) {
          MyDB::cargarTopologia($schema, $tolerancia);
        } else {
          MyDB::cargarTopologia($schema);
        }
        flash('Se creo la topología para '.$schema);
        return view('home');
    }

    public function dropTopologia($schema)
    {
        MyDB::dropTopologia($schema);
        return view('home');
    }

    public function addIndexListado($schema)
    {
        MyDB::addIndexListado($schema);
        flash('Se creo el indice para lados en listado en '.$schema);
        MyDB::addIndexListadoId($schema);
        flash('Se creo el indice para id listado en '.$schema);
        MyDB::addIndexListadoRadio($schema);
        flash('Se creo el indice para radio en listado en '.$schema);
        return view('home');
    }

    public function addIndexId($tabla)
    {
        MyDB::addIndexId($tabla);
        flash('Se creo el indice para id en '.$tabla);
        return view('home');
    }

    public function createIndex($schema,$tabla,$cols)
    {
        MyDB::createIndex($schema,$tabla,$cols);
        flash('Se creo el indice para '.$tabla.' del esquema '.$schema.' para las columnas '.$cols);
        return view('home');
    }

    public function georeferenciarEsquema($schema, $n=8, $frac=null, $radio=null)
    {
        flash('Georeferenciando listado del esquema '.$schema.' Fracción:'.$frac.' Radio: '.$radio.' N:'.$n)->info()->important();
        if (is_numeric($n)) {
            $desp = $n;
            MyDB::georeferenciar_listado($schema, $desp, $frac, $radio);
        } else {
            MyDB::georeferenciar_listado($schema, 7, $frac, $radio);
        }
        return view('home');
    }

    public function georeferenciarSegmentacionEsquema($schema)
    {
        // Georreferrenciar segmentación...
        MyDB::georeferenciar_segmentacion($schema);
        flash('Se georeferencio el listado del esquema '.$schema);
        return view('home');
    }

    public function segmentarEsquema($schema)
    {
        MyDB::segmentar_equilibrado($schema,36);
        flash('Se segmento el listado del esquema '.$schema);
        return view('home');
    }

    public function generarAdyacenciasEsquema($schema)
    {
        $cant = MyDB::generarAdyacencias($schema);
        flash('Se generaron '.$cant.' adyacencias para el esquema '.$schema);
        return view('home');
    }

    public function limpiarEsquema($schema)
    {
        MyDB::limpiar_esquema($schema);
        flash('Limpieza de esquema '.$schema);
       	return view('home');
    }
    
    public function juntarSegmentos($schema)
    {
        flash('Resultado: '.MyDB::juntar_segmentos($schema));
        flash('Se juntaron los segmentos con 0 viviendas del esquema '.$schema);
        flash('Sincro R3: '.MyDB::grabarSegmentacion(substr($schema,1,strlen($schema)-1)));
        return view('home');
    }

    /**
     * Show the index application dashboard.
     * Junta Segmentos con menos de $n cantidad de viviendas
     * en el $schema, para el $frac, $radio
     *
     * @schema text
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function juntarSegmentosMenores($schema, $frac, $radio, $n)
    {
      
        for ($m=$n;$n>0;$n--) {
            $result = MyDB::juntar_segmentos_con_menos_de($schema, $frac, $radio, $m-$n);
            flash('Juntado para '.($m-$n).': '.$result);
        }
        flash('Sincro R3: '.MyDB::grabarSegmentacion(substr($schema,1,strlen($schema)-1),$frac,$radio));
        return view('home');
    }


    public function muestreaEsquema($schema)
    {
        $result= MyDB::muestrear($schema);
        flash('Se muestreo el esquema '.$schema.' 
        '.$result);
        return view('home');
    }

    public function limpiaEsquemasTemporales()
    {
        $result= MyDB::limpiaEsquemasTemporales();
        return view('home');
    }


    public function tipoVivdeDescripcion($schema){
        MyDB::UpdateTipoVivDescripcion($schema,true);
    }

    public function limpiaListado($schema)
    {
        MyDB::eliminaRepetidosListado($schema);
        MyDB::eliminaLSVconViviendasEnListado($schema);
        MyDB::sincroSegmentacion($schema);
        flash('Sincro R3: '.MyDB::grabarSegmentacion(substr($schema,1,strlen($schema)-1)));
        return view('home');
    }

    public function juntaR3()
    {
        flash('Resultado: '.MyDB::juntaR3());
        return view('home');
    }

    public function juntaManzanas()
    {
        flash('Resultado: '.MyDB::juntaManzanas());
        return view('home');
    }

    public function juntaVias()
    {
        flash('Resultado: '.MyDB::juntaVias());
        return view('home');
    }
    
    public function juntaCuadras()
    {
        flash('Resultado: '.MyDB::juntaCuadras());
        return view('home');
    }

    public function juntaLocalidades()
    {
        flash('Resultado: '.MyDB::juntaLocalidades());
        return view('home');
    }

    // Carga todos los esquemas en topo_pais
    // function   indec.cargarTopologiaPais
    public function cargarTopologiasPais()
    {
        flash('Resultado: '.MyDB::cargarToposPais());
        return view('home');
    }

    public function cargaSrids()
    {
        flash('Resultado: '.MyDB::cargaSrids());
        return view('home');
    }

    public function corrigeSrids()
    {
        flash('Resultado: '.MyDB::corrigeSrids());
        return view('home');
    }

    public function radiosDeListados()
    {
        flash('Resultado: '.MyDB::radiosDeListados());
        return view('home');
    }

    public function radiosDeArcs()
    {
        flash('Resultado: '.MyDB::radiosDeArcs());
        return view('home');
    }

    public function juntaListadosSegmentados()
    {
        flash('Resultado: '.MyDB::juntaListadossegmentados());
        return view('home');
    }

    public function testFlash($texto='Mensaje de prueba.')
    {
        flash(' Normal  '.$texto);
        flash(' Error  '.$texto)->error();
        flash(' Info  '.$texto)->info();
        flash(' Success  '.$texto)->success();
        flash('Message success')->success();// Set the flash theme to "success".
        flash('Message error')->error();// Set the flash theme to "danger".
        flash('Message warning')->warning();// Set the flash theme to "warning".
        flash('Proyecto Mandarina Test Overlay flash message')->overlay();// Render the message as an overlay.
        flash()->overlay('Modal Message Mandarina', 'Mandarina Modal Title');// Display a modal overlay with a title.
        flash('Message important')->important();//: Add a close button to the flash message.
       	flash('Message error()->important')->error()->important();//       
        flash(' Info Importante '.$texto)->info()->important();
        flash(' Success imporatnte  '.$texto)->success()->important();
        

        Log::emergency($texto);
        Log::alert($texto);
        Log::critical($texto);
        Log::error($texto);
        Log::warning($texto);
        Log::notice($texto);
        Log::info($texto);
        Log::debug($texto);       	
        return view('home');
    }
    
}
