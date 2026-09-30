/*
titulo: indec.vias_de_esquema(esquema)
descripción:
variante de indec.vias() (function.indec.vias.sql) para UN solo esquema, sin
el UNION sobre todos los e________ existentes. Misma forma de fila exacta,
para poder usarse con MyDB::actualizarViasDeEsquema() y actualizar
public.vias incrementalmente (borrar+reinsertar solo lo de ese esquema, sin
rehacer la tabla entera con MyDB::juntaVias()/DROP TABLE).
autor: Claude (SIGeo refactor, fase 0.4/hallazgos)
fecha: 2026-09-30
*/

DROP FUNCTION IF EXISTS indec.vias_de_esquema(text);
CREATE OR REPLACE FUNCTION indec.vias_de_esquema(esquema text)
   RETURNS TABLE (
      geom public.geometry,
      nomencla character varying,
      codigo character varying,
      tipo character varying,
      nombre character varying,
      desdei character varying,
      desded character varying,
      hastai character varying,
      hastad character varying,
      codloc character varying,
      codaglo character varying,
      link character varying,
      created_at timestamp with time zone
)
language plpgsql volatile
set client_min_messages = 'notice'
as $function$
begin
    return query execute format('
        select
          st_union(st_transform(wkb_geometry,4326)) as geom,
          nomencla,
          codigo20::varchar codigo,
          tipo,
          nombre::varchar nombre,
          min(desdei)::varchar desdei,
          min(desded)::varchar desded,
          max(hastai)::varchar hastai,
          max(hastad)::varchar hastad,
          coalesce(substr(mzai,0,9), substr(mzad,0,9))::varchar codloc,
          null::varchar codaglo,
          (nomencla||nombre||tipo)::varchar link,
          now() created_at
        from %I.arc a
        group by
          nomencla, codigo20, tipo, nombre,
          coalesce(substr(mzai,0,9), substr(mzad,0,9))
    ', esquema);
end
$function$
