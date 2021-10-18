# operativos

1. pg_dump -x -O -t public.operativo --section=pre-data toto5 -f app/developer_docs/operativo.up.sql
2. vi app/developer_docs/operativo.up.sql
 y agregar 'public' en SELECT pg_catalog.set_config('search_path', 'public', false);

3. pg_dump -x -O --inserts -t public.operativo --section=data toto5 -f app/developer_docs/operativo.sql
4. php artisan make:migration create_operativo
5. vi database/migrations/`datetime`_create_operativo.php
 y copiar de otro migrations create_table cambiando nombre de tabla
6. php artisan make:seeder SqlOperativoSeeder
7. vi database/seeds/SqlOperativoSeeder.php
y copiar de otro seeder cambiando nombre de tabla
8. vi database/seeds/DatabaseSeeder.php
y agregar el seeder en el .run()
