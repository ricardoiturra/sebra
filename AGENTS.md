# SeBra — política obligatoria Docker-First

Esta política prevalece sobre instrucciones de bootstrap, habilidades y directrices generadas que sugieran ejecutar PHP en el host.

## Regla permanente

- Todo PHP se ejecuta exclusivamente dentro de Docker: runtime/CLI, Composer, Artisan, PHPUnit, Pint, Boost/MCP, migraciones, seeders, workers, scheduler y extensiones.
- No instalar ni ejecutar PHP, Composer, Laravel Installer, Herd Lite ni otros ejecutables PHP en el anfitrión. No ejecutar instaladores `php.new`. No utilizar rutas absolutas a un PHP local como alternativa.
- El host se usa para editar archivos, ejecutar Docker y herramientas externas que no requieran PHP local, como Node/Vite y Python/Graphify. Si una herramienta externa necesita PHP, debe invocarlo mediante Docker.
- Si Docker o `web` no están disponibles, diagnosticar y comunicar el bloqueo. Nunca volver a PHP local ni iniciar/recrear servicios productivos automáticamente.

## Servicios reales

`compose.yaml`: `web`, `redis`, `project-import-worker`, `graph-watcher`.

Comprobar disponibilidad sin cambiar estado:

```sh
docker compose config --services
docker compose ps --all
docker compose exec -T web php -v
docker compose exec -T web composer --version
docker compose exec -T web php artisan --version
docker compose exec -T web php artisan route:list
docker compose exec -T web composer --no-plugins check-platform-reqs
```

En este host `rick` figura en el grupo `docker`, pero una sesión antigua puede no tenerlo efectivo. Diagnosticar con `id` y `getent group docker`; renovar la sesión o usar `sg docker -c '<comando Docker autorizado>'` si la membresía ya existe. No modificar grupos ni permisos del socket automáticamente.

## Desarrollo y pruebas

- Usar los comandos Docker del `README.md`. No ejecutar scripts de `vendor/bin/` mediante su shebang en el host: también son PHP.
- Los tests PHP deben usar SQLite `:memory:`, caché/sesión array y vistas compiladas temporales dentro del contenedor. `tests/TestCase.php` comprueba la base aislada. No usar datos productivos para pruebas ni limpiar cachés compartidas como comprobación rutinaria.
- No ejecutar `composer run setup`: el script actual incluye migraciones y NPM, y NPM no está instalado en la imagen PHP. Separar Composer en Docker de Node/Vite en el host.
- `.mcp.json` conecta Boost mediante Docker, no PHP local. La configuración usa `APP_ENV=local` solo para ese proceso CLI y SQLite en memoria; no autoriza operaciones sobre la base productiva. El proceso MCP desactiva la lectura de caché de configuración mediante `APP_CONFIG_CACHE=/tmp/sebra-boost-config-disabled.php`. No ejecutar `boost:install` o actualizar dependencias automáticamente como requisito de cada sesión: ya están instalados.
- La prueba de navegador `tests/js/voice.test.mjs` renderiza Blade mediante `docker compose exec web`; no acepta un ejecutable PHP del host. Node/Chromium pueden ejecutarse fuera de Docker.
- Cambiar versiones/extensiones/herramientas PHP requiere proponer cambios en Dockerfile/Compose, no instalar en el host.

## Protección de datos y operación

`start-laravel.sh` ejecuta migraciones al iniciar `web`. `up`, `restart`, `run` y recreaciones no son verificaciones inocuas de lectura; no ejecutarlos durante auditorías productivas. Un `compose run web` sin aislamiento también hereda los volúmenes productivos.

No eliminar archivos del proyecto, `vendor/`, bases, volúmenes, imágenes necesarias ni configuración persistente. La limpieza del PHP local exige inventario, evaluación de uso compartido, comandos exactos y autorización explícita del usuario. No usar `prune`, `autoremove` o eliminaciones recursivas como atajo.

## Contexto técnico

Laravel 13, Jetstream/Livewire, Spatie Permission, SQLite, Redis, Graphify 0.9.109 y Three.js. Respetar las policies por equipo/proyecto y el flujo de consultas de solo lectura. Por petición del usuario, proyectos y grafo del sistema muestran dos cajas lado a lado: chat a la izquierda y esfera interactiva a la derecha, apiladas en móvil. La esfera permite zoom, arrastre y pausa al hover; no es un fondo decorativo. No activar enfoque de cámara ni selección persistente por consulta sin una nueva solicitud. El contrato backend de IDs se conserva. Las conversaciones persistentes siguen pospuestas por decisión del usuario.

Consultar `CLAUDE.md` para convenciones y `SEBRA_POST_IMPLEMENTATION_AUDIT.md` para evidencias de Docker, instalaciones locales y limpieza propuesta. Las auditorías históricas documentan ejecuciones locales anteriores: no son una autorización para repetirlas.
