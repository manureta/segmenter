/*
titulo: indec.esquemas_con_vias_pendientes()
descripción:
recorre todos los esquemas e________ con tabla arc (mismo criterio que
indec.vias()) y cuenta, por esquema, cuántas vías habría que agregar/borrar
en public.vias si se corriera MyDB::diffViasDesdeEsquema() ahí -- sin traer
el detalle fila por fila, solo para poblar el selector "esquemas con
diferencias" del panel de control de calidad. Devuelve solo los esquemas
con al menos una diferencia.
autor: Claude (SIGeo refactor, fase 0.4/hallazgos)
fecha: 2026-09-30
*/

DROP FUNCTION IF EXISTS indec.esquemas_con_vias_pendientes();
CREATE OR REPLACE FUNCTION indec.esquemas_con_vias_pendientes()
   RETURNS TABLE (
      esquema character varying,
      agregar integer,
      borrar integer
)
language plpgsql volatile
set client_min_messages = 'notice'
as $function$
declare
    rec record;
    codloc text;
    nAgregar integer;
    nBorrar integer;
begin
    for rec in select table_schema
               from information_schema.tables
               where table_schema like 'e________' and table_name = 'arc'
               group by table_schema
               having count(*) = 1
    loop
        codloc := substr(rec.table_schema, 2, 8);

        execute format('
            select count(*) from indec.vias_de_esquema(%L) n
            left join (select * from public.vias where codloc = %L) a on a.link = n.link
            where a.link is null
               or a.tipo is distinct from n.tipo or a.nombre is distinct from n.nombre
               or a.codigo is distinct from n.codigo
               or a.desdei is distinct from n.desdei or a.desded is distinct from n.desded
               or a.hastai is distinct from n.hastai or a.hastad is distinct from n.hastad
        ', rec.table_schema, codloc) into nAgregar;

        execute format('
            select count(*) from (select * from public.vias where codloc = %L) a
            left join indec.vias_de_esquema(%L) n on n.link = a.link
            where n.link is null
               or a.tipo is distinct from n.tipo or a.nombre is distinct from n.nombre
               or a.codigo is distinct from n.codigo
               or a.desdei is distinct from n.desdei or a.desded is distinct from n.desded
               or a.hastai is distinct from n.hastai or a.hastad is distinct from n.hastad
        ', codloc, rec.table_schema) into nBorrar;

        if (nAgregar + nBorrar) > 0 then
            esquema := rec.table_schema;
            agregar := nAgregar;
            borrar := nBorrar;
            return next;
        end if;
    end loop;
end
$function$
