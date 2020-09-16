<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Model\Radio;

class Aglomerado extends Model
{
    //
    protected $table='aglomerados';

    protected $fillable = [
        'id','codigo','nombre'
    ];
    public $carto;
    public $listado;
    public $segmentadoListado;
    public $segmentadoLados;

     /**
      * Relación con Localidades, un Aglomerados tiene una o varias localidad.
      *
      */

     public function localidades()
     {
         return $this->hasMany('App\Model\Localidad');
     }


    public function getCartoAttribute($value)
    {
    //select * from information_schema.tables where table_schema = 'e0777' and table_name = 'arc' and table_type = 'BASE TABLE'
        if (Schema::hasTable('e'.$this->codigo.'.arc')) {
            //
            return true;
        }else{
            return false;
        }
    }

    public function getListadoAttribute($value)
    {
        /// do your magic
        if (! $this->listado) { 
        
            if (Schema::hasTable('e'.$this->codigo.'.listado')) {
                //
                $this->listado= true;
            }else{
                $this->listado= false;
            }
        }
        return $this->listado;
    }

    public function getSegmentadolistadoAttribute($value)
    {
        /// do your magic
        if (! $this->segmentadoListado) { 
          if (Schema::hasTable('e'.$this->codigo.'.segmentacion')) {
            //SELECT (count( distinct segmento_id)) segmentos,count(*) domicilios,round( (1.0*count(*))/(count( distinct segmento_id)) ,1) promedio  FROM e0777.segmentacion;
              return $this->segmentadoListado = true;
          }else{
              return $this->segmentadoListado = false;
          }
        }
        return $this->segmentadoListado;
    }

    public function getSegmentadoladosAttribute($value)
    {
        /// do your magic
        if (! $this->segmentadoLados) {
          if (Schema::hasTable('e'.$this->codigo.'.arc')) {
              $radios = DB::table('e'.$this->codigo.'.arc')
                           ->select(DB::raw("distinct substr(mzai,1,12) link"))
                           ->whereNotNull('segi')
                           ->orwhereNotNull('segd');
              if ($radios->count()>0){       
                  return $this->segmentadoLados = true;
              }else{
                  return $this->segmentadoLados = false;
              }
          }else{
              return $this->segmentadoLados = false;
          }
        }
        return $this->segmentadoLados;
    }

    public function setCartoAtribute()
    {   
        if (! $this->carto) {
          if (Schema::hasTable('e'.$this->codigo.'.arc')) {
              return $this->carto = true;
          }else{
              return $this->carto = false;
          }
        }
        return $this->carto;

    }

    public function getRadiosAttribute()
    {
        $radios= null;
        if ($this->Listado==1){
            $radios = DB::table('e'.$this->codigo.'.listado')
                                ->select(DB::raw("prov||dpto||frac||radio as link,codloc,
             '('||dpto||') '||max(nom_dpto)||': '||frac||' '||radio as nombre,
             count(distinct mza) as cant_mzas,
             count(*) as vivs,
             count(CASE WHEN tipoviv='A' THEN 1 else null END) as vivs_a,
             count(CASE WHEN (tipoviv='B1' or tipoviv='B2') THEN 1 else null END) as vivs_b,
             count(CASE WHEN tipoviv='CA/CP' THEN 1 else null END) as vivs_c,
             count(CASE WHEN tipoviv='CO' THEN 1 else null END) as vivs_co,
             count(CASE WHEN (tipoviv='D'  or tipoviv='J'  or tipoviv='VE' )THEN 1 else null END) as vivs_djve,
             count(CASE WHEN tipoviv='' THEN 1 else null END) as vivs_unclas
    "))
                                ->groupBy('prov','dpto','codloc','frac','radio')
                                ->orderBy('prov','asc')
                                ->orderBy('dpto','asc')
                                ->orderBy('codloc','asc')
                                ->orderBy('frac','asc')
                                ->orderBy('radio','asc') 
                                ->get();
        }
            $links=[];
            $new_radios=[];
            $objRadios= Collect (new Radio);
            $nuevos_radios=0;
        foreach($radios as $radio){
            if (Radio::where('codigo',$radio->link)->exists()){
                           $links[]=$radio->link;
            }else{
                $new_radios[]=new Radio (['codigo'=>$radio->link,'nombre'=>'Nuevo: '.$radio->link]);
                $nuevos_radios++;
            }
//            $links[]=$radio->link; };
            }
        if (count($links)>0){
            $objRadios=Radio::whereIn('codigo',$links)->get();
        }
            //dd($new_radios,$nuevos_radios);
            $objs=$objRadios->union(Collect ($new_radios));
        return $objs;

    }


    public function getComboRadiosAttribute()
    {
        $radios= null;
        if ($this->Listado==1){
            $radios = DB::table('e'.$this->codigo.'.listado')
                                ->select(DB::raw("prov||dpto||frac||radio as link,codloc,
             '('||dpto||') '||max(nom_dpto)||': '||frac||' '||radio as nombre,
             count(distinct mza) as cant_mzas,
             count(*) as vivs,
             count(CASE WHEN tipoviv='A' THEN 1 else null END) as vivs_a,
             count(CASE WHEN (tipoviv='B1' or tipoviv='B2') THEN 1 else null END) as vivs_b,
             count(CASE WHEN tipoviv='CA/CP' THEN 1 else null END) as vivs_c,
             count(CASE WHEN tipoviv='CO' THEN 1 else null END) as vivs_co,
             count(CASE WHEN (tipoviv='D'  or tipoviv='J'  or tipoviv='VE' )THEN 1 else null END) as vivs_djve,
             count(CASE WHEN tipoviv='' THEN 1 else null END) as vivs_unclas
    "))
                                ->groupBy('prov','dpto','codloc','frac','radio')
                                ->orderBy('prov','asc')
                                ->orderBy('dpto','asc')
                                ->orderBy('codloc','asc')
                                ->orderBy('frac','asc')
                                ->orderBy('radio','asc') 
                                ->get();
        }
        return $radios;
    }

    public function getSVG()
    {
        // return SVG Carto? Listado? Segmentación?
        // WITH shapes (geom, attribute) AS (a
//
//  VALUES
//    ((SELECT ST_MakeLine(ST_MakePoint(0,0), ST_MakePoint(50,50))), 2),
        //    ((SELECT ST_Envelope(ST_MakeBox2d(ST_MakePoint(0,0), st_makepoint(10,10)))), 3)

        if ($this->Carto){
$height=400;
$width=450;
$escalar=false;
            $extent=DB::select("SELECT box2d(st_collect(wkb_geometry)) box FROM  e".$this->codigo.".arc");
            $extent=$extent[0]->box;
            list($x0,$y0,$x1,$y1) = sscanf($extent,'BOX(%f %f,%f %f)');

             $Dx=$x1-$x0; $Dy=$y1-$y0;
            if (!$height and $width) $height=round($width*$Dy/$Dx);
            if ($height and !$width) $width=round($height*$Dx/$Dy);
            if (!$height and !$width) {$width=round($perimeter*$Dx/2/($Dx+$Dy)); $height=round($width*$Dy/$Dx);}
            $dx=$Dx/$width; $dy=$Dy/$height; $epsilon=min($dx,$dy)/15; // mínima reolución, acho y alto de lo que representa un pixel
            if ($escalar) {$viewBox="0 0 $width $height"; $stroke=2;}
            else { $viewBox=$this->viewBox($extent,$epsilon,$height,$width); $stroke=2*$epsilon;
        }


//            dd($x0);
            $svg=DB::select("
WITH shapes (geom, attribute) AS (
    SELECT wkb_geometry,2 FROM e".$this->codigo.".arc 
  ),
  paths (svg) as (
     SELECT concat(
         '<path d= \"', 
         ST_AsSVG(geom,0), '\" ', 
         CASE WHEN attribute = 0 THEN 'stroke=\"red\" stroke-width=\"3\" fill=\"none\"' 
         ELSE 'stroke=\"black\" stroke-width=\"".$stroke."\" fill=\"green\"' END,
          ' />') 
     FROM shapes
 )
 SELECT concat(
         '<svg id=\"aglo_".$this->codigo."\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"".$x0." -".$y0." ".$x1." ".$y1."\" height=\"400\" width=\"450\">',
         array_to_string(array_agg(svg),''),
         '</svg>')
 FROM paths;
");
        return $svg[0];
        }else{ return "No geodata"; }
        
    }

    private function viewBox($extent,$epsilon,$height,$width){
        list ( $x0, $y0, $x1, $y1 ) = sscanf ( $extent, 'BOX(%f %f,%f %f)' );
        $Dx = $x1 - $x0;
        $Dy = $y1 - $y0;
         $m_izq=.9*$Dx; $m_der=.9*$Dx; $m_arr=0.9*$Dy; $m_aba=.0*$Dy;
        $viewBox = ($x0 - $m_izq) . " " . (- $y1 - $m_arr) . " " . ($Dx + $m_izq + $m_der) . " " . ($Dy + $m_arr + $m_aba);
        if (! $height and ! $width)
            $height = 600;
        if (! $height)
            $height = $width * $Dy / $Dx;
        if (! $width)
           $width = $height * $Dx / $Dy;
        $epsilon = min ( $Dx / $width, $Dy / $height );
        return $viewBox;
    }

}
