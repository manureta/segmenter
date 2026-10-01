<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Model\Radio;
use App\Model\Provincia;
use Symfony\Component\Process\Process;
use Auth;
use Illuminate\Database\QueryException;

class MyDB extends Model
{

    // Muestrea el esquema
    //
    public static function resumenProvincial(Provincia $oProv)
    {
      if( isset($oProv) ){
        try{
            $result = DB::select("select prov,dpto,d.nombre,codloc,l.nombre,
                                     count(distinct frac::text||'-'||radio::text) radios_m_u ,
                                     count(*) segmentos,
                                     sum(viviendas) vivs,
                                     round(1.0*sum(viviendas)/count(*),2) prom
                                     from r3 join departamentos d on
                                        d.codigo=lpad(prov::text,2,'0')||lpad(dpto::text,3,'0')
                                     join localidad l on
                                       l.codigo=lpad(prov::text,2,'0')||lpad(dpto::text,3,'0')||lpad(codloc::text,3,'0')
                                     join radio r on
                                       r.codigo=lpad(prov::text,2,'0')||lpad(dpto::text,3,'0')||lpad(frac::text,2,'0')||lpad(radio::text,2,'0')
                                     WHERE r.tipo_de_radio_id in (1,3) and prov='".$oProv->codigo."' and seg!='90' group by 1,2,3,4,5 ;");
        }catch(QueryException $e){
                $result=null;
                Log::error('No se pudo generar resuemn de la provincia ',[$oProv],$e);
            }
            Log::debug('Se consulto resumen de provincia '.$oProv->codigo);
            return $result;
       }else{
         return 'no se seleccionó Provincia';
       }
    }

    // Muestrea el esquema
    //
    public static function muestrear($esquema)
    {
        try{
            DB::beginTransaction();
            DB::statement(" SELECT indec.muestrear('".$esquema."');");
            $result = DB::statement(" SELECT * from indec.describe_despues_de_muestreo('".$esquema."');");
            DB::commit();
        }catch(QueryException $e){
            DB::Rollback();
                $result=null;
                Log::error('No se pudo muestrar el esquema '.$esquema.' Error:'.$e);
            }
            Log::debug('Se muestreo el esquema '.$esquema.' !');
            return $result;
    }

        // Segmenta a listado los lados excedidos segun umbral
        //
        public static function
        segmentar_excedidos_ffrr($esquema,$frac,$radio,$umbral=20,$deseado=20)
        {
            try{
                Log::info('Resegmentando segmentos excedidos de fraccion
                '.$frac.', radio '.$radio);
                return DB::statement(" SELECT indec.segmentar_excedidos_ffrr(
                'e".$esquema."',".$frac.",".$radio.",".$umbral.",".$deseado.");");
            }catch(QueryException $e){
                Log::warning('No se pudo segmentar segmentos excedidos, reintentando...');
                self::cambiarSegmentarBigInt($esquema);
                self::recrea_vista_segmentos_lados_completos($esquema);
                try{
                    return DB::statement(" SELECT indec.segmentar_excedidos_ffrr(
                    'e".$esquema."',".$frac.",".$radio.",".$umbral.",".$deseado.");");
                }catch(QueryException $e){
                    Log::error('No se pudo segmentar segmentos excedidos'.$e);
                    return 0;
                }
            }
            Log::debug('Se resegmentaron los segmentos excedidos!');
        }

        // Propaga la segmentacion a partir de lados completos hacia la tabla de
        // segmentacion.
        public static function
        lados_completos_a_tabla_segmentacion_ffrr($esquema,$frac,$radio)
        {
            try{
                DB::statement("SELECT
                indec.lados_completos_a_tabla_segmentacion_ffrr('e".$esquema."',".$frac.",".$radio.");");
            }catch(QueryException $e){
                self::generarSegmentacionVacia($esquema);
                self::generarR3Vacia($esquema);
                self::addSequenceSegmentos('e'.$esquema);
                Log::warning('Create sequence xq no exisitia...');
                self::recrea_vista_segmentos_lados_completos($esquema);
                DB::statement("SELECT
                indec.lados_completos_a_tabla_segmentacion_ffrr('e".$esquema."',".$frac.",".$radio.");");

            }
            Log::debug('Propagando segmentacion lados completos de tabla arc a
            tabla segmentacion -> '.$esquema);
        }

        // crea o reemplaza la vista de segmentos generados por lados completos.
        public static function recrea_vista_segmentos_lados_completos($esquema)
        {
            DB::statement("SELECT
            indec.v_segmentos_lados_completos('e".$esquema."');");
            Log::debug('Creando vista manzana lado numero segmento en radio, cant
            viviendas -> '.$esquema);
        }

        // Obtengo segmentos excedidos.
        public static function segmentos_excedidos($esquema,$vivs,Radio $radio=null)
        {
                if ($radio){
                    $filtro=" ppdddcccffrr like
                    '".substr($radio->codigo,0,5)."___".substr($radio->codigo,-4)."'";
                    Log::debug('Filtro excedidos del radio: '.$radio->codigo.'
                                aplicando '.$filtro);
                    try{
                       $result = DB::select("SELECT * FROM ".$esquema.".v_segmentos_lados_completos
                        WHERE vivs > ".$vivs." and ".$filtro.";");
                    }catch(QueryException $e){
                      Log::error('ERROR Buscando segmentos excedidos del esquema-> '.$esquema.' con el filtro '.$filtro.$e);
                      $result=[];
                    }
                 }
                 else{
                    try{
                     $result = DB::select("SELECT * FROM ".$esquema.".v_segmentos_lados_completos
                           WHERE vivs > ".$vivs.";");
                    }catch(QueryException $e){
                      Log::error('ERROR Buscando segmentos excedidos del esquema-> '.$esquema.' sin filtro '.$e);
                      $result=[];
                    }
                }
            return $result;
        }

        // Consulta cantidad de segmentos con 0 vivendas o menos de x.
        public static function cantidad_segmentos($esquema,$viviendas=0,$frac=null,$radio=null)
        {
            if ( ($frac!=null) and ($radio!=null) ){
              $filtro = ' where (frac::integer,radio::integer)=('.$frac.','.$radio.') ';
            } else {
              $filtro = '';
            }
            try {
              $result = (int) DB::select('
                          SELECT count(*) cant_segmentos FROM (
                            select segmento_id, count(indec.contar_vivienda(tipoviv)) as vivs
                            from "' . $esquema . '".listado
                            join "' . $esquema . '".segmentacion
                            on listado.id = segmentacion.listado_id
                            '.$filtro.'
                            group by segmento_id
                            having count(indec.contar_vivienda(tipoviv)) <= '.$viviendas.
                                        ') foo;')[0]->cant_segmentos;
              return $result;
            } catch(QueryException $e) {
              Log::error('ERROR Contando segmentos del esquema-> '.$esquema.$e);
              return -1;
            }
        }

        // Junta los segmentos con 0 viviendas al segmento menor cercano.
        public static function juntar_segmentos($esquema,$frac=null,$radio=null)
        {
            $_cant_segmentos_en_cero_antes = 0;
            $_cant_segmentos_en_cero = self::cantidad_segmentos($esquema,0);
            $result= 'Nada';
            while ( $_cant_segmentos_en_cero>0 and $_cant_segmentos_en_cero!=$_cant_segmentos_en_cero_antes){
              $_cant_segmentos_en_cero_antes = $_cant_segmentos_en_cero;
              try{
                if (($frac==null) and ($radio==null)){
                  $result = DB::statement("SELECT indec.juntar_segmentos('".$esquema."')");
                  $_cant_segmentos_en_cero = self::cantidad_segmentos($esquema,0);
                }else{
                  $result = DB::statement("SELECT indec.juntar_segmentos_ffrr('".$esquema."',".$frac.",".$radio.")");
                  $_cant_segmentos_en_cero = self::cantidad_segmentos($esquema,0);
                }
                Log::info('Juntando segmentos con 0 viviendas del esquema-> '.$esquema.' Había: '.$_cant_segmentos_en_cero);
              }catch(QueryException $e){
                Log::error('ERROR Juntando segmentos del esquema-> '.$esquema);
                return false;
              }
            }
            flash('Se termino de juntar todos los segmentos en 0 que se pudo. Quedaron: '.$_cant_segmentos_en_cero)->success();
            return $result;

        }

        // Junta los segmentos con menos de $n viviendas al segmento menor cercano.
        // En el esquema $esquema para el Radio: $frac,$radio
        public static function juntar_segmentos_con_menos_de($esquema,$frac,$radio,$n)
        {
            $_cant_segmentos_antes = 0;
            $_cant_segmentos = self::cantidad_segmentos($esquema,$n,$frac,$radio);
            $result= 'Nada';
            while ( $_cant_segmentos>0 and $_cant_segmentos!=$_cant_segmentos_antes){
              $_cant_segmentos_antes = $_cant_segmentos;
              try{
                $result = DB::statement("SELECT indec.juntar_segmentos_con_menos_de_ffrr('".$esquema."',".$frac.",".$radio.",".$n.")");
                Log::info('Juntando segmentos con menos de '.$n.' viviendas del esquema-> '
                            .$esquema.' F: '.$frac.' R: '.$radio.' Había: '.$_cant_segmentos.' Result:'.$result);
              }catch(QueryException $e){
                Log::error('ERROR Juntando segmentos de menos de '.$n.' viviendas del esquema-> '.$esquema);
                flash('Error juntando segmentos. Quedaron: '.$_cant_segmentos_en_antes)->error();
                return false;
              }
              $_cant_segmentos = self::cantidad_segmentos($esquema,$n,$frac,$radio);
            }
            flash('Se termino de juntar todos los segmentos que se pudieron. Quedaron: '.$_cant_segmentos)->success();
            return $result;

        }


        //Crea el esquema si no existe y asigna los permisos.
        public static function createSchema($esquema)
        {
            if (!DB::select('SELECT 1 from information_schema.schemata where schema_name = ?',['e'.$esquema])){
              DB::statement('CREATE SCHEMA IF NOT EXISTS "e'.$esquema.'"');
              Log::info('Creando esquema-> e'.$esquema);
              self::darPermisos('e'.$esquema);
              return true;
            }else{
              Log::debug('Encontrado esquema-> e'.$esquema);
              return true;
            }
            return false;
        }

        // Borra los esquemas temporales donde fallaron carga de arcos y etiquetas comenzados en e_
        // * debo escapar el _ que es comodin de caracter en sql.
        public static function limpiaEsquemasTemporales()
        {
            if ($esquemas_temporales=DB::select("SELECT schema_name from information_schema.schemata where schema_name like 'e\_%'")){
                foreach ($esquemas_temporales as $esquema){
                  DB::statement("DROP SCHEMA \"".$esquema->schema_name."\" CASCADE;"); //,[$esquema->schema_name]);
                }
                flash('Se borraron '.count($esquemas_temporales).' equemas temporales');
               return true;
            }
            flash('No se encontraron esquemas temporales');
            return false;
        }

        //Dar permisos a una tabla.
        public static function darPermisosTabla($tabla,$rol='geoestadistica')
        {
            try{
                DB::statement("GRANT SELECT ON TABLE  ".$tabla." TO ".$rol);
            }catch(QueryException $e){
                Log::error('No se pudo dar permiso a '.$rol.' sobre '.$tabla.'.'.$e);
          return false;
            }
      Log::info('Se dió permiso a '.$rol.' sobre '.$tabla.'.');
      return true;
  }

        //Develve data del DBF subido.
        public static function infoDBF($tabla,$esquema)
        {
            return json_encode(DB::select('
                            SELECT prov,dpto,nom_loc,codaglo, codloc, nom_loc, codent,nom_ent,count(*) registros,
                            count(distinct frac||radio) as radios,
                            count(indec.contar_vivienda(tipoviv)) as viviendas
                        --,count(*) vivs
                        ,count(distinct prov||dpto||codloc||frac||radio||mza) as mzas
                        --,array_agg(distinct prov||dpto||codloc||frac||radio||mza||lado),count(distinct lado) as lados
FROM
                        e'.$esquema.'.'.$tabla.'
                        GROUP BY 1,2,3,4,5,6,7,8;'));
    }

    public static function getAglo($tabla,$esquema,$filtro='')
    {
        return (DB::select('SELECT distinct codaglo FROM
        '.$esquema.'.'.$tabla.$filtro.';')[0]->codaglo);
    }

    public static function getDataAglo($tabla,$esquema,$codloc)
    {
      if(isset($codloc)){
          log::info('Buscando aglomerado para la localidad : '.$codloc);
                $filtro=" WHERE codprov||coddepto||codloc= '".$codloc."'";
      }else{$filtro='';}
      try {
           return (DB::select('SELECT codaglo as codigo, nombre FROM
                         '.$esquema.'.'.$tabla.
                         $filtro.
                         ' group by 1,2 order by count(*) desc Limit 1;')[0]);
        }catch (\Illuminate\Database\QueryException $exception) {
            Log::notice('Aglomerado Sin Nombre: '.$filtro);
            //Supongo sin Nombre
            $codaglo=self::getAglo($tabla,$esquema,$filtro);
            return (object) ['codigo'=>$codaglo,'nombre'=>'Sin Nombre'];
        }
    }

    public static function getProv($tabla,$esquema)
    {
        try {
            return (DB::select('SELECT prov as link FROM
            "'.$esquema.'".'.$tabla.' Limit 1;')[0]->link);
        }catch (\Illuminate\Database\QueryException $exception) {
                Log::error('Error: '.$exception);
                //Supongo codprov sin Nombre
                return null;
        }
    }

    public static function getDataProv($tabla,$esquema)
    {
        try {
            return (DB::select('SELECT codprov as codigo,nomprov as nombre FROM
            '.$esquema.'.'.$tabla.' group by 1,2 order by count(*) desc Limit 1;')[0]);
        }catch (\Illuminate\Database\QueryException $exception) {
    Log::error('Error: '.$exception);
    //Supongo codprov sin Nombre
    $codprov=self::getCodProv($tabla,$esquema);
      return ['codigo'=>$codprov,'nombre'=>'Sin Nombre'];
  }
    }

    public static function getCodProv($tabla,$esquema)
    {
        try {
            return (DB::select('SELECT codprov as link FROM
            '.$esquema.'.'.$tabla.' group by 1 order by count(*) Limit 1;')[0]->link);
        }catch (\Illuminate\Database\QueryException $exception) {
      Log::error('Error: '.$exception);
      return '0';
  }
    }

    public static function getDataDepto($tabla,$esquema)
    {
        try {
            return (DB::select('SELECT codprov||coddepto as codigo,nomdepto as nombre FROM
            '.$esquema.'.'.$tabla.' group by 1,2 order by codprov||coddepto asc,count(*) desc ;'));
        }catch (\Illuminate\Database\QueryException $exception) {
            if (Schema::hasColumn($esquema.'.'.$tabla , 'coddepto')){
                DB::unprepared('ALTER TABLE '.$esquema.'.'.$tabla.' RENAME coddepto23 TO coddepto');
                Log::warning('Cuidado!! Se utilizó coddepto23 cono coddepto : '.$exception);
                return (DB::select('SELECT codprov||coddepto as codigo,nomdepto as nombre FROM
              '.$esquema.'.'.$tabla.' group by 1,2 order by codprov||coddepto asc,count(*) desc ;'));
      }
      Log::error('Error: '.$exception);
      // Loguea error y devuelve array nulo
      return [];
      }
    }

    public static function getDataFrac($tabla,$esquema,$codigo_dpto=null)
    {
      if(isset($codigo_dpto)){
          log::debug(' Fracciones del departamento: '.$codigo_dpto);
          $filtro=" WHERE codprov||coddepto= '".$codigo_dpto."'";
      }else{$filtro='';}
        try {
          return (DB::select('SELECT codprov||coddepto||frac2020 as codigo,
                       codprov||coddepto||codloc||frac2020 as nombre FROM
                     '.$esquema.'.'.$tabla.' '.$filtro.' group by 1,2 order by codprov||coddepto||codloc||frac2020 asc, count(*) desc ;'));
        }catch (\Illuminate\Database\QueryException $exception) {
          Log::error('Error: '.$exception);
          //
          return [];
        }
    }

    public static function checkPxRad($tabla,$esquema,$codigo_loc=null)
    {
        $ok = true;
        $filtro = null;
        $result = [];
        try {
            // Consulta por códigos de radio con diferente tipo.
            $result = (DB::select(
                'SELECT codprov||coddepto||frac2020||radio2020 as codigo,
                    string_agg(distinct tiporad20||\' en \'||codloc,\',\') inconsistencia
                FROM
                '.$esquema.'."'.$tabla.'" '.$filtro.' group by 1 HAVING count(distinct tiporad20)>1 '.
                'order by codprov||coddepto||frac2020||radio2020 asc, count(*) desc ;'
                )
            );

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Error en consulta para validar pxrad: '.$e->getMessage());
        }
        if (count($result) > 0) {
            $ok = false;
            throw new Exceptions\GeoestadisticaException(
                'Más de un tipo distinto para el mismo código de radio. '.
                collect($result)->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                , 1);
        }
        try {
            // Consulta por códigos de radio en más de una localidad que no es mixto.
            $result = (DB::select(
                'SELECT codprov||coddepto||frac2020||radio2020 as codigo,
                    string_agg(distinct \' en \'||codloc,\',\') inconsistencia
                FROM
                '.$esquema.'."'.$tabla.'" '.
                'where upper(tiporad20) != \'M\')'.
                'group by 1 HAVING count(distinct codloc)>1'.
                'order by codprov||coddepto||frac2020||radio2020 asc, count(*) desc ;'
                )
            );

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Error en consulta para validar pxrad: '.$e->getMessage());
        }
        if (count($result) > 0) {
            $ok = false;
            throw new Exceptions\GeoestadisticaException(
                'Más de una localidad en un radio que no es mixto. '.
                collect($result)->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                , 2);
        }
        if ($ok) Log::info('Pxrad ok: '.$tabla);
        return $ok;
    }

    public static function getDataRadio($tabla,$esquema,$codigo_loc=null)
    {
        log::debug(' Radios de la Localidad: '.$codigo_loc);
        if (isset($codigo_loc)) {
           $filtro=" WHERE codprov||coddepto||codloc= '".$codigo_loc."'";
        } else {
           $filtro='';
        }
        try {
           $result = (DB::select('SELECT codprov||coddepto||frac2020||radio2020 as codigo,
                \'x \'||codloc as nombre, upper(tiporad20) as tipo FROM
                '.$esquema.'.'.$tabla.' '.$filtro.' group by 1,2,3 order by codprov||coddepto||frac2020||radio2020 asc, count(*) desc ;'));
        } catch (\Illuminate\Database\QueryException $exception) {
            Log::warning('Malabares : '.$exception);
            flash('Puede que no se haya encontrado el tipo de radio, se asúme todo Urbano')
                ->important()->warning();
            // Se intenta asumiendo que es urbano y falta el tiporad20
            try {
                $result = (DB::select('SELECT codprov||coddepto||frac2020||radio2020 as codigo,
                    codprov||coddepto||codloc||frac2020||radio2020 as nombre,\'U\' as tipo FROM
                    '.$esquema.'.'.$tabla.' '.$filtro.
                    ' group by 1,2,3 order by codprov||coddepto||codloc||frac2020||radio2020 asc, count(*) desc ;'));
                //
      }catch (\Illuminate\Database\QueryException $exception) {
          Log::error('Error : '.$exception);
          return [];
      }
    }
    return $result;
  }

    public static function getDataLoc($tabla,$esquema,$codigo_depto=null)
    {
      log::debug(' Localidades del depto: '.$codigo_depto);
      if (isset($codigo_depto)) {
        $filtro=" WHERE codprov||coddepto= '".$codigo_depto."'";
      } else { $filtro='';
      }
        try {
            return (DB::select('SELECT codprov||coddepto||codloc as codigo,nomloc as nombre FROM
            '.$esquema.'.'.$tabla.' '.$filtro.' group by 1,2 order by codprov||coddepto||codloc asc, count(*) desc ;'));
        }catch (\Illuminate\Database\QueryException $exception) {
          Log::error('Error: '.$exception);
      //Sin data de localidad
      //
      return null;;
  }
    }

    // Devuelve el link de localidad de mayor ocurrencia
    public static function getLoc($tabla,$esquema){
      return self::getLocs($tabla,$esquema)[0]->link;
    }

    // Devuelve link de localidad y cantidad de ocurrencias
    public static function getLocs($tabla,$esquema)
    {
        try {
            return (DB::select('SELECT prov||dpto||codloc as link,count(*) FROM
                    "'.$esquema.'".'.$tabla.' group by prov||dpto||codloc order by count(*);'));
        }catch (QueryException $exception) {
           try {
               return (DB::select('SELECT prov||depto||codloc as link,count(*) FROM
                       "'.$esquema.'".'.$tabla.' group by prov||depto||codloc order by count(*);'));
           }catch (QueryException $exception) {
              try {
                  return (DB::select('SELECT link,count(*) FROM
                          "'.$esquema.'".'.$tabla.' group by link order by count(*);'));
              }catch (QueryException $exception) {
                  try {
                      $result_debug = DB::select('SELECT * FROM
                              "'.$esquema.'".'.$tabla.' limit 1');
                  }catch (QueryException $exception) {
                     Log::error('No se pudo encontrar localidades. ',[$exception]);
                   return [];
                  }
               Log::warning('No se pudo encontrar localidades. ',[$exception,$result_debug]);
               return [];
              }
           }
         }
    }

    // Devuelve link de deptos y cantidad de ocurrencias
    public static function getDptos($tabla,$esquema)
    {
        try {
            return (DB::select('SELECT prov||dpto as link,count(*) FROM
                    "'.$esquema.'".'.$tabla.' group by prov||dpto order by count(*);'));
        }catch (QueryException $exception) {
           try {
               return (DB::select('SELECT prov||depto as link,count(*) FROM
                       "'.$esquema.'".'.$tabla.' group by prov||depto order by count(*);'));
           }catch (QueryException $exception) {
               Log::error('No se pudo encontrar departamentos: '.$exception);
               return [];
           }
         }
    }


    // Mueve de esquema temporal a otro
    public static function moverEsquema($de_esquema,$a_esquema)
    {
    try {
      return (DB::unprepared('ALTER SCHEMA  "'.$de_esquema.'" RENAME TO "'.$a_esquema.'"'));
    }catch (QueryException $exception) {
    if ($exception->getCode() == '42P06'){
      Log::debug('Ya existe el Esquema. Intento mover tablas ARC y LAB');
      try{
          DB::beginTransaction();
          (DB::unprepared('ALTER TABLE  "'.$de_esquema.'".arc SET SCHEMA "'.$a_esquema.'" '));
          DB::commit();
      }catch (QueryException $exception) {
           if ($exception->getCode() == '42P07'){
             Log::warning('Ya hay tablas cargadas, NO se pisarán los datos! ');
             DB::Rollback();
             try{
//                    DB::beginTransaction();
//                    DB::unprepared('DROP TABLE IF EXISTS '.$a_esquema.'.arc CASCADE');
//                    DB::unprepared('ALTER TABLE  "'.$de_esquema.'".arc SET SCHEMA "'.$a_esquema.'" ');
//                    DB::commit();

                  Log::info('NO Se movio tabla ARC a '.$a_esquema.' de  '.$de_esquema);
              }catch (QueryException $exception) {
                Log::error('Error: '.$exception);
                DB::Rollback();
              }
          }
      }
      try{
          DB::beginTransaction();
          (DB::unprepared('ALTER TABLE  "'.$de_esquema.'".lab SET SCHEMA "'.$a_esquema.'" '));
          DB::commit();
      }catch (QueryException $exception) {
           if ($exception->getCode() == '42P07'){
             Log::warning('Ya hay tablas cargadas, NO se pisarán los datos! ');
             DB::Rollback();
             try{
//                    DB::beginTransaction();
//                    DB::unprepared('DROP TABLE IF EXISTS '.$a_esquema.'.lab CASCADE');
//                    DB::unprepared('ALTER TABLE  "'.$de_esquema.'".lab SET SCHEMA "'.$a_esquema.'" ');
//                    DB::commit();
                  Log::info('NO Se movió la tabla LAB a '.$a_esquema.' y no se borro el esquema '.$de_esquema);
              }catch (QueryException $exception) {
                Log::error('Error: '.$exception);
                DB::Rollback();
              }
          }
      }

    }else{
              Log::error('Error: '.$exception);
              return false;
    }
   }
   return true;
  }

    // Copia de esquema temporal a otro
    //
    public static function copiaraEsquema($de_esquema,$a_esquema,$localidad_codigo=null)
    {
        if (isset($localidad_codigo)) {
                 $filtro=" WHERE substr(a.mzai,1,8)= '".$localidad_codigo."' or substr(a.mzad,1,8)= '".$localidad_codigo."' ";
                 $filtro_lab=" WHERE a.prov || a.depto || a.codloc = '".$localidad_codigo."'";
        } else { $filtro='';
                 $filtro_lab=''; }
        try {
             DB::beginTransaction();
             DB::unprepared('CREATE TABLE "'.$a_esquema.'".arc AS SELECT * FROM "'.$de_esquema.'".arc a '.$filtro);
             DB::commit();
         }catch (QueryException $e) {
             DB::Rollback();
             if ($e->getCode() == '42P07'){
               // Tabla duplicada
               self::comparaEsquema($de_esquema,$a_esquema,'arc',$filtro);
             } else {
               //DB::unprepared('DROP TABLE IF EXISTS '.$a_esquema.'.arc CASCADE');
               Log::error('Error: '.$e);
             }
        }
        try {
             DB::beginTransaction();
             DB::unprepared('CREATE TABLE "'.$a_esquema.'".lab AS SELECT * FROM "'.$de_esquema.'".lab a '.$filtro_lab);
             DB::commit();
         }catch (QueryException $e) {
             DB::Rollback();
             if ($e->getCode() == '42P07'){
               // Tabla duplicada
               self::comparaEsquema($de_esquema,$a_esquema,'lab',$filtro_lab);
             } else {
               //DB::unprepared('DROP TABLE IF EXISTS '.$a_esquema.'.lab CASCADE');
               Log::error('Error: '.$e);
             }
        }
    }

    // Compara un esquema con otro
    //
    public static function comparaEsquema($de_esquema,$a_esquema,$tabla,$filtro=null) {
        try {
             $result = DB::select('SELECT count(*) total, count( distinct a.ogc_fid) en_base, count( distinct de.ogc_fid) en_archivo from '.
                       ' "'.$a_esquema.'"."'.$tabla.'" a full join "'.$de_esquema.'"."'.$tabla.'" de using(ogc_fid) '.$filtro);
            flash('Se encontró información ya cargada para '.$tabla.' en '.$a_esquema.' : '.
                  collect($result)->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))->info()->important();
         }catch (QueryException $exception) {
             Log::error('Error: '.$exception);
             return false;
        }
    }

    // Copia de esquema temporal a otro
    //
    public static function copiaraEsquemaPais($de_esquema,$a_esquema,$tipo='arc',$depto_codigo=null,$prov_codigo=null)
    {
        if (isset($depto_codigo)) {
                  //JOIN CON TABLA LAB SEGUN FACE_ID =?
                 $filtro=" WHERE prov || depto = '".$depto_codigo."' ";
        } else {
            if (isset($prov_codigo)) {
                //JOIN CON TABLA LAB SEGUN FACE_ID =?
               $filtro=" WHERE prov  = '".$prov_codigo."' ";
            } else { $filtro=''; }
        }
         try {
             DB::beginTransaction();
             DB::unprepared('CREATE TABLE "'.$a_esquema.'".'.$tipo.' AS SELECT * FROM "'.$de_esquema.'".'.$tipo.' '.$filtro);
             DB::commit();
         }catch (QueryException $exception) {
             DB::Rollback();
             Log::warning('Error: '.$exception);
             flash('Error procesando '.$tipo.' '.$exception->getMessage())->error()->important();
             $error = true;
             //DB::unprepared('DROP TABLE IF EXISTS '.$a_esquema.'.arc CASCADE');
         }

        return true;
    }

    public static function getEntidades($tabla,$esquema,$localidad=null)
    {
  if(isset($localidad)) {
      $filtro=" WHERE codprov||coddepto||codloc= '".$localidad->codigo."'";
  }else{$filtro='';}
        try {
            return (DB::select('SELECT distinct prov||dpto||codloc||codent as codigo, noment as nombre FROM
        '.$esquema.'.'.$tabla.' '.$filtro.' group by 1,2
        order by codprov||coddepto||codloc||codent asc, count(*) desc ;'));
        }catch (\Illuminate\Database\QueryException $exception) {
            Log::error('Error: '.$exception);
      return null;
  }
    }

    public static function procesarPxRad($tabla,$esquema)
    {
        try {
            $resumen = DB::select('SELECT * FROM
                '.$esquema.'."'.$tabla.'" limit 1;');
            Log::debug(
                'Se pudo leer el registro en '.$tabla.' . Ejemplo : '.
                (collect($resumen)->toJson(JSON_UNESCAPED_UNICODE))
            );
            } catch (\Illuminate\Database\QueryException $exception) {
               Log::error('No se cargó correctamente la PxRad: '.$exception);
               flash( $resumen='NO se cargó correctamente la PxRad')->error()->important();
            }
      try {
      $radios = DB::select('SELECT codprov, coddepto, codloc, codent, codaglo,
        frac2001, radio2001,
                    frac2010, radio2010, tiporad10,
                    tiporad20, frac2020, radio2020, tiporad20,
                    nomloc, noment
                   FROM
       '.$esquema.'."'.$tabla.'" ;');
      $resumen = DB::select('SELECT array_agg(distinct codprov) prov,
        array_agg( distinct codprov|| coddepto) depto,
        array_length( array_agg( distinct codprov|| coddepto|| codloc),1) localidades,
        array_length( array_agg( distinct codprov|| coddepto|| frac2020),1) frac2020,
        array_length( array_agg( distinct codprov|| coddepto|| frac2020 || radio2020),1) rad2020 FROM
                   '.$esquema.'."'.$tabla.' ;');

       flash('Resumen de lo cargado: '.collect($resumen)->toJson());
            }catch (\Illuminate\Database\QueryException $exception) {
        Log::error('No se valido correctamente la PxRad: ('.collect($resumen)->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).' )' .$exception);
        flash('No se valido correctamente la PxRad ')->error()->important();
      }
      try{
        $resumen=DB::select('SELECT codprov,coddepto,codloc,count(*) as radios FROM
        '.$esquema.'.'.$tabla.' GROUP BY codprov,coddepto,codloc ;');
            }catch (\Illuminate\Database\QueryException $exception) {
        Log::error('No se cargó correctamente la PxRad: ('.collect($resumen)->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).') ' .$exception);
      flash( $resumen='NO se cargó correctamente la PxRad')->error()->important();
      }
      self::checkPxRad($tabla, $esquema);
    return collect($resumen)->toJson();
    }

//         $tabla = strtolower( substr($file_name,strrpos($file_name,'/')+1,-4) );
    public static function moverDBF($file_name,$esquema,$localidad_codigo=null)
    {
         if (isset($localidad_codigo)) {
                 $filtro=" WHERE prov||dpto||codloc = '".$localidad_codigo."' ";
          } else {
                 $filtro='';
         }
        self::createSchema($esquema);
        $tabla = strtolower( substr($file_name,strrpos($file_name,'/')+1,-4) );
        $esquema = 'e'.$esquema;
        Log::debug('Cargando dbf en esquema-> '.$esquema);
        try{
            DB::beginTransaction();
//            DB::unprepared('ALTER TABLE "'.$tabla.'" SET SCHEMA '.$esquema);
            DB::unprepared('CREATE TABLE "'.$esquema.'"."'.$tabla.'" AS SELECT * FROM "'.$tabla.'" '.$filtro);
            DB::unprepared('ALTER TABLE "'.$esquema.'"."'.$tabla.'" RENAME TO listado');
            DB::unprepared('ALTER TABLE "'.$esquema.'".listado ADD COLUMN id serial');
            if (! Schema::hasColumn($esquema.'.listado' , 'tipoviv')){
                if (Schema::hasColumn($esquema.'.listado' , 'cod_tipo_2')){
                    DB::unprepared('ALTER TABLE '.$esquema.'.listado RENAME cod_tipo_2 TO tipoviv');
                }elseif (Schema::hasColumn($esquema.'.listado' , 'cod_tipo_v')){
                        DB::unprepared('ALTER TABLE '.$esquema.'.listado RENAME cod_tipo_v TO tipoviv');
                }elseif (Schema::hasColumn($esquema.'.listado' , 'cod_viv')){
                        DB::unprepared('ALTER TABLE '.$esquema.'.listado RENAME cod_viv TO tipoviv');
                    }elseif (Schema::hasTable($esquema.'.listado')){
                        DB::statement('ALTER TABLE '.$esquema.'.listado ADD COLUMN tipoviv text;');
                    }
            }
            if (! Schema::hasColumn($esquema.'.listado' , 'cod_subt_v')){
                if (Schema::hasColumn($esquema.'.listado' , 'cod_subt_2')){
                    DB::unprepared('ALTER TABLE '.$esquema.'.listado RENAME
                    cod_subt_2 TO cod_subt_v');
                }else{
                  DB::statement('ALTER TABLE '.$esquema.'.listado ADD COLUMN cod_subt_v text;');
                  Log::debug('No se encontró cod_subt_v, se agrega');
                }
            }elseif (Schema::hasColumn($esquema.'.listado' , 'cod_subt_2')){
                  DB::statement('ALTER TABLE '.$esquema.'.listado ADD COLUMN
                    cod_subt_v_original text ;');

                  DB::statement("UPDATE ".$esquema.".listado SET
                  cod_subt_v_original=cod_subt_v,
                    cod_subt_v=COALESCE(nullif(trim(cod_subt_2),''),nullif(trim(cod_subt_v),''));");
                  Log::debug('Se encontró cod_subt_v y cod_subt_2, se usa el _2
                    y si esta vacio se usa el _v');
                }

            else{
                  DB::statement('UPDATE '.$esquema.'.listado SET
                  cod_subt_v=trim(cod_subt_v);');
                  Log::debug('Se quitan espacios en cod_subt_v');
            }

            self::UpdateTipoVivDescripcion($esquema);

            if (! Schema::hasColumn($esquema.'.listado' , 'codent')){
                        DB::statement('ALTER TABLE '.$esquema.'.listado ADD
                        COLUMN codent text;');
            }
            if (! Schema::hasColumn($esquema.'.listado' , 'nom_ent')){
                        DB::statement('ALTER TABLE '.$esquema.'.listado ADD
                        COLUMN nom_ent text;');
            }
            if (! Schema::hasColumn($esquema.'.listado' , 'piso')){
                    if (  Schema::hasColumn($esquema.'.listado' , 'pisoredef')){
                        DB::unprepared('ALTER TABLE '.$esquema.'.listado RENAME pisoredef TO piso');
                    }else{
                        DB::statement('ALTER TABLE '.$esquema.'.listado ADD
                            COLUMN piso text;');
                    }
      }elseif (Schema::hasColumn($esquema.'.listado' , 'pisoredef')){
                  DB::statement('ALTER TABLE '.$esquema.'.listado ADD COLUMN
                   piso_original text ;');

                  DB::statement("UPDATE ".$esquema.".listado SET
                  piso_original=piso,
                    piso=COALESCE(nullif(trim(pisoredef),''),nullif(trim(piso),''));");
                  Log::debug('Se encontró piso y pisoredef, se usa el pisoredef
                    y si esta vacio se usa el piso');

      }

                if (! Schema::hasColumn($esquema.'.listado' , 'nrocatastr')){
                        if (Schema::hasColumn($esquema.'.listado' , 'nro_catast')){
                        DB::unprepared('ALTER TABLE '.$esquema.'.listado RENAME
                        nro_catast TO nrocatastr');
                        }else{
                        DB::statement('ALTER TABLE '.$esquema.'.listado ADD
                            COLUMN nrocatastr text;');
                        }
                }


                if (! Schema::hasColumn($esquema.'.listado' , 'descripci2')){
                            DB::statement('ALTER TABLE '.$esquema.'.listado ADD
                                COLUMN descripci2 text;');
                }

                if (Schema::hasColumn($esquema.'.listado' , 'mza')){
                            $resultado = DB::statement('UPDATE '.$esquema.'.listado SET
                            mza=right(mza,3);');
                            Log::debug('Se adaptaron lados tomando 3 ultimos caractares ignorando primera letra -> '.$resultado);

                }

                // Tomo nro_listado en lugar de orden reco para CABA
                    if (MyDB::getProv('listado',$esquema)=='02'){
                        Log::debug('Se detecto CABA');
                        if ( Schema::hasColumn($esquema.'.listado' , 'nro_listad')){
                            Log::debug('campo nro_listad');
                            if ( Schema::hasColumn($esquema.'.listado' , 'orden_reco')){
                                DB::statement('ALTER TABLE '.$esquema.'.listado
                                RENAME COLUMN orden_reco TO orden_reco_bak;');
                                Log::debug('Se backupea orden_reco original');
                            }
                                DB::statement('ALTER TABLE '.$esquema.'.listado
                                RENAME COLUMN nro_listad TO orden_reco;');
                                DB::statement('ALTER TABLE '.$esquema.'.listado ADD
                                    COLUMN nro_listad integer;');
                                Log::debug('Se cambia orden_reco x nro_listado');
                        }
                }

                DB::commit();
            self::eliminaRepetidosListado($esquema);
            self::eliminaLSVconViviendasEnListado($esquema);
         } catch (QueryException $e) {
             DB::Rollback();
             if ($e->getCode() == '42P07'){
               // Tabla duplicada
               //self::comparaEsquema($esquema,$a_esquema,'listado');
               flash('Ya existe un listado cargado en '.$esquema)->warning();
             } else {
               Log::error('Error: '.$e);
               //DB::unprepared('DROP TABLE IF EXISTS '.$esquema.'.listado CASCADE');
            }
        }
            self::juntaListadoGeom($esquema);
        }

        public static function UpdateTipoVivDescripcion($esquema,$fix=false) {
            if (Schema::hasColumn($esquema.'.listado' , 'tipoviv') and Schema::hasColumn($esquema.'.listado' , 'descripcio')){
                  DB::statement("UPDATE ".$esquema.".listado SET
                    tipoviv = case
                      when descripcio ilike '%Conteo - A%' then 'A'
                      when descripcio ilike '%Conteo - B1%' then 'B1'
                      when descripcio ilike '%Conteo - B2%' then 'B2'
                      when descripcio ilike '%Conteo - B3%' then 'B3'
                      when descripcio ilike '%Conteo - C%' then 'C'
                      when descripcio ilike '%Conteo - D%' then 'D'
                      when descripcio ilike '%Conteo - H%' then 'H'
                      when descripcio ilike '%Conteo - J%' then 'J'
                      when descripcio ilike '%Conteo - VE%' then 'VE'
                      when descripcio ilike '%Conteo - FD%' then 'FD'
                      when descripcio ilike '%Conteo - CA/CP%' then 'CA/CP'
                      when descripcio ilike '%Conteo - CO%' then 'CO'
                      else 'X'
                    end
                    where tipoviv = 'X' and descripcio ilike '%Conteo - %'
                  ;");
                  Log::debug("Se toma tipoviv de descripcio si tipoviv es X y figura 'Conteo - Tipo' en descripcio");
            }
            if ($fix){
                 DB::unprepared("Select indec.cargar_conteos('".$esquema."')");
            }
        }

        public static function eliminaRepetidosListado($esquema,$tabla='listado'){
          if (Schema::hasTable($esquema.'.'.$tabla)){
            Log::debug('eliminando registros repetidos en listado '.$esquema);
            DB::beginTransaction();
              try {$result=DB::delete('delete from '.$esquema.'.'.$tabla.'
                                      where id not in
                                      (select min(id) from '.$esquema.'.'.$tabla.'
                                      group by prov, dpto, codloc, frac, radio, mza, lado, orden_reco);');
              }catch (\Illuminate\Database\QueryException $exception) {
                        Log::error('No se pudo eliminar registros repetidos en '.$esquema.'.'.$tabla,$exception);
                        DB::Rollback();
              };
            DB::commit();
          }
        }

        public static function eliminaLSVconViviendasEnListado($esquema,$tabla='listado'){
          if (Schema::hasTable($esquema.'.'.$tabla)){
            Log::debug('eliminando registros LSV en lados con viviendas del listado '.$esquema);
            DB::beginTransaction();
              try {$result=DB::delete('DELETE FROM  '.$esquema.'.'.$tabla.'
                   WHERE (frac,radio,mza,lado) in (select frac,radio,mza,lado from '.$esquema.'.'.$tabla.'
                    group by frac,radio,mza,lado
                    having \'LSV\' = ANY (array_agg(tipoviv)) and count(*)>1) and tipoviv = \'LSV\';');
              }catch (\Illuminate\Database\QueryException $exception) {
                        Log::error('No se pudo eliminar registros LSV en lados con viviendas del listado '.$esquema.'.'.$tabla,$exception);
                        DB::Rollback();
              };
            DB::commit();
          }
        }

        public static function generarAdyacencias($esquema){
               DB::unprepared("Select indec.generar_adyacencias('".$esquema."')");
               self::createIndex($esquema,'lados_adyacentes','substr(mza_i,1,2),substr(mza_i,3,3),substr(mza_i,9,2),substr(mza_i,11,2),substr(mza_i,13,3)');
               self::createIndex($esquema,'lados_adyacentes','substr(mza_j,1,2),substr(mza_j,3,3),substr(mza_j,9,2),substr(mza_j,11,2),substr(mza_j,13,3)');
        }

        public static function juntaListadoGeom($esquema){
            if (Schema::hasTable($esquema.'.arc') and Schema::hasTable($esquema.'.listado')){
                Log::debug('Geometrias y listado cargado, se comienza posprocesos');
                // Comienzan posprocesos de carga
                DB::beginTransaction();
                    try {
                        if (! Schema::hasColumn($esquema.'.arc' , 'nomencla10')){
                                    DB::statement('ALTER TABLE '.$esquema.'.arc ADD COLUMN nomencla10 text;');
                                    Log::debug('Invento nomencla10 en arc de esquema '.$esquema);
                        }
                        if (! Schema::hasColumn($esquema.'.listado' , 'nomencla10')){
                                    DB::statement('ALTER TABLE '.$esquema.'.listado ADD COLUMN nomencla10 text;');
                                    Log::debug('Invento nomencla10 en listado de esquema '.$esquema);
                        }
                        if (! Schema::hasColumn($esquema.'.arc' , 'segi')){
                                    DB::statement('ALTER TABLE '.$esquema.'.arc ADD COLUMN IF NOT EXISTS segi integer;');
                        }
                        if (! Schema::hasColumn($esquema.'.arc' , 'segd')){
                                DB::statement('ALTER TABLE '.$esquema.'.arc ADD COLUMN IF NOT EXISTS segd integer;');
                        }
                        DB::commit();
                    }catch (\Illuminate\Database\QueryException $exception) {
                            Log::error('No se pudieron cargar lados '.$exception);
                            DB::Rollback();
                    };

              if ( (! Schema::hasTable($esquema.'.conteos')) or (! Schema::hasTable($esquema.'.lados')) ) {
                DB::beginTransaction();
                    try {
                        DB::unprepared("Select indec.cargar_lados('".$esquema."')");
                        DB::unprepared("Select indec.cargar_conteos('".$esquema."')");
                        self::createIndex($esquema,'conteos','prov,dpto,frac,radio,mza,lado');
                        self::generarAdyacencias($esquema);
                        Log::info('Se procesaron lados, conteos y adyacencias!');
                        DB::commit();

                    // Comienzan posprocesos de carga
                       DB::beginTransaction();
                        try {
                           DB::unprepared("Select indec.descripcion_segmentos('".$esquema."')");
                           DB::commit();
                        }catch (\Illuminate\Database\QueryException $exception) {
                           Log::error('No se pudo crear la descripcion de los segmentos: '.$exception);
                           DB::Rollback();
                       }
                    self::addSequenceSegmentos($esquema);
                    self::generarSegmentacionNula($esquema);

                    // Indices y georef.
                    $schema=$esquema;
                    self::addIndexListado($schema);
                    flash('Se creo el indice para lados en listado en '.$schema);
                    self::addIndexListadoId($schema);
                    flash('Se creo el indice para id listado en '.$schema);
                    self::addIndexListadoRadio($schema);
                    flash('Se creo el indice para radio en listado en '.$schema);

                    }catch (\Illuminate\Database\QueryException $exception) {
                            Log::error('No se pudieron cargar lados '.$exception);
                            DB::Rollback();
                    };
                }


            $schema=$esquema;
            if (self::cargarTopologia($schema)) {
                flash('Se creo la topología para '.$schema)->success()->important();
            }else{
                flash('No se pudo validar la topología para '.$schema)->warning()->important();
            }

            self::georeferenciar_listado($schema);

            }
        }

        public static function borrarTabla($tabla)
        {
        // Borrar tabla "temporal"
        try {
              DB::beginTransaction();
              DB::statement('DROP TABLE "'.$tabla.'" CASCADE;');
              DB::commit();
              Log::info('Se eliminó el tabla '.$tabla);
              return true;
             }catch (\Illuminate\Database\QueryException $exception) {
                try {
                  DB::Rollback();
                  DB::beginTransaction();
                  DB::statement('DROP TABLE "'.strtolower($tabla).'" CASCADE;');
                  DB::commit();
                  Log::info('Se eliminó la tabla '.strtolower($tabla).' en el segundo intento.');
                  return true;
                }catch (\Illuminate\Database\QueryException $exception) {
                    Log::error('No se pudo borrar la tabla: '.$exception);
                    DB::Rollback();
                    return false;
                }
             }
        }

        public static function limpiar_esquema($esquema)
        {
        // Comienzan limpieza de esquema
        try {
             // si el nombre del esquema tiene mas de 20 caracteres, es temporal :/
             if (strlen($esquema) < 40 ) {
                  $query_limpieza = 'ALTER SCHEMA "'.$esquema.'" RENAME TO "h_'.$esquema.'";';
                  $mensaje = 'Se renombró el esquema '.$esquema;
              } else {
                  $query_limpieza = 'DROP SCHEMA "'.$esquema.'" CASCADE;';
                  $mensaje = 'Se borró el esquema '.$esquema;
              }
              DB::beginTransaction();
              DB::statement($query_limpieza);
              DB::commit();
              Log::info($mensaje);
              return true;
                }catch (\Illuminate\Database\QueryException $exception) {
                    Log::error('No se pudo limpiar el esquema: '.$exception);
                    DB::Rollback();
                    return false;
                }
        }

        public static function agregarsegisegd(String $esquema)
        {
            if (Schema::hasTable('e'.$esquema.'.arc')) {
            DB::statement('ALTER TABLE e'.$esquema.'.arc ADD COLUMN IF NOT EXISTS segi integer;');
            DB::statement('ALTER TABLE e'.$esquema.'.arc ADD COLUMN IF NOT EXISTS segd integer;');
            return true;
            }
            else{
            return false;
            }
        }


        public static function generarSegmentacionNula($esquema)
        {
            if (Schema::hasTable($esquema.'.listado')) {
              if (!Schema::hasTable($esquema.'.segmentacion')) {
                DB::statement('create TABLE if not exists '.$esquema.'.segmentacion as
                    select id as listado_id, Null::integer as segmento_id
                    from '.$esquema.'.listado
                    ;');
                return true;
              }else{
                DB::beginTransaction();
                DB::statement('truncate TABLE '.$esquema.'.segmentacion');
                DB::statement('insert into '.$esquema.'.segmentacion
                    select id as listado_id, Null::integer as segmento_id
                    from '.$esquema.'.listado
                    ;');
                DB::commit();
                return true;
              }
            }
            else{
            return false;
            }
        }

        public static function sincroSegmentacion($esquema){
            if (Schema::hasTable($esquema.'.listado')) {
              if (Schema::hasTable($esquema.'.segmentacion')) {
                DB::beginTransaction();
                try {
                  // Borra los id de listado que no están más en listado
                  DB::delete('delete from '.$esquema.'.segmentacion
                                      where listado_id not in
                                      (select id from '.$esquema.'.listado
                                      );');
                  // Borra las segmentaciones de la r3 que no están más en segmentacion
                  DB::delete('delete from '.$esquema.'.r3
                                      where segmento_id not in
                                      (select segmento_id from '.$esquema.'.segmentacion
                                      );');
                  // Agrega los id de lisado nuevos en el listado.
                  DB::statement('insert into '.$esquema.'.segmentacion
                    select id as listado_id, Null::integer as segmento_id
                    from '.$esquema.'.listado
                    where id not in (select listado_id from '.$esquema.'.segmentacion )
                    ;');
                  DB::commit();
                  Log::debug('Sincronizado listado con segmentacion para '.$esquema);
                }catch (\Illuminate\Database\QueryException $exception) {
                        Log::error('No se pudo resincronizar listado con tabla segmentacion para '.$esquema,[$exception]);
                        DB::Rollback();
                }
              return true;
              }
            }
            else{
              return false;
            }
        }

        public static function generarSegmentacionVacia($esquema)
        {
            if (Schema::hasTable('e'.$esquema.'.listado')) {
            DB::statement('create TABLE if not exists
                e'.$esquema.'.segmentacion (listado_id integer, segmento_id integer)
                ;');
            return true;
            }
            else{
            return false;
            }
        }

        public static function generarR3Vacia($esquema)
        {
            if (! Schema::hasTable('e'.$esquema.'.r3')) {
            DB::statement('create TABLE if not exists
                e'.$esquema.".r3 as select * from indec.describe_segmentos_con_direcciones_ffrr('e".$esquema."', 0, 0)
                ;"); // crea la R3 con una consulta que devuelve la esctructura vacía
            return true;
            }
            else{
            return false;
            }
        }

        public static function grabarSegmentacion($esquema,$frac=null,$radio=null)
        {
            if ($frac!=null) {
              try{
                 DB::statement("select indec.sincro_r3_ffrr('e".$esquema."', $frac, $radio);");
                 Log::info('Se actualizó la R3 para Esquema: '.$esquema.' F:'.$frac.' R:'.$radio.' !');
                 return true;
              }catch(QueryException $e){
                 Log::error($e);
                 return false;
              }
        // guarda indec.describe_segmentos_con_direcciones_ffrr en esquema.r3 (hace delete & insert)a
      }else{
        DB::statement("SELECT indec.sincro_r3('e".$esquema."');");
            }
            Log::info('Se actualizó la R3! ('.$esquema.')');
            return true;
        }


        public static function segmentar_equilibrado($esquema,$deseado = 10,Radio $radio=null)
        {
            $AppUser= Auth::user();
            $processLog = Process::fromShellCommandline('echo "$tiempo: $usuario_name ($usuario_id) -> va a segmentar a manzana independiente: $info_segmenta"  >> segmentaciones.log');
            $processLog->run(null, ['info_segmenta' => " Aglomerado: ".$esquema ,
                                    'usuario_id' => $AppUser->id,
                                    'usuario_name' => $AppUser->name,
                                    'tiempo' => date('Y-m-d H:i:s')]);
            if ($radio){
             try{
                if ( DB::statement("SELECT indec.segmentar_equilibrado_ffrr(
                      'e".$esquema."',".$radio->CodigoFrac.",".$radio->CodigoRad.",".$deseado.");") )
                    {
                     // Llamar a función guardar segmentación para actualizar la r3 con los resultados...
                     // $esquema para el esquema completo.
                     self::grabarSegmentacion($esquema,$radio->CodigoFrac,$radio->CodigoRad);
                      return true;
                    }else{
                      return false; }
                }catch (QueryException $e){
                  LOG::error('Se produjo algún error luego de segmentar equilibrado a manzanas independientes '.$e);
                  flash('Se produjo algún error luego de segmentar equilibrado a manzanas independientes')->error()->important();
                  return false;
             }
            }else{ //Segmentar equilibrado esquema entero.
             try{
                self::addSequenceSegmentos('e'.$esquema,false);
                self::generarSegmentacionNula('e'.$esquema);
                if ( DB::statement("SELECT indec.segmentar_equilibrado('e".$esquema."'::text,".$deseado.");") ){
                // llamar generar r3 como tabla resultado de function indec.r3(agl)
                    ( DB::statement("SELECT indec.descripcion_segmentos('e".$esquema."');") );
                 flash('Resultado: '.self::juntar_segmentos('e'.$esquema));
                 // Llamar a función guardar segmentación para actualizar la r3 con los resultados...
                 // $esquema para el esquema completo.
                    self::grabarSegmentacion($esquema);
                    return true;
                }else{
                    return false; }
             }catch (QueryException $e){
                  LOG::error('Se produjo algún error luego de segmentar equilibrado a manzanas independientes '.$e);
                  flash('Se produjo algún error luego de segmentar equilibrado a manzanas independientes')->error()->important();
                  return false;
            }
           }
        // SQL retrun: Select segmento_id,count(*) FROM e0777.segmentacion GROUP BY segmento_id;
        }

        public static function
        segmentar_equilibrado_ver($esquema,$max=1000,Radio $radio = Null)
        {
            $esquema = 'e'.$esquema;
            if ($radio){
                $esquema=$radio->esquema;
                $filtro= ' where (frac::integer,radio::integer) =
                    ('.$radio->CodigoFrac.','.$radio->CodigoRad.') ';
                if (Schema::hasTable($esquema.'.r3')){
                  $funcion_describe= ' "'.$esquema.'".r3 '.$filtro;
                }else{
                  $funcion_describe= " indec.describe_segmentos_con_direcciones_ffrr('".$esquema."',".$radio->CodigoFrac.",".$radio->CodigoRad.") ";
                }
            }else{
              $filtro = '';
              if (Schema::hasTable($esquema.'.r3')){
                $funcion_describe= ' "'.$esquema.'".r3 ';
              }else{
                $funcion_describe= " indec.describe_segmentos_con_direcciones('".$esquema."') ";
              }
            }

            try{
                return DB::select("SELECT segmento_id, lpad(frac::text,2,'0') frac,
                        lpad(radio::text,2,'0') radio, viviendas vivs,
                            descripcion detalle, lpad(seg,2,'0') seg, null ts
                            FROM
                            indec.describe_despues_de_muestreo('".$esquema."')
                            ".$filtro." ;");
                }catch(QueryException $e){
                    Log::info('Sin muestreo...');
            try{
                return DB::select("
                        SELECT segmento_id, lpad(frac::text,2,'0') frac,
                        lpad(radio::text,2,'0') radio, viviendas vivs,
                            descripcion detalle, lpad(seg,2,'0') seg, null ts
                            FROM
                            ".$funcion_describe."
                            order by frac,radio,seg,segmento_id
                            LIMIT ".$max.";");
                }catch(QueryException $e){
                        Log::warning('Se detecto una carga antigua o con problemas.
                            Se hace lo que se puede.');
                        try{
                        return DB::select('
                            SELECT segmento_id,l.frac,l.radio,count(*)
                            vivs,count(distinct mza) as mza,array_agg(distinct
                            prov||dpto||codloc||frac||radio||mza||lado)
                            detalle,count(distinct lado) as lado,
                            segmento_id seg,
                            null ts
                            FROM
                            '.$esquema.'.segmentacion s JOIN
                            '.$esquema.'.listado l ON s.listado_id=l.id
                            '.$filtro.'
                            GROUP BY segmento_id,l.frac,l.radio
                            ORDER BY count(*) asc, array_agg(mza), segmento_id
                            LIMIT '.$max.';');
                        }catch(QueryException $e){
                            if ($e->getCode() == '42P01'){
                              Log::notice('No hubo modo de encontrar una segmentación en esquema: '.$esquema.' para el radio: ',[$radio]);
                            }else{Log::error('Se produjo error no esperado buscando segmentación:',[$e]);}
                            return [];
                        }
                }
            }
        }

        public static function segmentar_equilibrado_ver_resumen($esquema)
        {
            $esquema = 'e'.$esquema;
            // Mismo criterio que segmentar_equilibrado_ver() (arriba): si el
            // aglomerado todavía no tiene tabla de segmentación (nunca se
            // corrió la segmentación), no es un error real - devuelve vacío
            // en vez de un 500 con SQL crudo. Bug real encontrado navegando
            // a /grafo/{id} de un aglomerado sin segmentar (168/0810).
            try {
                return DB::select('SELECT vivs,count(*) cant_segmentos,
                            string_agg(distinct array_to_string(en_lados,\' \'),\',\') en_lados FROM (
                            SELECT segmento_id,count(indec.contar_vivienda(tipoviv)) vivs,count(distinct mza) as
                            mzas,array_agg(distinct
                            frac||radio) en_lados,count(distinct lado) as lados FROM
                            '.$esquema.'.
                            segmentacion s JOIN
                            '.$esquema.'.
                            listado l ON s.listado_id=l.id
                            WHERE segmento_id is not null
                            GROUP BY segmento_id
                            ORDER BY count(*) asc, array_agg(mza), segmento_id) foo GROUP BY vivs order by vivs asc;');
            } catch (QueryException $e) {
                if ($e->getCode() == '42P01') {
                    Log::notice('No hubo modo de encontrar una segmentación en esquema: '.$esquema);
                } else {
                    Log::error('Se produjo error no esperado buscando segmentación (resumen equilibrado):', [$e]);
                }
                return [];
            }
        // SQL retrun:
        }

        public static function segmentar_lados_ver($esquema)
        {
            $esquema = 'e'.$esquema;
            return DB::select('
                            SELECT substr(lados.mza,1,12) radio, lados.seg,count(*)
                            lados,count(distinct lados.mza) as
                            mzas_count,array_agg(distinct substr(lados.mza,13,3))
                            mzas,sum(conteo) as vivs, d.descripcion FROM
                (SELECT segi seg,mzai mza,ladoi lado FROM '.$esquema.'.arc WHERE segi is not null
                UNION SELECT segd,mzad,ladod FROM '.$esquema.'.arc WHERE segd is not null) lados
                        JOIN  '.$esquema.'.conteos c ON (c.prov,c.dpto,c.codloc,c.frac,c.radio,c.mza,c.lado)=(
                        substr(lados.mza,1,2)::integer,substr(lados.mza,3,3)::integer,substr(lados.mza,6,3)::integer,
                        substr(lados.mza,9,2)::integer,substr(lados.mza,11,2)::integer,substr(lados.mza,13,3)::integer,lados.lado::integer)
                        JOIN  '.$esquema.'.descripcion_segmentos d ON
                            (d.prov::integer,d.depto::integer,d.codloc::integer,d.frac::integer,d.radio::integer,d.seg)=(
                            substr(lados.mza,1,2)::integer,substr(lados.mza,3,3)::integer,substr(lados.mza,6,3)::integer,
                            substr(lados.mza,9,2)::integer,substr(lados.mza,11,2)::integer,lados.seg::integer)
                                WHERE substr(lados.mza,1,12)!=\'\'
                                GROUP BY  substr(lados.mza,1,12), lados.seg,descripcion');
            // SQL retrun:
            }

            public static function segmentar_lados_ver_resumen($esquema)
            {
                $esquema = 'e'.$esquema;
                // Mismo criterio que segmentar_equilibrado_ver_resumen(): sin
                // arc/conteos todavía (aglomerado sin segmentar por lados
                // completos), no es un error real - devuelve vacío.
                try {
                    return DB::select('SELECT vivs,count(seg) cant_segmentos FROM (
                    SELECT substr(lados.mza,1,12) radio, seg,count(*) lados,count(distinct lados.mza) as mzas_count,array_agg(distinct substr(lados.mza,13,3)) mzas, sum(c.conteo) vivs FROM
                    (SELECT segi seg,mzai mza,ladoi lado FROM '.$esquema.'.arc WHERE segi is not null
                    UNION SELECT segd,mzad,ladod FROM '.$esquema.'.arc WHERE segd is not null) lados
                                JOIN '.$esquema.'.conteos c ON (c.prov,c.dpto,c.codloc,c.frac,c.radio,c.mza,c.lado)=(
                                                                substr(lados.mza,1,2)::integer,substr(lados.mza,3,3)::integer,substr(lados.mza,6,3)::integer,
                                                                substr(lados.mza,9,2)::integer,substr(lados.mza,11,2)::integer,substr(lados.mza,13,3)::integer,lados.lado::integer)
                                WHERE lados.mza != \'\'
                                GROUP BY  substr(lados.mza,1,12), seg ) foo
                                GROUP BY vivs order by vivs asc;');
                } catch (QueryException $e) {
                    if ($e->getCode() == '42P01') {
                        Log::notice('No hubo modo de encontrar una segmentación por lados en esquema: '.$esquema);
                    } else {
                        Log::error('Se produjo error no esperado buscando segmentación (resumen lados):', [$e]);
                    }
                    return [];
                }
            // SQL retrun:
    }

    /*
    * Funcion que georreferencia el listado según la cartografía.
    * Crea tabla para esquema o por fracción hace actualización.
    */
    public static function georeferenciar_listado(
        $esquema, $desplazamiento_vereda=8, $frac=null, $radio=null
    ) {
        $desp=-1*$desplazamiento_vereda;
        //   --ALTER TABLE ' ".$esquema." '.arc alter column wkb_geometry type geometry('LineString',22182) USING (st_setsrid(wkb_geometry,22182));
        if ($frac != null) {
            $filtro= ' where (l.frac::integer) =
                      ('.$frac.') ';
            $filtro_arcos = ' and substr(mza,9,2)::integer = '.$frac.' ';
            $insert_into = '';
         if (Schema::hasTable($esquema.'.listado_geo')) {
            $update_to = " INSERT INTO ".$esquema.".listado_geo ";
         } else {
            $update_to = "";
            $insert_into = " INTO ".$esquema.".listado_geo ";
         }
         if ($radio != null) {
             $filtro .= ' and (l.radio::integer) =
                       ('.$radio.') ';
             $filtro_arcos .= ' and substr(mza,11,2)::integer = '.$radio.' ';
             $insert_into = '';
         }
        } else {
            $filtro = '';
            $filtro_arcos = '';
            $update_to = '';
            $insert_into = " INTO ".$esquema.".listado_geo ";
        }

            try{
                DB::beginTransaction();
            if ($update_to=='' ) {
                // DB::statement("DROP TABLE IF EXISTS ".$esquema.".listado_geo;");
                // No se dropea más.
                flash('Se encontró listado georreferenciado, no se dropea más, se puede actualizar x frac, o radio')->warning();
            } else {
                DB::statement("DELETE FROM ".$esquema.".listado_geo l ".$filtro);
            }
              $query="
                WITH listado as (
                SELECT id, l.prov, nom_provin, l.dpto, nom_dpto, l.codaglo, l.codloc,
                nom_loc, codent, nom_ent, l.frac, l.radio, l.mza, l.lado,
                CASE WHEN nro_inicia='' THEN 0 ELSE nro_inicia::integer END
                ::integer as nro_inicia,
                CASE WHEN nro_final='' THEN 0 ELSE nro_final::integer END
                ::integer as nro_final,
                CASE WHEN orden_reco='' THEN 0 ELSE orden_reco::integer END ::integer as orden_reco,
                nro_listad, ccalle, ncalle,
                CASE WHEN l.nrocatastr in ('','S/N','S N') THEN null::integer ELSE
                l.nrocatastr::integer END nrocatastr,
            piso, casa, dpto_habit, trim(sector) sector, trim(regexp_replace(replace(edificio,'Â¾','ó'),'â\u0096\u0091','°')) edificio, trim(entrada) entrada, tipoviv, descripcio, descripci2 ,
            row_number() over w_lado as nro_en_lado,
            count(*) over w as conteo,
            conteo as conteo_vivs,
            row_number() over w_nrocatastr as nro_en_numero
            FROM
            ".$esquema.".listado l
            LEFT JOIN ".$esquema.".conteos c ON
            (c.prov,c.dpto,c.codloc,c.frac,c.radio,c.mza,c.lado)=
            (l.prov::integer,l.dpto::integer,l.codloc::integer,l.frac::integer,l.radio::integer,l.mza::integer,l.lado::integer)
            ".$filtro."
            WINDOW w_nrocatastr AS (partition by l.frac, l.radio, l.mza, l.lado ,
            nrocatastr
            order by CASE WHEN orden_reco='' THEN 1::integer ELSE
            orden_reco::integer END asc),
            w_lado AS (partition by l.frac, l.radio, l.mza, l.lado order by
            CASE WHEN orden_reco='' THEN 1::integer ELSE
            orden_reco::integer END asc),
            w AS (partition by l.frac, l.radio, l.mza, l.lado)
        ),
        arcos as (
      SELECT min(ogc_fid) ogc_fid, st_LineMerge(st_union(wkb_geometry)) wkb_geometry,
                   nomencla,codigo20,array_agg(distinct codigo10) codigo10, tipo, nombre,lado,min(desde) desde,
            max(hasta) hasta,mza
            FROM
      (SELECT ogc_fid,st_reverse(wkb_geometry) wkb_geometry,nomencla,codigo20,codigo10,
             tipo, nombre, ancho, anchomed, ladoi lado,desdei desde,
        hastai hasta,mzai mza
        FROM ".$esquema.".arc
        UNION
        SELECT ogc_fid,wkb_geometry, nomencla,codigo20,codigo10,tipo, nombre,
               ancho, anchomed, ladod lado,desded desde,
               hastad hasta,mzad mza
        FROM ".$esquema.".arc
        ) arcos_juntados
        GROUP BY nomencla,codigo20,tipo, nombre,lado,mza
        HAVING
        st_geometrytype(st_LineMerge(st_union(wkb_geometry)))='ST_LineString'
        and mza!=''
        ".$filtro_arcos."
    )
        ".$update_to."
    SELECT nro_en_lado, nro_en_numero, conteo,1.0*nro_en_lado/(conteo+1) interpolacion, l.orden_reco,
    case when 1.0*nro_en_lado/(conteo+1)>1 then
        ST_LineInterpolatePoint(st_reverse(st_offsetcurve(ST_LineSubstring(st_LineMerge(wkb_geometry),0.07,0.93),".$desp."-nro_en_lado)),0.5)
    else
    CASE WHEN (
            e.mza like '%'||btrim(to_char(l.frac::integer, '09'::text))::character varying(3)||btrim(to_char(l.radio::integer, '09'::text))::character varying(3)||btrim(to_char(l.mza::integer, '099'::text))::character varying(3))
                    and l.lado::integer=e.lado and (l.tipoviv='LSV' or
                    l.tipoviv='')
                    THEN
                    ST_LineInterpolatePoint(st_reverse(st_offsetcurve(ST_LineSubstring(st_LineMerge(wkb_geometry),0.07,0.93),".$desp."-(0.5*nro_en_numero))),0.5)
            WHEN ( e.mza like '%'||btrim(to_char(l.frac::integer, '09'::text))::character varying(3)||btrim(to_char(l.radio::integer, '09'::text))::character varying(3)||btrim(to_char(l.mza::integer, '099'::text))::character varying(3))
                    and l.lado::integer=e.lado
                    THEN
                    ST_LineInterpolatePoint(st_reverse(st_offsetcurve(ST_LineSubstring(st_LineMerge(wkb_geometry),0.07,0.93),".$desp."-(0.5*nro_en_numero))),1.0*(nro_en_lado)/(conteo+1))
                end
                END as wkb_geometry, e.ogc_fid||'-'||l.id id ,e.ogc_fid id_lin,l.id id_list, wkb_geometry wkb_geometry_lado,
    CASE WHEN nro_final::integer-nro_inicia::integer>0 and (nrocatastr)>0 THEN
    row_number() OVER (PARTITION BY prov,dpto,codloc,frac,radio,l.mza,l.lado
    ORDER BY l.nrocatastr,l.piso)
    END orden_segun_numero,
    row_number() OVER (PARTITION BY prov,dpto,codloc,frac,radio,l.mza,l.lado, l.nrocatastr ORDER BY l.piso)
    orden_en_numero,

    CASE WHEN nro_final::integer-nro_inicia::integer>0 and (nrocatastr)>0 THEN
        CASE
        WHEN (((nrocatastr::integer-nro_inicia::integer)::numeric/(nro_final::integer-nro_inicia::integer)<0
                or (nrocatastr::integer-nro_inicia::integer)::numeric/(nro_final::integer-nro_inicia::integer)>1 )) THEN
            ST_LineInterpolatePoint(st_reverse(st_offsetcurve(ST_LineSubstring(st_LineMerge(wkb_geometry),0.07,0.93),".$desp.")),0.5)
            ELSE
            ST_LineInterpolatePoint(st_reverse(st_offsetcurve(ST_LineSubstring(st_LineMerge(wkb_geometry),0.07,0.93),".$desp.")),1-
                                    (nrocatastr::integer-nro_inicia::integer)::numeric/(nro_final::integer-nro_inicia::integer))
        END
    ELSE
    ST_LineInterpolatePoint(st_reverse(st_offsetcurve(ST_LineSubstring(st_LineMerge(wkb_geometry),0.07,0.93),".$desp.")),
        0.5 --deberia usarse la posicion del anterior.. tiro null quizas ?
        )
        END geom_segun_nro_catastral,
                    codigo10, nomencla, codigo20,
                    tipo, nombre, e.lado ladoe, desde, hasta,e.mza mzae,
                    frac, radio, l.mza, l.lado, ccalle, ncalle, l.nrocatastr, piso,casa,dpto_habit,sector,edificio,entrada,tipoviv,
                    descripcio,descripci2
  ".$insert_into."
  FROM arcos e JOIN listado l ON
            (l.lado::integer=e.lado and
                trim(e.mza) =
                btrim(to_char(l.prov::integer,'09'::text))::character varying(2)||btrim(to_char(l.dpto::integer,'099'::text))::character varying(3)||btrim(to_char(l.codloc::integer,'099'::text))::character varying(3)||btrim(to_char(l.frac::integer, '09'::text))::character varying(2)||btrim(to_char(l.radio::integer, '09'::text))::character varying(2)||btrim(to_char(l.mza::integer, '099'::text))::character varying(3)
            );";
            $resultado= DB::select($query);
            DB::commit();
            flash('Se georreferenció el listado para '.$esquema.' F:'.$frac.' R:'.$radio)->success()->important();

            }catch(QueryException $e){
                DB::Rollback();
                if ($e->getCode() == '42P07'){ //Tabla duplicada
                        flash('Ya existe. No se pudo georrefernciar el listado '.$esquema)->warning()->important();
                        return false;
                }
                    if ($desplazamiento_vereda==8){
                            flash('No se pudo georreferenciar el listado dentro
                            de la manzana para '.$esquema.'.
                            Reintentado a 1m del eje.')->warning();
                            if($resultado = self::georeferenciar_listado($esquema,1,$frac,$radio)){
                              flash('Se georreferenció el listado sobre el eje de
                              calle')->success()->important();
                            }
                    }else{

                        flash('No se pudo georrefernciar el listado para '.$esquema)->error()->important();
                        Log::error('No se pudo georreferenciar el
                        listado.',[$e->getMessage()]);
                        return false;
                    }
            }
            try{
                DB::statement("GRANT SELECT ON TABLE  ".$esquema.".listado_geo TO geoestadistica");
            }catch(QueryException $e){
                Log::warning('No se pudo dar permiso a geoestadistica sobre el listado.'.$e);
            }
            Log::debug('Georreferenciado: '.$esquema);
            return $resultado;
        }

        public static function geo_translate($esquema)
        {
            try{
                DB::statement("UPDATE ".$esquema.".listado_geo SET
                    wkb_geometry=st_translate(wkb_geometry,-50,50),
                    geom_segun_nro_catastral=st_translate(geom_segun_nro_catastral,-50,50)
                    ;");
            }catch(QueryException $e){
                    Log::error('No se pudo trasladar la geometria.'.$e);
                        flash('No se pudo trasladar geometria')->error();
                    return false;
            }
                return true;
        }

            public static function georeferenciar_segmentacion($esquema)
            {
                //return true;
        //   --ALTER TABLE ' ".$esquema." '.arc alter column wkb_geometry type geometry('LineString',22182) USING (st_setsrid(wkb_geometry,22182));
                $esquema = 'e'.$esquema;
                DB::statement("DROP TABLE IF EXISTS ".$esquema.".listado_segmentado_geo;");
                $resultado= DB::select("
                WITH listado as (
            SELECT id, l.prov, nom_provin, ups, nro_area, l.dpto, nom_dpto, l.codaglo, l.codloc, nom_loc, codent, nom_ent, l.frac, l.radio, l.mza, l.lado,
            s.segmento_id as segmento_id, nro_inicia, nro_final, orden_reco,
            nro_listad, ccalle, ncalle,
            CASE WHEN l.nrocatastr='' or l.nrocatastr='S/N' THEN null::integer ELSE
            l.nrocatastr::integer END as nrocatastr,
            piso, casa, dpto_habit, sector, edificio, entrada, tipoviv, descripcio, descripci2 ,
            row_number() over(partition by l.frac, l.radio, l.mza, l.lado order by l.lado, orden_reco asc) nro_en_lado, conteo,
            FROM
            ".$esquema.".listado l
            JOIN ".$esquema.".segmentacion s ON s.listado_id=l.id
            LEFT JOIN ".$esquema.".conteos c ON
            (c.prov,c.dpto,c.codloc,c.frac,c.radio,c.mza,c.lado)=(l.prov::integer,l.dpto::integer,l.codloc::integer,l.frac::integer,l.radio::integer,l.mza::integer,l.lado::integer)
        ),
        arcos as (
            SELECT min(ogc_fid) ogc_fid, st_LineMerge(st_union(wkb_geometry)) wkb_geometry,nomencla,codigo20,array_agg(distinct codigo10) codigo10, tipo, nombre,lado,min(desde) desde,
            max(hasta) hasta,mza
            FROM
            (SELECT ogc_fid,st_reverse(wkb_geometry) wkb_geometry,nomencla10 nomencla,codigo20,codigo10,tipo, nombre, ancho, anchomed, ladoi lado,desdei desde,
            hastai hasta,mzai mza, nomencla10,nomenclai nomenclax, codinomb, segi seg
            FROM ".$esquema.".arc
        UNION
            SELECT ogc_fid,wkb_geometry,nomencla10 nomencla,codigo20,codigo10,tipo, nombre, ancho, anchomed, ladod lado,desded desde,
            hastad hasta,mzad mza, nomencla10,nomenclad nomenclax, codinomb, segd seg
            FROM ".$esquema.".arc) arcos_juntados
            GROUP BY nomencla,codigo20,tipo, nombre,lado,mza
        )
        SELECT segmento_id,nro_en_lado, conteo,1.0*nro_en_lado/(conteo+1) interpolacion, l.orden_reco,
        case when nro_en_lado/(conteo+1)>1
        then ST_LineInterpolatePoint(st_reverse(st_offsetcurve(ST_LineSubstring(st_LineMerge(wkb_geometry),0.07,0.93),".$desp."-nro_en_lado)),0.5)
        else
        CASE WHEN (
            e.mza like '%'||btrim(to_char(l.frac::integer, '09'::text))::character varying(3)||btrim(to_char(l.radio::integer, '09'::text))::character varying(3)||btrim(to_char(l.mza::integer, '099'::text))::character varying(3))
                    and l.lado::integer=e.lado and l.tipoviv='LSV'
                    THEN ST_LineInterpolatePoint(st_reverse(st_offsetcurve(ST_LineSubstring(st_LineMerge(wkb_geometry),0.07,0.93),".$desp.")),0.5)
            WHEN ( e.mza like '%'||btrim(to_char(l.frac::integer, '09'::text))::character varying(3)||btrim(to_char(l.radio::integer, '09'::text))::character varying(3)||btrim(to_char(l.mza::integer, '099'::text))::character varying(3))
                    and l.lado::integer=e.lado
                    THEN ST_LineInterpolatePoint(st_reverse(st_offsetcurve(ST_LineSubstring(st_LineMerge(wkb_geometry),0.07,0.93),".$desp.")),1.0*nro_en_lado/(conteo+1))
                end
                END as wkb_geometry, e.ogc_fid||'-'||l.id id ,e.ogc_fid id_lin,l.id id_list, wkb_geometry wkb_geometry_lado,
                    codigo10, nomencla, codigo20,
                        tipo, nombre, e.lado ladoe, desde, hasta,e.mza mzae,
                        frac, radio, l.mza, l.lado, ccalle, ncalle, l.nrocatastr, piso,casa,dpto_habit,sector,edificio,entrada,tipoviv,
                    descripcio,descripci2 ,
        INTO ".$esquema.".listado_segmentado_geo
        FROM arcos e JOIN listado l ON l.ccalle::integer=e.codigo20
        and
            (l.lado::integer=e.lado and
                e.mza like
                '%'||btrim(to_char(l.frac::integer, '09'::text))::character varying(3)||btrim(to_char(l.radio::integer, '09'::text))::character varying(3)||btrim(to_char(l.mza::integer, '099'::text))::character varying(3)
            );");
            DB::statement("GRANT SELECT ON TABLE
            ".$esquema.".listado_segmentado_geo TO geoestadistica");
                return $resultado;
            }

            public static function getNodos($esquema,$radio = '%01103')
            {
                try{
                    return DB::select('SELECT distinct *, substr(mza_i,13,3)||\':\'||lado_i as label,c.conteo FROM (
                                                    SELECT mza_i,lado_i from '.$esquema.'.lados_adyacentes WHERE mza_i like :radio UNION
                                                    SELECT mza_j,lado_j from
                                                    '.$esquema.'.lados_adyacentes
                                                    WHERE mza_j like :radio2) foo
            LEFT JOIN
            '.$esquema.'.conteos c
            ON (c.prov,c.dpto,c.codloc,c.frac,c.radio,c.mza,c.lado)=
                (substr(mza_i,1,2)::integer,
                substr(mza_i,3,3)::integer,
                substr(mza_i,6,3)::integer,
                substr(mza_i,9,2)::integer,
                substr(mza_i,11,2)::integer,
                substr(mza_i,13,3)::integer,
                lado_i)

                                ',['radio'=>$radio.'%','radio2'=>$radio.'%']);
                }catch(QueryException $e){
                    Log::error('No se pudieron obtener los nodos en '.$esquema);
                }
            }

            public static function getAdyacencias($esquema,$radio = '%01103')
            {
                try{
                    return DB::select('SELECT * from '.$esquema.'.lados_adyacentes
                WHERE mza_i like :radio and mza_j like :radio;',['radio'=>$radio.'%']);
                }catch(QueryException $e){
                    Log::error('No se pudieron obtener las adyacencias');
                }
            }

            public static function getSegmentos($esquema,$radio = '%01103')
            {
            try{
                        return DB::select('SELECT array_agg(mza||\'-\'||lado) segmento
                                            FROM
                                            (SELECT
                                                mzai mza,ladoi lado, segi se
                                                FROM '.$esquema.'.arc
                                            UNION
                                                SELECT
                                                mzad mza,ladod lado, segd se
                                                FROM '.$esquema.'.arc
                                            ) segs
                                            WHERE mza like :radio
                                            GROUP BY se
                                            ;',['radio'=>$radio.'%']);
                }catch(QueryException $e){
                    Log::error('No se pudieron obtener los segmentos de los
                    arcos');
                    return [];
                }
                return [];
            }

            public static function getCantMzas(Radio $radio,$esquema=null){
                $esquemas=$radio->Esquemas;
                $esquema=$radio->esquema;
                Log::debug('Buscando Mzas para radio '.$radio->codigo);
                $prov=substr($radio->codigo,0,2);
                $dpto=substr($radio->codigo,2,3);
                $frac=substr($radio->codigo,5,2);
                $rad=substr($radio->codigo,7,2);
                if(isset($esquema)){
                  if (in_array($esquema,$esquemas)){
                    Log::debug('Buscando Mzas para radio '.$radio->codigo.' en esquema '.$esquema);
                  }else{
                    Log::warning('Buscando Mzas para radio '.$radio->codigo.' en esquema '.$esquema.' fuera de lo esperado');
                  }
                  try{
                    return (int) DB::select("
                               SELECT count( distinct mza)  cant_mzas
                               FROM ".$esquema.".conteos WHERE prov=".$prov." and dpto = ".$dpto." and
                               frac=".$frac." and radio=".$rad." ;")[0]->cant_mzas;
                   }catch(QueryException $e){
                            if ($e->getCode() == '42P01'){
                              Log::debug('No existe o hay problemas con es conteos en esquema: '.$esquema);
                          }else{
                              Log::error('No se encontro conteo manzanas para radio '.$radio.$e);
                          }
                   }
                } else {
                    $sumas_mzas=[];
            foreach ($esquemas as $esquema) {
                try{
                        $mzas = (int) DB::select(
                          "SELECT count( distinct mza)  cant_mzas
                           FROM ".$esquema.".conteos WHERE prov=".$prov." and dpto = ".$dpto." and
                           frac=".$frac." and radio=".$rad." ;")[0]->cant_mzas;
                         $sumas_mzas[]=$mzas;
                         Log::info('Manzanas para radio '.$radio->codigo.' contadas en equema '.$esquema.' : '.$mzas);
                } catch (QueryException $e){
                            if ($e->getCode() == '42P01'){
                              Log::debug('No existe o hay problemas con tabla de conteo en esquema: '.$esquema);
                          }else{
                              Log::error('No se encontro conteo manzanas para radio '.$radio.$e);
                          }
                        }
                    }
                    if (count($sumas_mzas)>1){
                        Log::warning('Se encontraron mzas en mas de un esquema');
                        return max($sumas_mzas);
                    }elseif (count($sumas_mzas)==1){
                        return (int) $sumas_mzas[0];
                    }
              }
                Log::warning('NO se encontro conteo de mzas en ningún esquema de los buscados para el radio '.$radio->codigo);
                return -3;
            }

       public static function isSegmentado(Radio $radio=null,$esquema=null){
            if ($radio){
                $filtro = " and (frac,radio) = ('".$radio->CodigoFrac."','".$radio->CodigoRad."') ";
              } else {
                $filtro = '';
             }
             if(isset($esquema) and $esquema == $radio->esquema){
                 Log::debug('Viendo si esta segmentado el radio '.$radio->codigo.' en esquema '.$esquema);
                 if ($esquema != $radio->esquema){
                   Log::warning('Raro! Buscando segmentacion para radio '.$radio->codigo.' en esquema '.$esquema.
                                ' fuera del esperados '.$this->esquema);
                 }
                  try{
                    return (int) DB::select("SELECT count(distinct segmento_id) FROM ".$esquema.".segmentacion s JOIN
                           ".$esquema.".listado l ON s.listado_id=l.id WHERE segmento_id is not null ".
                            ($filtro)
                           ." ;")[0]->count;
                   }catch(QueryException $e){
                            if ($e->getCode() == '42P01'){
                              Log::debug('No existe o hay problemas con segmentacion en esquema: '.$esquema);
                          }else{
                              Log::error('No se encontro segmentacion manzanas para radio '.$radio.$e);
                          }
                   }
             }else{
             $count=0;
             foreach($radio->esquemas as $esquema){
                  try {
                    $count += (int) DB::select("SELECT count(distinct segmento_id) FROM ".$esquema.".segmentacion s JOIN
                           ".$esquema.".listado l ON s.listado_id=l.id WHERE segmento_id is not null ".
                            ($filtro)
                           ." ;")[0]->count;
               Log:info('Buscando si está segmentado '.$radio->codigo.' en esquema: '.$esquema.' segmentos: '.$count);
                       } catch (QueryException $e)  {
                            if ($e->getCode() == '42P01'){
                              Log::debug('No existe o hay problemas con esquema: '.$esquema);
                          }
                       }
              }
              return $count;
        }
     }

        public static function darPermisos($esquema,$grupo='geoestadistica'){
                try {
                DB::statement('GRANT USAGE ON SCHEMA "'.$esquema.'" TO '.$grupo);
                DB::statement('GRANT SELECT ON ALL TABLES IN SCHEMA  "'.$esquema.'" TO '.$grupo);
                DB::statement('ALTER DEFAULT PRIVILEGES IN SCHEMA  "'.$esquema.'"
                                GRANT SELECT ON TABLES TO '.$grupo);
                    } catch (QueryException $e)  {
                        Log::Error('No se pudieron asignar permisos'.$e);
                        return false;}
                Log::Debug('Se establecieron permisos para geoestadistica');
                return true;
        }

        public static function addUser($usuario,$grupo='geoestadistica'){
                try {
                        DB::unprepared("GRANT ".$grupo." TO ".$usuario.";");

                    } catch (QueryException $e)  {
                        Log::Debug('No se pudo agregar al grupo '.$grupo.' al '.$usuario);
            return false;
        }
                Log::Debug('Se pudo agregar al grupo '.$grupo.' al '.$usuario);
                return true;
        }

        // Carga geometria en topologia y genera manzanas, fracciones y radios.
        // Necesita arc y lab.
   public static function cargarTopologia($esquema, $tolerancia = null)
     {
          if (Schema::hasTable($esquema.'.arc_topology')){
                Log::Debug('NO Se carga topo ya existente '.$esquema);
                flash('NO Se carga topo ya existente '.$esquema)->warning();
                return false;
          }
            try{
                DB::beginTransaction();
                if (is_numeric($tolerancia)) {
                  DB::statement(" SELECT indec.cargarTopologia(
                  '".$esquema."','arc',$tolerancia);");
                } else {
                  DB::statement(" SELECT indec.cargarTopologia(
                  '".$esquema."','arc');");
                }
                DB::statement(" DROP TABLE if exists ".$esquema.".manzanas;");
                DB::statement(" CREATE TABLE ".$esquema.".manzanas AS SELECT * FROM
                ".$esquema.".v_manzanas;");
                DB::statement(" DROP TABLE if exists ".$esquema.".fracciones;");
                DB::statement(" CREATE TABLE ".$esquema.".fracciones AS SELECT * FROM
                ".$esquema.".v_fracciones;");
                DB::statement(" DROP TABLE if exists ".$esquema.".radios;");
                DB::statement(" CREATE TABLE ".$esquema.".radios AS SELECT * FROM
    ".$esquema.".v_radios;");
                DB::commit();

      } catch(QueryException $e) {
          DB::Rollback();
          if ($e->getCode()=='P0001'){
              self::setLabfromPol($esquema);
              self::cargarTopologia($esquema);
              Log::warning('Se pudo cargar la topologia creando etiquetas desde los poligonos para '.$esquema .' -> '.$e);
              return true;
          }
          if ($e->getCode()=='XX000'){
              if (is_null($tolerancia)) {
                 if (self::cargarTopologia($esquema,1)) {
                     Log::warning('Se pudo cargar la topologia utilizando tolerancia de 1 '.$esquema .' -> '.$e);
                     return true;
                  }
               } else {
                  Log::error('No se pudo cargar la topologia ni utilizando tolerancia de '.$tolerancia.' para '.$esquema .' -> '.$e);
                  $result = DB::select("SELECT distinct a.* from indec.crossTopologia('".$esquema."','arc') arc_cross join ".$esquema.".arc a using (ogc_fid)");
                  $cross_result = collect($result)->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                  flash('Arcos con posibles problemas (a revisar): '.$cross_result)->error()->important();
               }
          }
          Log::error('No se pudo cargar la topologia para '.$esquema.' ! '.$e);
          return false;
     }
     Log::info('Se generaron fracciones, radios y manzanas en '.$esquema);
     return true;
   }

        // Carga geometria en topologia pais genera fracciones y radios.
        // Carga geometria en topologia y genera fracciones y radios pais.
        // Necesita arc y lab.
        public static function cargarTopologiaPais($esquema)
        {
            try{
                DB::beginTransaction();
                DB::statement(" SELECT indec.cargar_topologia_pais(
                '".$esquema."','arc');");
                DB::statement(" DROP TABLE if exists ".$esquema.".fracciones_pais;");
                DB::statement(" CREATE TABLE ".$esquema.".fracciones_pais AS SELECT * FROM
                ".$esquema.".v_fracciones_pais;");
                DB::statement(" DROP TABLE if exists ".$esquema.".radios_pais;");
                DB::statement(" CREATE TABLE ".$esquema.".radios_pais AS SELECT * FROM
    ".$esquema.".v_radios_pais;");
                DB::commit();

            }
            catch(QueryException $e){
                DB::Rollback();
                Log::error('No se pudo cargar la topologia pais...'.$e);
                flash('Error cargando topología pais en '.$esquema)
                  ->error()->important();
                return false;
            }
            catch(Exception $e){
                DB::Rollback();
                Log::error('No se pudo cargar la topologia pais...'.$e);
                flash('Error raro cargando topología pais en '.$esquema)
                  ->error()->important();
                return false;
            }
            Log::debug('Se generaron fracciones, radios pais en '.$esquema);
            flash('Se cargó topología pais. Se generaron fracciones, radios pais en '.$esquema)
                  ->success()->important();
            return true;
        }

        // DROPEA esquema de topologia si quedo desfazado por rollback mal
        // hecho
        public static function dropTopologia($esquema)
        {
            try{
                DB::statement(" SELECT topology.dropTopology('".$esquema."');");
            }catch(QueryException $e){
                Log::error('No se pudo borrar la topologia de topology');
            }
            Log::debug('Se borro la topologia ');

            try{
                DB::statement(' DROP SCHEMA IF EXISTS "'.$esquema.'" CASCADE ;');
            }catch(Exception $e){
                Log::error('No se pudo borrar la topologia');
            }
            Log::debug('Se borro esquema con topos ');
        }


    // Crea secuencia para id de segmentos.
    //
    public static function addSequenceSegmentos($esquema,$reset = false)
    {
        try{
            if($reset){
                DB::unprepared('DROP sequence IF EXISTS '.$esquema.'.segmentos_seq CASCADE');
                }
            DB::unprepared('create sequence IF NOT EXISTS '.$esquema.'.segmentos_seq');
        }catch(QueryException $e){
            Log::error('No se pudo recrear la secuencia');
        }
        Log::debug('Se genero una nueva secuencia de segmentos, si no exisitia.');
    }


    // Cambio a bigint id segmentacion.
    public static function cambiarSegmentarBigInt($esquema)
    {
        try{
            DB::statement("ALTER TABLE \"e".$esquema."\".segmentacion ALTER
            COLUMN segmento_id SET DATA TYPE bigint ;");
        }catch(QueryException $e){
            Log::error('NO Se pudo realizar el cambio del tipo segmento_id a bigint');
        }
        Log::debug('Se cambio el tipo segmento_id a bigint');
    }

    // Generar indice en tabla de listados.
    public static function addIndexListado($esquema)
    {
        try{
            DB::statement(
            "create index IF NOT EXISTS listado_piso on ".$esquema.".listado
                (prov, dpto, codloc, frac, radio, mza, lado,
                nrocatastr, sector, edificio, entrada, piso);");
    }catch(QueryException $e){
     Log::debug('No se pudo generar indice de lado en '.$esquema);
    }
     Log::debug('Se creo indice de lado en '.$esquema);
    }

    // Generar indice en tabla de listados.
    public static function createIndex($esquema,$tabla,$campos,$tipo_indice='btree')
    {
        try{
            DB::statement(
            "create index IF NOT EXISTS ".$esquema."_".$tabla."_".str_replace(array(' ', ',','(',')'),'_',$campos)." on ".$esquema.".".$tabla."
               USING ".$tipo_indice."
               (".$campos.")");
        }catch(QueryException $e){
            Log::error('No se pudo generar indice de en '.$esquema.' para tabla '.$tabla.' para '.$campos,[$e]);
            return;
        }
     Log::debug('Se creo indice de en '.$esquema.'.'.$tabla.' para '.$campos);
    }

// Generar indice en tabla de listados.
public static function addIndexListadoId($esquema)
{
    try{
        DB::statement(
         "create index IF NOT EXISTS idx_listado_id on ".$esquema.".listado
            (id);");
    }catch(QueryException $e){
     Log::debug('No se pudo generar indice en id para '.$esquema);
    }
     Log::debug('Se creo indice en id para '.$esquema);
}

// Generar indice en tabla de listados x radio.
public static function addIndexListadoRadio($esquema)
{
    try{
        DB::statement(
         "create index IF NOT EXISTS listado_radio on ".$esquema.".listado
            (prov, dpto, codloc, frac, radio);");
    }catch(QueryException $e){
     Log::debug('No se pudo generar indice de radio en '.$esquema);
    }
     Log::debug('Se creo indice de radio en '.$esquema);
}

// Generar indice en id de tabla.
public static function addIndexId($tabla)
{
    try{
        DB::statement(
         "create index IF NOT EXISTS id_".$tabla." on ".$tabla."
            (id);");
    }catch(QueryException $e){
     Log::debug('No se pudo generar indice en id para '.$tabla);
    }
     Log::debug('Se creo indice en id para '.$tabla);
}

// Generar salida pxseg -> tabla.
public static function getPxSeg($esquema)
{
    try{
        return DB::select(
               "select row_number() over() id,* FROM (
           select r3.prov::character(2),null::character(4) codmuni,null::character(4) catmuni,
                       '1046'::character(4) codaglo,null::character(2) nroentidad, lpad(r3.dpto::text,3,'0')::character(3) depto,
                       lpad(r3.codloc::text,3,'0')::character(3) codloc, lpad(r3.frac::text,2,'0'::text)::character(2) frac,
                       lpad(r3.radio::text,2,'0'::text)::character(2) radio,'U'::character(1) tiporad,l.mza mza,
           l.lado lado,'P' tipoform,seg seg,
           string_agg(distinct case when tipoviv in ('VE','CC','BC','CA') then tipoviv
                                                when indec.contar_vivienda(tipoviv) is not null then null
                                                when tipoviv in ('LSV',null,'') then null
                                                else 'incluye' end
                        ,' ') ve_cc_bc_ca, 0 rural,
                       count(indec.contar_vivienda(tipoviv)) vivs
                 from ".$esquema.".r3 JOIN ".$esquema.".segmentacion s On s.segmento_id=r3.segmento_id
                               JOIN ".$esquema.".listado l ON l.id=s.listado_id
                 group by 1,2,3,4,5,6,7,8,9,10,11,12,13,14
                 order by frac,radio,seg,mza,lado) foo ;
                ");

       }catch(QueryException $e){
            Log::error('Error al generar la PxSeg '.$esquema.$e);
            return 'Sin pxseg';
       }
}

    public static function setLabfromPol($esquema,$srid_id=null)
    {
       try{
         DB::beginTransaction();
         $srid_id = DB::select("SELECT st_srid(wkb_geometry) from ".$esquema.".lab limit 1;")[0]->st_srid;
         DB::statement("ALTER TABLE ".$esquema.".lab  ADD COLUMN IF NOT EXISTS wkb_geometry_lab
                      geometry(POINT,".$srid_id.");");
         DB::statement("UPDATE ".$esquema.".lab SET wkb_geometry_lab = st_transform(st_centroid(wkb_geometry),st_srid(wkb_geometry))");
         DB::statement('ALTER TABLE '.$esquema.'.lab RENAME wkb_geometry TO wkb_geometry_pol');
         DB::statement('ALTER TABLE '.$esquema.'.lab RENAME wkb_geometry_lab TO wkb_geometry');

         DB::commit();
       } catch(QueryException $e) {
         Log::warning('Problemas al generar lab de pol srid: '.$srid_id.' en '.$esquema.': '.$e);
         try{
           DB::statement("ALTER TABLE ".$esquema.".lab ALTER COLUMN wkb_geometry SET DATA TYPE
                      geometry(LINESTRING,".$srid_id.")
                      USING st_setsrid(wkb_geometry,".$srid_id.");");
           return;
         } catch(QueryException $e) {
           Log::debug('Se estableció el SRS: '.$srid_id.' en '.$esquema);
         }
      }
    }


    public static function setSRID($esquema,$srid_id)
    {
    try{
        DB::statement("UPDATE ".$esquema.".arc SET wkb_geometry=st_setsrid(wkb_geometry,".$srid_id.");");
        DB::statement("UPDATE ".$esquema.".lab SET wkb_geometry=st_setsrid(wkb_geometry,".$srid_id.");");
    }catch(QueryException $e){
      Log::warning('Problemas al establecer el SRS: '.$srid_id.' en '.$esquema.': '.$e);
      try{
        DB::statement("ALTER TABLE ".$esquema.".arc ALTER COLUMN wkb_geometry SET DATA TYPE
                      geometry(LINESTRING,".$srid_id.")
                      USING st_setsrid(wkb_geometry,".$srid_id.");");
        DB::statement("ALTER TABLE ".$esquema.".lab  ALTER COLUMN wkb_geometry TYPE
                      geometry(POINT,".$srid_id.") USING st_setsrid(wkb_geometry,".$srid_id.");");
        return;
      }catch(QueryException $e){
        try{
          DB::statement("drop view if exists ".$esquema.".descripcion_segmentos cascade;");
          DB::statement("drop view if exists ".$esquema.".v_radios cascade;");
          DB::statement("drop view if exists ".$esquema.".v_fracciones cascade;");
          DB::statement("drop view if exists ".$esquema.".v_manzanas cascade;");
        }catch(QueryException $e){
          dd($e);
        }
        //Log::error('Reintentar.', [$e]);
        self::setSRID($esquema,$srid_id);
        return;
      }
    }
     Log::debug('Se estableció el SRS: '.$srid_id.' en '.$esquema);
    }

    //Generar R3.
    public static function generarR3Esquema($esquema)
    {
//        try{
//    self::generarR3($esquema);
//        }catch(QueryException $e){
            Log::error('TODO Función sin definir, generar R3 del esquema completo para el equema '.$esquema);
            return false;
//        }
//        Log::info('Se dió permiso a '.$rol.' sobre '.$tabla.'.');
//        return true;
    }

    // Generar informe de avances del uso del segmentador.
    public static function getAvances($filtro=null)
    {
        try{
            return DB::select(
           "SELECT l.codigo, l.nombre localidad,
      a.codigo codaglo,a.nombre aglomerado,
            count(*) radios,
            count(r.resultado) probados,
            round((count(r.resultado)/(1.0*count(*)))*100,1) segmentado,
            round((count(r.resultado)/(1.0*count(*)))*100,1) cant,
             max(date(updated_at)) fecha,
             max(date(updated_at)) hecho
        from localidad l JOIN aglomerados a ON a.id=l.aglomerado_id
             JOIN radio_localidad ON l.id=localidad_id
             JOIN radio r ON r.id=radio_localidad.radio_id
        WHERE r.updated_at is not null
        GROUP BY 1,2,3,4
        ORDER BY count(r.resultado) desc,a.codigo,l.codigo;");
       }catch(QueryException $e){
            Log::error('Error al consultar avances en radios '.$filtro.$e);
            return 'Sin resultados de avances';
       }
    }

    // Generar informe de avances del uso del segmentador.
    public static function getAvancesProv($filtro=null)
    {
        try{
            return DB::select(
              "select substr(codigo,1,2) prov,date(updated_at) hecho,
                      count(case when resultado is not null then 1 else null end) cant
                from radio
                where updated_at is not null
                group by 1,date(updated_at)
                order by substr(codigo,1,2),date(updated_at) asc;");
       }catch(QueryException $e){
            Log::error('Error al consultar avances en radios resumindos por provincias '.$filtro.$e);
            return 'Sin resultados de avances';
       }
    }

    // Generar informe de avances del uso del segmentador.
    // Acumulado
    public static function getAvanceProvAcum($filtro=null)
    {
        try{
            return DB::select(
"with hechos_por_dia as (
  select substr(codigo,1,2) prov,date(updated_at) hecho,
  count(case when resultado is not null then 1 else null end) cant
  from radio
  where updated_at is not null
  group by 1,date(updated_at)
  )
select hoy.prov, hoy.hecho, sum(antes.cant) cant
from hechos_por_dia hoy
join hechos_por_dia antes
on antes.prov = hoy.prov and antes.hecho <= hoy.hecho
group by 1,2
order by 1,2
    ;");
       }catch(QueryException $e){
            Log::error('Error al consultar avances en radios segmentados acumulados x provincia'.$filtro.$e);
            return 'Sin resultados de avances';
       }
    }

    /* Junta listados de todos los esquemas con segmentacion. *\
     * Genera vistas para aplicación censar.
     */
    public static function juntaListadosSegmentados($filtro=null)
    {
        try{
            DB::beginTransaction();
            if (Schema::hasTable('listados_segmentados')) {
                DB::statement("DROP TABLE if exists public.listados_segmentados CASCADE;");
            }
            DB::statement("CREATE TABLE public.listados_segmentados AS SELECT * FROM indec.listados();");
            $result = DB::select("SELECT Count(*) from listados_segmentados;")[0]->count;
            self::darPermisosTabla('listados_segmentados');
            $result_vista_censar = DB::unprepared(
                "CREATE or REPLACE VIEW public.listado_pre_censar as
                 (select
                  prov as cod_provincia, provincia as desc_provincia,
                  dpto as cod_departamento, departamento as desc_departamento,
                  codloc as cod_localidad, localidad desc_localidad,
                  frac cod_fraccion, radio cod_radio, seg cod_segmento,
                  ccalle, ncalle, nrocatastr nro_catast, piso, casa, dpto_habit,
                  sector, edificio, entrada,
                  row_number() over ( partition by prov,dpto,frac,radio,seg order by mza,lado,
                               case when orden_reco!='' then orden_reco::integer else 0 end::integer
                  ) ordenamiento,
                  'C' estado, tipoviv
                from listados_segmentados)"
            );
            $result_vista_0 = DB::unprepared(
                "create or replace view public.listado_pre_censar_seg_0 as
                             select cod_provincia, desc_provincia,
                                    cod_departamento, desc_departamento,
                                    cod_localidad, desc_localidad,
                                    cod_fraccion, cod_radio, cod_segmento,
                                    'Calle S N' ncalle, 'S/N' nro_catast, '' piso,
                                    '' casa, '' dpto_habit, '' sector, '' edificio,
                                    '' entrada, 0 ordenamiento, 'F' estado
                             from listado_pre_censar group by 1,2,3,4,5,6,7,8,9
                             having count( indec.contar_vivienda(tipoviv) ) = 0 ;"
            );
            $result_vista_1 = DB::unprepared(
                "create or replace view public.listado_pre_censar_vivs AS
                   select cod_provincia, desc_provincia,
                          cod_departamento, desc_departamento,
                          cod_localidad, desc_localidad,
                          cod_fraccion, cod_radio, cod_segmento,
                          ncalle, nro_catast, piso, casa, dpto_habit, sector, edificio,
                          entrada, ordenamiento, estado
                    from listado_pre_censar
                where tipoviv=indec.contar_vivienda(tipoviv);"
            );
            DB::statement("DROP TABLE if exists public.listado_censar;");
            $result_censar = DB::unprepared(
                "CREATE TABLE public.listado_censar AS
                    (select * from public.listado_pre_censar_vivs
                      union
                     select * from public.listado_pre_censar_seg_0);"
            );
            $count_censar = DB::select("SELECT Count(*) from public.listado_censar;")[0]->count;
            DB::commit();
            self::darPermisosTabla('listado_censar');
        }catch(QueryException $e){
            DB::Rollback();
            $result=null;
            Log::error('Error no se pudo actualizar los listados_segmentados '.$filtro.$e);
            return 'Listados Segmentados sin actualizar';
        }
        return 'Se actualizo listados_segmentado con '.$result.' registros y para censar: '.$count_censar;
    }

    // Junta r3 de todos los esquemas.
    public static function juntaR3($filtro=null)
    {
        try{
            DB::beginTransaction();
            if (Schema::hasTable('r3')) {
              DB::statement("DROP TABLE r3;");
            }
            DB::statement("CREATE TABLE r3 AS SELECT * FROM indec.segmentos();");
            $result = DB::select("SELECT Count(*) from r3;")[0]->count;
            self::darPermisosTabla('r3');
            DB::commit();
        }catch(QueryException $e){
            DB::Rollback();
            $result=null;
            Log::error('Error no se pudo actualizar las r3 '.$filtro.$e);
            return 'R3 sin actualizar';
       }
       return 'Se actualizo r3 con '.$result.' registros';
    }

    // Junta Manzanas de todos los esquemas.
    public static function juntaManzanas($filtro=null)
    {
        try{
            DB::beginTransaction();
            if (Schema::hasTable('public.manzanas')) {
              DB::statement("DROP TABLE public.manzanas;");
            }
            DB::statement("CREATE TABLE public.manzanas AS SELECT * FROM indec.manzanas();");
            $result = DB::select("SELECT Count(*) from manzanas;")[0]->count;
            self::darPermisosTabla('manzanas');
            self::createIndex('public','manzanas','prov,dpto,frac,radio,mza');
            self::createIndex('public','manzanas','wkb_geometry','gist');
            DB::commit();
        }catch(QueryException $e){
            DB::Rollback();
            $result=null;
            Log::error('Error no se pudo actualizar las Manzanas '.$filtro.$e);
            return 'Manzanas sin actualizar: '.$e->getMessage();
       }
       return 'Se actualizo manzanas con '.$result.' registros';
    }


    // Junta Localidad a partir de radios :D.
    public static function juntaLocalidades($filtro=null)
    {
        // Tabla con geometría de localdiad y conteo de manzanas
        try{
            DB::beginTransaction();
            $result = DB::select("SELECT Count(*) from indec.radios();")[0]->count;
            if (Schema::hasTable('public.localidad_geo')) {
              DB::statement("DROP TABLE public.localidad_geo;");
            }
            DB::statement("
                CREATE TABLE public.localidad_geo AS
                select st_transform(st_union(wkb_geometry),22184) wkb_geometry, prov, dpto, codloc,
                max(l.nombre) nombre,
                sum(conteo) conteo, count(*) manzanas, sum(cant_lados) lados from public.manzanas
                left join public.localidad l on l.codigo=prov||dpto||codloc
                group by prov, dpto, codloc order by prov, dpto, codloc;"
            );
            self::darPermisosTabla('localidad_geo');
            self::createIndex('public','localidad_geo','prov,dpto,codloc');
            self::createIndex('public','localidad_geo','wkb_geometry','gist');
            DB::commit();
        }catch(QueryException $e){
            DB::Rollback();
            $result=null;
            Log::error('Error no se pudo actualizar las localidades '.$filtro.$e);
            return 'Localidades sin actualizar';
       }
       return 'Se actualizo localidades_geo con '.$result.' registros';
    }

    // Junta Vias de todos los esquemas.
    public static function juntaVias($filtro=null)
    {
        try{
            DB::beginTransaction();
            if (Schema::hasTable('public.vias')) {
              DB::statement("DROP TABLE public.vias;");
            }
            DB::statement("CREATE TABLE public.vias AS SELECT * FROM indec.vias();");
            $result = DB::select("SELECT Count(*) from vias;")[0]->count;
            self::darPermisosTabla('vias');
            self::createIndex('public','vias','codloc');
            self::createIndex('public','vias','geom','gist');
            DB::commit();
        }catch(QueryException $e){
            DB::Rollback();
            $result=null;
            Log::error('Error no se pudo actualizar las Vias '.$filtro.$e);
            return 'Vias sin actualizar';
       }
       return 'Se actualizo vias con '.$result.' registros';
    }

    /**
     * Calcula, caso por caso, la diferencia entre public.vias (para UN
     * esquema) y lo que su cartografía (<esquema>.arc) dice ahora — sin
     * tocar nada. Reemplaza el enfoque de juntaVias() (dropea y reconstruye
     * TODA public.vias, de TODOS los esquemas, cada vez) por algo que se
     * puede revisar y aplicar de a un caso por vez, vía
     * MyDB::aplicarCasosVias().
     *
     * Cada caso identifica una vía por su `link` (= nomencla||nombre||tipo,
     * la clave que ya usa indec.vias()) y trae su `accion`:
     *   - "nueva": está en la cartografía, no en vias todavía.
     *   - "eliminada": está en vias, ya no aparece en la cartografía.
     *   - "modificada": mismo link, cambió tipo/nombre/código/numeración
     *     (comparado con IS DISTINCT FROM, así que también detecta cuando
     *     un valor pasó a o desde NULL).
     *
     * El esquema sigue la convención "e"+prov(2)+dpto(3)+codloc(3) (ver
     * MyDB::getLocs()/moverDBF()); esos 8 caracteres son exactamente el
     * valor que indec.vias()/vias_de_esquema() calculan como columna
     * "codloc" (substr(mzai/mzad,0,9)).
     *
     * @return array{status:string,mensaje:string,casos:array}
     */
    public static function diffViasDesdeEsquema($esquema, $proveedorFondo = 'esri')
    {
        if (! Schema::hasTable($esquema.'.arc')) {
            return ['status' => 'error', 'mensaje' => "Esquema sin tabla arc: $esquema", 'casos' => []];
        }

        if (! Schema::hasTable('public.vias')) {
            return [
                'status' => 'sin_vias',
                'mensaje' => 'public.vias todavía no existe — se crea completa (sin nada que confirmar) en la primera carga.',
                'casos' => [],
            ];
        }

        $codloc = substr($esquema, 1, 8);

        // IS DISTINCT FROM compara también NULLs correctamente (a != NULL da NULL, no true).
        $criterioCambio = 'a.tipo IS DISTINCT FROM n.tipo OR a.nombre IS DISTINCT FROM n.nombre
             OR a.codigo IS DISTINCT FROM n.codigo
             OR a.desdei IS DISTINCT FROM n.desdei OR a.desded IS DISTINCT FROM n.desded
             OR a.hastai IS DISTINCT FROM n.hastai OR a.hastad IS DISTINCT FROM n.hastad';

        $filas = DB::select("
            SELECT coalesce(n.link, a.link) as link,
                   n.tipo as tipo_nuevo, n.nombre as nombre_nuevo,
                   a.tipo as tipo_actual, a.nombre as nombre_actual,
                   a.codigo as codigo_actual, n.codigo as codigo_nuevo,
                   a.desdei as desdei_actual, n.desdei as desdei_nuevo,
                   a.desded as desded_actual, n.desded as desded_nuevo,
                   a.hastai as hastai_actual, n.hastai as hastai_nuevo,
                   a.hastad as hastad_actual, n.hastad as hastad_nuevo,
                   round(ST_Length(a.geom::geography)::numeric) as longitud_actual_m,
                   round(ST_Length(n.geom::geography)::numeric) as longitud_nueva_m,
                   ST_AsSVG(a.geom, 0, 8) as svg_path_actual,
                   ST_AsSVG(n.geom, 0, 8) as svg_path_nuevo,
                   Box2D(a.geom)::text as bbox_actual,
                   Box2D(n.geom)::text as bbox_nuevo
            FROM indec.vias_de_esquema(?) n
            FULL OUTER JOIN (SELECT * FROM public.vias WHERE codloc = ?) a ON a.link = n.link
            WHERE a.link IS NULL OR n.link IS NULL OR ($criterioCambio)
        ", [$esquema, $codloc]);

        $casos = array_map(function ($f) {
            $accion = $f->tipo_actual === null ? 'nueva' : ($f->tipo_nuevo === null ? 'eliminada' : 'modificada');

            // "Completado": ningún campo pisa un valor ya cargado por otro
            // distinto — lo único que cambia es que un campo vacío (código o
            // numeración/altura) pasó a tener un valor real. Candidato obvio
            // para aceptar sin revisar mucho (a diferencia de un valor real
            // reemplazado por otro, que sí conviene mirar).
            $sobrescribeAlgo = false;
            foreach ([['codigo_actual', 'codigo_nuevo'], ['desdei_actual', 'desdei_nuevo'],
                      ['desded_actual', 'desded_nuevo'], ['hastai_actual', 'hastai_nuevo'],
                      ['hastad_actual', 'hastad_nuevo']] as [$colActual, $colNuevo]) {
                if ($f->$colActual !== null && $f->$colActual != $f->$colNuevo) {
                    $sobrescribeAlgo = true;
                    break;
                }
            }
            $completado = $accion === 'modificada' && ! $sobrescribeAlgo;

            return [
                'link' => $f->link,
                'accion' => $accion,
                'tipo' => $f->tipo_nuevo ?? $f->tipo_actual,
                'nombre' => $f->nombre_nuevo ?? $f->nombre_actual,
                'tipo_anterior' => $accion === 'modificada' ? $f->tipo_actual : null,
                'nombre_anterior' => $accion === 'modificada' ? $f->nombre_actual : null,
                'completado' => $completado,
                'longitud_actual_m' => $f->longitud_actual_m !== null ? (int) $f->longitud_actual_m : null,
                'longitud_nueva_m' => $f->longitud_nueva_m !== null ? (int) $f->longitud_nueva_m : null,
                'svg_path_actual' => $f->svg_path_actual,
                'bbox_actual' => $f->bbox_actual,
                'svg_path_nuevo' => $f->svg_path_nuevo,
                'bbox_nuevo' => $f->bbox_nuevo,
            ];
        }, $filas);

        $casos = self::emparejarViasPorNombre($casos);

        // Recién acá, ya resuelto quién es "actual" y quién "nuevo" para
        // cada caso (incluidos los empate, que combinan dos filas SQL
        // distintas), se arma el SVG combinado -- y se descartan los campos
        // crudos de geometría, que no hace falta guardar en el historial.
        $casos = array_map(function ($c) use ($proveedorFondo) {
            $c['svg'] = self::miniSvg($c['svg_path_actual'], $c['bbox_actual'], $c['svg_path_nuevo'], $c['bbox_nuevo'], $c['tipo'], $proveedorFondo);

            return array_diff_key($c, array_flip(['svg_path_actual', 'bbox_actual', 'svg_path_nuevo', 'bbox_nuevo']));
        }, $casos);

        return [
            'status' => 'ok',
            'mensaje' => count($casos)
                ? count($casos).' caso(s) para revisar en '.$esquema.'.'
                : 'Sin diferencias: vias ya está al día con la cartografía de '.$esquema.'.',
            'casos' => $casos,
        ];
    }

    /**
     * SVG chico y autocontenido para previsualizar una geometría de vía en
     * la tabla de diff — solo para ver de un vistazo que el trazo es
     * razonable, no un visor interactivo. Dibuja anterior (si hay) y nueva
     * (si hay) EN LA MISMA imagen/coordenadas — ST_AsSVG ya da ambos paths
     * en el mismo espacio global (geográfico, con Y invertida para SVG), así
     * que no hace falta reproyectar uno respecto del otro, solo calcular un
     * viewBox que cubra a los dos. Ver miniSvgViewBox() para el cálculo de
     * viewBox/fondo (compartido con el fondo satelital/mapa).
     */
    private static function miniSvg(
        $svgPathActual, $bboxActual, $svgPathNuevo, $bboxNuevo, $tipo = null, $proveedorFondo = 'esri',
        array $contextoPaths = [], $anchoDisplay = 190, $altoDisplay = 120
    ) {
        if ($svgPathActual === null && $svgPathNuevo === null) {
            return null;
        }

        $vb = self::miniSvgViewBox($bboxActual, $bboxNuevo);
        if ($vb === null) {
            return null;
        }
        [$xMin, $yMinSvg, $width, $height, $bboxGeografico] = $vb;

        // Pedir el fondo con la MISMA proporción del viewBox -- si no, ArcGIS
        // devuelve una imagen de tamaño fijo que se estira/deforma para
        // calzar en un viewBox angosto o alargado (se veía "rotado" en
        // calles muy largas y finas). El lado máximo pedido escala con el
        // tamaño de despliegue para que una vista grande no salga borrosa.
        $ladoMax = max(220, $anchoDisplay, $altoDisplay);
        $ladoMin = 70;
        $ratio = $width / $height;
        $anchoPx = $ratio >= 1 ? $ladoMax : max($ladoMin, round($ladoMax * $ratio));
        $altoPx = $ratio >= 1 ? max($ladoMin, round($ladoMax / $ratio)) : $ladoMax;

        // Rect gris siempre presente, debajo de la imagen de fondo -- así el
        // halo blanco del trazo se nota igual cuando el fondo satelital se
        // oculta con el switch (ver vias-esquema.blade.php), en vez de
        // quedar un blanco sobre blanco invisible.
        $fondoBase = '<rect x="'.$xMin.'" y="'.$yMinSvg.'" width="'.$width.'" height="'.$height.'" fill="#e9ecef" />';

        $fondo = self::fondoImgEnEspacioGeografico($proveedorFondo, $bboxGeografico, $anchoPx, $altoPx);

        $color = self::colorPorTipoVia($tipo);
        $stroke = max($width, $height, 0.00005) * 0.035;
        $halo = max($width, $height, 0.00005) * 0.06;

        // Otras calles del área, finas y grises, solo de contexto -- no son
        // seleccionables ni tienen halo, van por debajo de todo lo demás.
        $contexto = '';
        foreach ($contextoPaths as $p) {
            if ($p) {
                $contexto .= '<path d="'.e($p).'" fill="none" stroke="#adb5bd" stroke-width="'.($stroke * 0.5).'" stroke-linecap="round" />';
            }
        }

        // Halo blanco debajo de cada trazo para que se note sobre cualquier
        // fondo (foto satelital, no solo un color plano) -- sin esto una
        // línea fina se pierde contra terreno/edificios variados.
        $trazo = function ($path, $dasharray = null) use ($color, $stroke, $halo) {
            if (! $path) {
                return '';
            }
            $dash = $dasharray ? ' stroke-dasharray="'.$dasharray.'"' : '';

            return '<path d="'.e($path).'" fill="none" stroke="#fff" stroke-width="'.$halo.'" stroke-linecap="round" opacity="0.85" />'
                .'<path d="'.e($path).'" fill="none" stroke="'.$color.'" stroke-width="'.$stroke.'" stroke-linecap="round"'.$dash.' />';
        };

        $pathActual = $trazo($svgPathActual, $stroke * 2.2);
        $pathNuevo = $trazo($svgPathNuevo);

        $viewBox = "$xMin $yMinSvg $width $height";

        return '<svg viewBox="'.$viewBox.'" width="'.$anchoDisplay.'" height="'.$altoDisplay.'" preserveAspectRatio="xMidYMid meet" class="svg-via" '
            .'style="margin:6px;min-width:'.round($anchoDisplay * 0.8).'px;min-height:'.round($altoDisplay * 0.8).'px;">'
            .$fondoBase.$fondo.$contexto.$pathActual.$pathNuevo.'</svg>';
    }

    /**
     * Color por tipo de vía, para distinguir CALLE/AVENIDA/RUTA/etc. de un
     * vistazo. `tipo` en datos reales trae basura (mayúsculas variables,
     * typos tipo "CALE"/"CLLE" -- ver via_tipo_correccion) así que se
     * normaliza a mayúsculas/trim antes de buscar, con gris como default
     * para cualquier valor no reconocido en vez de fallar.
     */
    private static function colorPorTipoVia($tipo)
    {
        $colores = [
            'CALLE' => '#2563eb', 'AVENIDA' => '#f97316', 'RUTA' => '#dc2626',
            'AUTOPISTA' => '#7c3aed', 'CAMINO' => '#16a34a', 'PASAJE' => '#0891b2',
        ];

        return $colores[strtoupper(trim((string) $tipo))] ?? '#6b7280';
    }

    /**
     * Viewbox SVG (con Y invertida) + bbox geográfico original, combinando
     * uno o dos Box2D() de texto ("BOX(x0 y0,x1 y1)"). Devuelve
     * [xMin, yMinSvg, width, height, bboxGeografico] o null si no hay nada
     * que mostrar. Separado de miniSvg() porque el bbox geográfico (sin
     * invertir) es lo que hay que mandarle a un servicio de teselas, no el
     * viewBox ya invertido para SVG.
     */
    private static function miniSvgViewBox($bboxActual, $bboxNuevo)
    {
        $cajas = array_filter([$bboxActual, $bboxNuevo]);
        $puntos = [];
        foreach ($cajas as $texto) {
            if (preg_match('/BOX\(([-\d.]+) ([-\d.]+),([-\d.]+) ([-\d.]+)\)/', $texto, $m)) {
                $puntos[] = array_map('floatval', array_slice($m, 1));
            }
        }
        if (empty($puntos)) {
            return null;
        }

        $x0 = min(array_map(fn ($p) => min($p[0], $p[2]), $puntos));
        $x1 = max(array_map(fn ($p) => max($p[0], $p[2]), $puntos));
        $y0 = min(array_map(fn ($p) => min($p[1], $p[3]), $puntos));
        $y1 = max(array_map(fn ($p) => max($p[1], $p[3]), $puntos));

        $width = max($x1 - $x0, 0.00005);
        $height = max($y1 - $y0, 0.00005);
        $padX = $width * 0.15;
        $padY = $height * 0.15;

        $xMin = $x0 - $padX;
        $widthPad = $width + 2 * $padX;
        $yMinSvg = -($y1 + $padY);
        $heightPad = $height + 2 * $padY;

        $bboxGeografico = [$xMin, $y0 - $padY, $xMin + $widthPad, $y1 + $padY];

        return [$xMin, $yMinSvg, $widthPad, $heightPad, $bboxGeografico];
    }

    /**
     * Imagen de fondo (satelital u otra) para el bbox geográfico dado
     * [x0,y0,x1,y1] en lon/lat (EPSG:4326). Devuelve ['url'=>, 'bbox'=>]
     * -- no siempre 'bbox' es el mismo que se pidió: "osm" sirve una
     * tesela entera (grilla fija, no bbox arbitrario), así que el llamador
     * tiene que usar el bbox REAL devuelto para posicionar la imagen, no
     * el que pidió, o queda desalineada.
     *
     * Dos proveedores públicos, sin API key (dependencia externa real --
     * si el servicio cae o cambia, el fondo simplemente no carga, el SVG
     * de las líneas sigue andando igual porque <image> y <path> son
     * independientes):
     *
     * - "esri": World Imagery (ArcGIS Online), export directo por bbox --
     *   acepta lon/lat sin reproyectar, un solo request, imagen exacta.
     * - "osm": tile.openstreetmap.org, el tile server oficial de
     *   OpenStreetMap (mapa de calles, no satelital) -- XYZ estándar, no
     *   bbox: se calcula qué tesela cubre el centro del área pedida al
     *   zoom más cercano, y se devuelve el bbox REAL de esa tesela (no el
     *   bbox pedido). (staticmap.openstreetmap.de, usado antes acá, dejó
     *   de resolver -- cambiado a la infraestructura oficial.)
     */
    /**
     * Fragmento <image> para cuando el espacio del SVG YA es geográfico
     * (lon/lat con Y invertida, como miniSvg() de vías) -- usa el bbox REAL
     * que devuelve tileBackgroundUrl() (no siempre el pedido, ver ahí).
     */
    private static function fondoImgEnEspacioGeografico($proveedor, array $bboxPedido, $anchoPx, $altoPx)
    {
        $tile = self::tileBackgroundUrl($proveedor, $bboxPedido, $anchoPx, $altoPx);
        if (! $tile) {
            return '';
        }
        [$gx0, $gy0, $gx1, $gy1] = $tile['bbox'];

        return '<image class="svg-fondo" href="'.e($tile['url']).'" x="'.$gx0.'" y="'.(-$gy1).'" '
            .'width="'.($gx1 - $gx0).'" height="'.($gy1 - $gy0).'" preserveAspectRatio="none" />';
    }

    private static function tileBackgroundUrl($proveedor, array $bboxGeografico, $anchoPx, $altoPx)
    {
        [$x0, $y0, $x1, $y1] = $bboxGeografico;

        if ($proveedor === 'osm') {
            $centroLon = ($x0 + $x1) / 2;
            $centroLat = ($y0 + $y1) / 2;

            $tileLon = fn ($tx, $n) => $tx / $n * 360 - 180;
            $tileLat = function ($ty, $n) {
                $rad = M_PI - 2 * M_PI * $ty / $n;

                return rad2deg(atan(sinh($rad)));
            };

            // Una tesela es siempre 256x256, sin importar $anchoPx -- el
            // zoom tiene que elegirse para que la tesela CUBRA todo el
            // bbox pedido, no para que tenga buena resolución a $anchoPx
            // (eso dejaba teselas más chicas que el área pedida, se veía
            // solo un pedazo del fondo). Se arranca en una estimación y se
            // baja el zoom hasta que la tesela realmente lo cubra.
            $anchoGrados = max($x1 - $x0, 0.00001);
            $zoom = (int) max(0, min(19, floor(log(360 * $anchoPx / ($anchoGrados * 256), 2))));

            do {
                $n = 2 ** $zoom;
                $tileX = (int) floor(($centroLon + 180) / 360 * $n);
                $latRad = deg2rad($centroLat);
                $tileY = (int) floor((1 - log(tan($latRad) + 1 / cos($latRad)) / M_PI) / 2 * $n);

                $tLon0 = $tileLon($tileX, $n);
                $tLon1 = $tileLon($tileX + 1, $n);
                $tLat0 = $tileLat($tileY + 1, $n); // sur
                $tLat1 = $tileLat($tileY, $n);     // norte

                $cubre = $tLon0 <= $x0 && $tLon1 >= $x1 && $tLat0 <= $y0 && $tLat1 >= $y1;
                if (! $cubre) {
                    $zoom--;
                }
            } while (! $cubre && $zoom >= 0);
            $zoom = max($zoom, 0);

            return [
                'url' => "https://tile.openstreetmap.org/$zoom/$tileX/$tileY.png",
                'bbox' => [$tLon0, $tLat0, $tLon1, $tLat1],
            ];
        }

        // esri (default)
        return [
            'url' => 'https://services.arcgisonline.com/arcgis/rest/services/World_Imagery/MapServer/export'
                .'?bbox='.$x0.','.$y0.','.$x1.','.$y1
                .'&bboxSR=4326&imageSR=4326&size='.$anchoPx.','.$altoPx.'&format=png&transparent=false&f=image',
            'bbox' => $bboxGeografico,
        ];
    }

    /**
     * El `link` que identifica una vía (nomencla||nombre||tipo) sale
     * regenerado entero cada vez que se reprocesa cartografía -- así que
     * "la misma calle con mejor numeración" aparece como una "nueva" y una
     * "eliminada" sin relación explícita entre sí, aunque tipo+nombre no
     * cambiaron. Esto agrupa por (tipo,nombre) y arma un caso "empate"
     * cuando hay EXACTAMENTE una nueva y una eliminada con ese tipo+nombre
     * -- ahí sí hay confianza razonable de que es la misma vía.
     *
     * Cuando hay más de una de cada lado (nombres genéricos tipo "CALLE
     * S/N", "HUELLA", "CAMINO" -- confirmado con datos reales, no es
     * hipotético) NO se empareja a ciegas: no hay forma de saber cuál
     * "nueva" corresponde a cuál "eliminada". Esos casos quedan como
     * nueva/eliminada sueltas, pero marcadas con `grupo_ambiguo` y el
     * tamaño del grupo de cada lado, para que la UI avise por qué no se
     * emparejaron en vez de dejarlo parecer arbitrario.
     */
    private static function emparejarViasPorNombre(array $casos)
    {
        $grupos = collect($casos)->groupBy(fn ($c) => $c['tipo'].'|'.$c['nombre']);
        $resultado = [];

        foreach ($grupos as $grupo) {
            $nuevas = $grupo->where('accion', 'nueva')->values();
            $eliminadas = $grupo->where('accion', 'eliminada')->values();
            $otras = $grupo->whereNotIn('accion', ['nueva', 'eliminada'])->values();

            if ($nuevas->count() === 1 && $eliminadas->count() === 1) {
                $n = $nuevas->first();
                $e = $eliminadas->first();
                $resultado[] = [
                    'link' => $n['link'],
                    'link_anterior' => $e['link'],
                    'accion' => 'empate',
                    'tipo' => $n['tipo'], 'nombre' => $n['nombre'],
                    'tipo_anterior' => $e['tipo'], 'nombre_anterior' => $e['nombre'],
                    'completado' => false,
                    'longitud_actual_m' => $e['longitud_actual_m'],
                    'longitud_nueva_m' => $n['longitud_nueva_m'],
                    'svg_path_actual' => $e['svg_path_actual'],
                    'bbox_actual' => $e['bbox_actual'],
                    'svg_path_nuevo' => $n['svg_path_nuevo'],
                    'bbox_nuevo' => $n['bbox_nuevo'],
                ];
            } else {
                $ambiguo = $nuevas->count() > 1 || $eliminadas->count() > 1;
                foreach ($nuevas->concat($eliminadas) as $c) {
                    $c['grupo_ambiguo'] = $ambiguo;
                    $c['grupo_tamano'] = $ambiguo ? ['nuevas' => $nuevas->count(), 'eliminadas' => $eliminadas->count()] : null;
                    $resultado[] = $c;
                }
            }

            foreach ($otras as $c) {
                $resultado[] = $c;
            }
        }

        return $resultado;
    }

    /**
     * Cuenta, por esquema, las diferencias pendientes contra public.vias
     * (ver MyDB::diffViasDesdeEsquema()) — para poblar un selector de
     * "esquemas con diferencias" sin traer el detalle caso por caso de
     * cada uno. Recorre TODOS los esquemas e________ con tabla arc, así
     * que en una base con cientos de esquemas puede tardar — pensado para
     * una pantalla de admin que se visita a demanda, no para cada carga
     * de página.
     */
    public static function esquemasConViasPendientes()
    {
        return DB::select('SELECT * FROM indec.esquemas_con_vias_pendientes() ORDER BY esquema');
    }

    /**
     * Fondo satelital/mapa para insertar en los SVG que YA arman
     * Radio::getSVG(), Localidad::getSVG() y Localidad::getSVGRadios() --
     * esos tres arman el SVG entero vía SQL (`concat()`), en el SRID
     * métrico nativo del esquema (ej. 22183), con su propio `viewBox()`
     * (convención `-y1` como origen Y, ver Radio.php/Localidad.php). Esta
     * función NO toca esa lógica: dado el mismo `$extent` ("BOX(x0 y0,x1
     * y1)") y la tabla de origen (para sacarle el SRID), devuelve un
     * fragmento `<rect>+<image>` ya en ESE mismo sistema de coordenadas
     * (métrico, Y invertida), listo para insertarse como primer hijo del
     * `<svg>` ya generado. Para pedir el tile reproyecta el extent a
     * 4326 (lon/lat) -- la distorsión de reproyectar un rectángulo chico
     * (escala de radio/localidad) es despreciable para una imagen de
     * fondo, no hace falta alinear pixel a pixel.
     *
     * @return string fragmento SVG, o '' si no se pudo determinar el SRID/extent
     */
    public static function fondoSvgParaExtent($extent, $tablaOrigen, $proveedorFondo = 'esri')
    {
        if (! preg_match('/BOX\(([-\d.]+) ([-\d.]+),([-\d.]+) ([-\d.]+)\)/', $extent, $m)) {
            return '';
        }
        [, $x0, $y0, $x1, $y1] = array_map('floatval', $m);

        // $tablaOrigen se interpola en SQL más abajo -- los llamadores de
        // hoy solo pasan literales propios ('e'.$this->codigo.'.arc', etc.),
        // pero valida igual antes de interpolar: si en el futuro ese valor
        // depende de algo con menos control, esto es lo único que lo frena.
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*\.[a-zA-Z_][a-zA-Z0-9_]*$/', $tablaOrigen)) {
            return '';
        }

        $srid = DB::selectOne("SELECT ST_SRID(wkb_geometry) srid FROM $tablaOrigen LIMIT 1")->srid ?? null;
        if (! $srid) {
            return '';
        }

        $geo = DB::selectOne(
            'SELECT ST_XMin(t) gx0, ST_YMin(t) gy0, ST_XMax(t) gx1, ST_YMax(t) gy1
             FROM (SELECT ST_Transform(ST_SetSRID(ST_MakeEnvelope(?,?,?,?), ?), 4326) t) s',
            [$x0, $y0, $x1, $y1, $srid]
        );
        if (! $geo) {
            return '';
        }

        $width = $x1 - $x0;
        $height = $y1 - $y0;
        $rectY = -$y1; // misma convención que Radio::viewBox()/Localidad::viewBox(): origen Y = -y1

        $ladoMax = 400;
        $ladoMin = 70;
        $ratio = $width / max($height, 0.00001);
        $anchoPx = $ratio >= 1 ? $ladoMax : max($ladoMin, round($ladoMax * $ratio));
        $altoPx = $ratio >= 1 ? max($ladoMin, round($ladoMax / $ratio)) : $ladoMax;

        $tile = self::tileBackgroundUrl($proveedorFondo, [$geo->gx0, $geo->gy0, $geo->gx1, $geo->gy1], $anchoPx, $altoPx);

        $rect = '<rect x="'.$x0.'" y="'.$rectY.'" width="'.$width.'" height="'.$height.'" fill="#e9ecef" />';
        $imagen = '';
        if ($tile) {
            // El bbox que devuelve tileBackgroundUrl() puede no ser el que
            // se pidió (osm sirve teselas de grilla fija, no bbox
            // arbitrario) -- hay que reproyectar ESE bbox real de vuelta al
            // SRID métrico del esquema para posicionar la imagen bien,
            // salvo que sea exactamente el mismo que ya se pidió (esri).
            if ($tile['bbox'] === [$geo->gx0, $geo->gy0, $geo->gx1, $geo->gy1]) {
                [$ix0, $iy0, $ix1, $iy1] = [$x0, $y0, $x1, $y1];
            } else {
                // Para algunos SRID viejos/legacy (confirmado con 22187,
                // Campo Inchauspe faja 7) PROJ transforma 4326, pero no al
                // revés ("could not parse proj string") -- sin esto, un
                // esquema con ese SRID rompía el SVG entero por un fondo
                // opcional. Si falla, sigue sin fondo en vez de crashear.
                try {
                    [$tgx0, $tgy0, $tgx1, $tgy1] = $tile['bbox'];
                    $m = DB::selectOne(
                        'SELECT ST_XMin(t) ix0, ST_YMin(t) iy0, ST_XMax(t) ix1, ST_YMax(t) iy1
                         FROM (SELECT ST_Transform(ST_SetSRID(ST_MakeEnvelope(?,?,?,?), 4326), ?::integer) t) s',
                        [$tgx0, $tgy0, $tgx1, $tgy1, $srid]
                    );
                    [$ix0, $iy0, $ix1, $iy1] = [$m->ix0, $m->iy0, $m->ix1, $m->iy1];
                } catch (QueryException $e) {
                    Log::warning("fondoSvgParaExtent: no se pudo reproyectar de vuelta a SRID $srid: $e");
                    $ix0 = null;
                }
            }
            $imagen = $ix0 !== null
                ? '<image class="svg-fondo" href="'.e($tile['url']).'" x="'.$ix0.'" y="'.(-$iy1).'" '
                    .'width="'.($ix1 - $ix0).'" height="'.($iy1 - $iy0).'" preserveAspectRatio="none" />'
                : '';
        }

        return $rect.$imagen;
    }

    /**
     * Detalle de UNA vía: su versión actual en public.vias (si está) y su
     * versión fresca en la cartografía del esquema (si está) -- las mismas
     * dos fuentes que diffViasDesdeEsquema(), pero para un solo link, con
     * SVG grande y las otras calles del área dibujadas de contexto (ver
     * MyDB::miniSvg(), parámetro $contextoPaths). Incluye el historial de
     * vias_actualizaciones que tocó este link puntual (como link nuevo o
     * como link_anterior de un "empate"), no el del esquema entero.
     *
     * @return array|null null si el link no existe ni en vias ni en la cartografía actual
     */
    public static function detalleVia($esquema, $link, $proveedorFondo = 'esri')
    {
        $codloc = substr($esquema, 1, 8);

        $actual = DB::selectOne(
            'SELECT tipo, nombre, codigo, desdei, desded, hastai, hastad,
                    round(ST_Length(geom::geography)::numeric) as longitud_m,
                    ST_AsSVG(geom, 0, 8) as svg_path, Box2D(geom)::text as bbox
             FROM public.vias WHERE codloc = ? AND link = ?',
            [$codloc, $link]
        );
        $nuevo = DB::selectOne(
            'SELECT tipo, nombre, codigo, desdei, desded, hastai, hastad,
                    round(ST_Length(geom::geography)::numeric) as longitud_m,
                    ST_AsSVG(geom, 0, 8) as svg_path, Box2D(geom)::text as bbox
             FROM indec.vias_de_esquema(?) WHERE link = ?',
            [$esquema, $link]
        );

        if (! $actual && ! $nuevo) {
            return null;
        }

        $vb = self::miniSvgViewBox($actual->bbox ?? null, $nuevo->bbox ?? null);
        $contextoPaths = [];
        if ($vb !== null) {
            [, , , , $bboxGeografico] = $vb;
            // Área de contexto más amplia que el bbox ajustado de la propia
            // calle -- si no, "las otras calles del área" casi nunca entran
            // en cuadro (el bbox de miniSvgViewBox ya está recortado a la
            // calle misma, con poco margen).
            [$x0, $y0, $x1, $y1] = $bboxGeografico;
            $mx = ($x1 - $x0) * 1.5;
            $my = ($y1 - $y0) * 1.5;
            $contextoPaths = collect(DB::select(
                'SELECT ST_AsSVG(geom, 0, 8) as svg_path FROM public.vias
                 WHERE codloc = ? AND link <> ?
                   AND geom && ST_MakeEnvelope(?, ?, ?, ?, 4326)',
                [$codloc, $link, $x0 - $mx, $y0 - $my, $x1 + $mx, $y1 + $my]
            ))->pluck('svg_path')->all();
        }

        $tipo = $nuevo->tipo ?? $actual->tipo;
        $svg = self::miniSvg(
            $actual->svg_path ?? null, $actual->bbox ?? null,
            $nuevo->svg_path ?? null, $nuevo->bbox ?? null,
            $tipo, $proveedorFondo, $contextoPaths, 520, 340
        );

        $historial = \App\Model\ViaActualizacion::where('esquema', $esquema)
            ->get()
            ->filter(function ($h) use ($link) {
                return collect($h->casos)->contains(function ($c) use ($link) {
                    return ($c['link'] ?? null) === $link || ($c['link_anterior'] ?? null) === $link;
                });
            })
            ->load('usuario');

        return [
            'link' => $link,
            'actual' => $actual,
            'nuevo' => $nuevo,
            'tipo' => $tipo,
            'nombre' => $nuevo->nombre ?? $actual->nombre,
            'svg' => $svg,
            'historial' => $historial,
        ];
    }

    /**
     * Aplica SOLO los casos elegidos (subconjunto de lo que devuelve
     * MyDB::diffViasDesdeEsquema()) — no es todo o nada. Para cada caso:
     * borra la versión vieja si existía en vias (no-op si era "nueva") e
     * inserta la versión fresca si todavía existe en la cartografía
     * (no-op si era "eliminada") — una "modificada" queda como
     * borrar+insertar en un solo paso. Reaplica via_tipo_correccion al
     * final sobre lo tocado, y deja registro en vias_actualizaciones
     * (esquema, usuario, motivo/origen, y el detalle de casos aplicados)
     * — ese registro es a la vez el historial y, por MAX(created_at)
     * agrupado por esquema, el "última actualización" de cada uno.
     *
     * @param  array  $casosSeleccionados  subconjunto de los 'casos' que devuelve diffViasDesdeEsquema()
     */
    public static function aplicarCasosVias($esquema, array $casosSeleccionados, $userId = null, $motivo = null)
    {
        if (empty($casosSeleccionados)) {
            return ['aplicados' => 0, 'mensaje' => 'Nada seleccionado, no se aplicó ningún cambio.'];
        }

        // "empate" (ver emparejarViasPorNombre()) borra un link DISTINTO del
        // que inserta -- el resto (nueva/eliminada/modificada/completado)
        // usa el mismo link de los dos lados, como antes.
        $linksInsertar = [];
        $linksBorrar = [];
        foreach ($casosSeleccionados as $c) {
            if ($c['accion'] !== 'eliminada') {
                $linksInsertar[] = $c['link'];
            }
            if ($c['accion'] === 'empate') {
                $linksBorrar[] = $c['link_anterior'];
            } elseif ($c['accion'] !== 'nueva') {
                $linksBorrar[] = $c['link'];
            }
        }

        $codloc = substr($esquema, 1, 8);
        $pgArray = fn (array $links) => '{'.implode(',', array_map(fn ($l) => '"'.str_replace('"', '\"', $l).'"', $links)).'}';

        try {
            DB::beginTransaction();

            if (! Schema::hasTable('public.vias')) {
                DB::statement('CREATE TABLE public.vias AS SELECT * FROM indec.vias_de_esquema(?) WHERE link = ANY(?)', [$esquema, $pgArray($linksInsertar)]);
                self::darPermisosTabla('vias');
                self::createIndex('public', 'vias', 'codloc');
                self::createIndex('public', 'vias', 'geom', 'gist');
            } else {
                if (! empty($linksBorrar)) {
                    DB::statement('DELETE FROM public.vias WHERE codloc = ? AND link = ANY(?)', [$codloc, $pgArray($linksBorrar)]);
                }
                if (! empty($linksInsertar)) {
                    DB::statement('INSERT INTO public.vias SELECT * FROM indec.vias_de_esquema(?) WHERE link = ANY(?)', [$esquema, $pgArray($linksInsertar)]);
                }
            }

            DB::statement(
                'UPDATE public.vias v SET tipo = c.tipo_corregido
                 FROM via_tipo_correccion c
                 WHERE v.codloc = ? AND v.tipo = c.tipo_original',
                [$codloc]
            );

            $agregados = count(array_filter($casosSeleccionados, fn ($c) => $c['accion'] !== 'eliminada'));
            $borrados = count(array_filter($casosSeleccionados, fn ($c) => $c['accion'] !== 'nueva'));

            \App\Model\ViaActualizacion::create([
                'esquema' => $esquema, 'user_id' => $userId, 'motivo' => $motivo,
                'agregados' => $agregados, 'borrados' => $borrados, 'casos' => $casosSeleccionados,
            ]);

            DB::commit();
        } catch (QueryException $e) {
            DB::Rollback();
            Log::error("No se pudo aplicar casos de vias del esquema $esquema: $e");

            return ['aplicados' => 0, 'mensaje' => 'Error al aplicar los cambios — revisar logs.'];
        }

        $n = count($casosSeleccionados);

        return ['aplicados' => $n, 'mensaje' => $n.' caso(s) aplicado(s) para '.$esquema.'.'];
    }

    // MVT de manzanas
    //
    public static function mvtManzanas(Provincia $oProv)
    {
      if( isset($oProv) ){
        try{
            $result = DB::select("select prov,dpto,d.nombre,codloc,l.nombre,
                                     count(distinct frac::text||'-'||radio::text) radios_m_u ,
                                     count(*) segmentos,
                                     sum(viviendas) vivs,
                                     round(1.0*sum(viviendas)/count(*),2) prom
                                     from r3 join departamentos d on
                                        d.codigo=lpad(prov::text,2,'0')||lpad(dpto::text,3,'0')
                                     join localidad l on
                                       l.codigo=lpad(prov::text,2,'0')||lpad(dpto::text,3,'0')||lpad(codloc::text,3,'0')
                                     join radio r on
                                       r.codigo=lpad(prov::text,2,'0')||lpad(dpto::text,3,'0')||lpad(frac::text,2,'0')||lpad(radio::text,2,'0')
                                     WHERE r.tipo_de_radio_id in (1,3) and prov='".$oProv->codigo."' and seg!='90' group by 1,2,3,4,5 ;");
        }catch(QueryException $e){
                $result=null;
                Log::error('No se pudo generar resuemn de la provincia ',[$oProv],$e);
            }
            Log::debug('Se consulto resumen de provincia '.$oProv->codigo);
            return $result;
       }else{
          try {
              $result = DB::select("select prov,dpto,codloc,frac,radio,mza,ST_AsMVT(wkb_geometry) from manzanas");
          } catch (QueryException $e) {
              $result=null;
              Log::error('No se pudo generar resuemn de la provincia ',[$oProv],$e);
          }
          return $result;
          return 'no se seleccionó Provincia';
       }
    }

    // Junta arc de todos los esquemas en public.cuadras.
    public static function juntaCuadras($filtro=null)
    {
        try{
            DB::beginTransaction();
            if (Schema::hasTable('public.cuadras')) {
              DB::statement("DROP TABLE public.cuadras;");
            }
            DB::statement("CREATE TABLE public.cuadras AS SELECT * FROM indec.cuadras();");
            $result = DB::select("SELECT Count(*) from cuadras;")[0]->count;
            self::darPermisosTabla('cuadras');
            self::createIndex('public','cuadras','codloc20');
            self::createIndex('public','cuadras','nombre');
            self::createIndex('public','cuadras','geom','gist');
            DB::commit();
        }catch(QueryException $e){
            DB::Rollback();
            $result=null;
            Log::error('Error no se pudo actualizar las Cuadras '.$filtro.$e);
            return 'Cuadras sin actualizar';
       }
       return 'Se actualizo cuadras con '.$result.' registros';
    }

    // Crea tabla con los srids elegidos de las localidades cargadas
    public static function cargaSrids($filtro=null)
    {
        try{
            DB::beginTransaction();
            if (Schema::hasTable('public.localidad_srid')) {
              DB::statement("DROP TABLE public.localidad_srid;");
            }
            DB::statement("CREATE TABLE public.localidad_srid AS SELECT distinct codloc20, srid FROM indec.cuadras();");
            $result = DB::select("SELECT Count(*) from localidad_srid;")[0]->count;
            self::darPermisosTabla('localidad_srid');
            self::createIndex('public','localidad_srid','codloc20');
            self::createIndex('public','localidad_srid','srid');
            DB::commit();
        }catch(QueryException $e){
            DB::Rollback();
            $result=null;
            Log::error('Error no se pudo actualizar la relación localidad_srid '.$filtro.$e);
            return 'localidad_srid sin actualizar';
       }
       return 'Se actualizo localidad_srid con '.$result.' registros';
    }

    public static function corrigeSrids($filtro=null)
    {
        try {
            self::cargaSrids();
            $result = DB::select("select 'e' || codloc20 as esquema, provincia.srid as srid_id
                                    from localidad_srid
                                    join provincia
                                    on codigo = substr(codloc20,1,2)
                                    where localidad_srid.srid != provincia.srid;
                                ");
            $result = array_map(function ($value) {
                return (array)$value;
                }, $result);
            foreach ($result as $registro) {
                    self::setSRID($registro['esquema'], $registro['srid_id']);
                    self::cargarTopologia($registro['esquema']);
                    self::georeferenciar_listado($registro['esquema']);
            }
        } catch (QueryException $e) {
            Log::error('Error no se pudo corregir localidades con localidad_srid '.$filtro.$e);
            return 'no se pudo corregir localidades con localidad_srid';
        }
        return 'Se corrigieron '.count($result).' localidades';
    }

    public static function cargarToposPais($filtro=null)
    {
        try {
            $result = DB::select("select 'e' || codloc20 as esquema, provincia.srid as srid_id
                                    from localidad_srid
                                    join provincia
                                    on codigo = substr(codloc20,1,2)
                                    ;
                                ");
            $result = array_map(function ($value) {
                return (array)$value;
                }, $result);
        } catch (QueryException $e) {
            Log::error('Error no se pudo revisar las localidades a cargar en topo_pais '.$filtro.$e);
            return 'No se pudo cargar nueva topo_pais';
        }
            $se_encontro = 0; $nuevo = 0;
            foreach ($result as $registro) {
                  try {
                    $radios_pais = DB::select("select * from ".
                        $registro['esquema'].".v_radios_pais limit 1;");
                    $se_encontro = $se_encontro + 1 ;
                    flash($se_encontro.'. Se encontró cargado '.$registro['esquema'].' ')->info()->important();

                  } catch (QueryException $e) {
                    $nuevo = $nuevo + 1;
                    flash($nuevo.'. Cargando... '.$registro['esquema'])->warning()->important();
                    self::cargarTopologiaPais($registro['esquema']);
                    Log::debug('Se cargó la localidad '.$registro['esquema'].' ('.$nuevo.') ');

                    if ($nuevo  % 10 == 0)  {
                      Log::debug('Se ANALYZE pais_topo');
                      DB::statement(" ANALYZE pais_topo.edge;");
                      DB::statement(" ANALYZE pais_topo.edge_data;");
                      DB::statement(" ANALYZE pais_topo.node;");
                      DB::statement(" ANALYZE pais_topo.face;");
                    }
                  }
            }
        Log::debug('Se cargaron localidades: '.count($result));
        return 'Se procesaron '.count($result).' localidades '.
               'Se encontraron '.$se_encontro.' cargadas y '.$nuevo.' nuevas';
    }

    public static function radiosDeListados()
    {
        try{
            DB::beginTransaction();
            if (Schema::hasTable('public.radios_de_listados')) {
              DB::statement("DROP TABLE public.radios_de_listados;");
            }
            DB::statement("CREATE TABLE public.radios_de_listados AS SELECT * FROM indec.radios_de_listados();");
            $result = DB::select("SELECT Count(*) from radios_de_listados;")[0]->count;
            self::darPermisosTabla('radios_de_listados');
            self::createIndex('public','radios_de_listados','radio');
            self::createIndex('public','radios_de_listados','localidad');
            DB::commit();
        }catch(QueryException $e){
            DB::Rollback();
            $result=null;
            Log::error('Error no se pudo actualizar los Radios de ePPDDDLLL.listados '.$e);
            return 'Radios de ePPDDDLLL.listados sin actualizar';
       }
       return 'Se actualizo radios_de_listados con '.$result.' registros';
    }

    public static function radiosDeArcs()
    {
        try{
            DB::beginTransaction();
            if (Schema::hasTable('public.radios_de_arcs')) {
              DB::statement("DROP TABLE public.radios_de_arcs;");
            }
            DB::statement("CREATE TABLE public.radios_de_arcs AS SELECT * FROM indec.radios_de_arcs();");
            $result = DB::select("SELECT Count(*) from radios_de_arcs;")[0]->count;
            self::darPermisosTabla('radios_de_arcs');
            self::createIndex('public','radios_de_arcs','radio');
            self::createIndex('public','radios_de_arcs','localidad');
            DB::commit();
        }catch(QueryException $e){
            DB::Rollback();
            $result=null;
            Log::error('Error no se pudo actualizar los Radios de ePPDDDLLL.arcs '.$e);
            return 'Radios de ePPDDDLLL.arcs sin actualizar';
       }
       return 'Se actualizo radios_de_arcs con '.$result.' registros';
    }
}

